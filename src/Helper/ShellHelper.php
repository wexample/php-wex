<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Helper;

use Wexample\PhpWex\Common\ShellResult;
use Wexample\PhpWex\Exceptions\ProcessFailedException;

final class ShellHelper
{
    private const READ_CHUNK_SIZE = 8192;
    private const SELECT_TIMEOUT_MICROSECONDS = 200000;

    /**
     * Run a command without going through a shell, so arguments never need
     * escaping.
     *
     * @param string[] $command
     *
     * @throws ProcessFailedException
     */
    public static function run(
        array $command,
        ?string $cwd = null,
        ?float $timeout = null,
        bool $inheritStdio = false,
    ): ShellResult {
        $descriptors = $inheritStdio
            ? [
                0 => ['file', 'php://stdin', 'r'],
                1 => ['file', 'php://stdout', 'w'],
                2 => ['file', 'php://stderr', 'w'],
            ]
            : [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

        $startTime = microtime(true);
        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (! is_resource($process)) {
            throw ProcessFailedException::unableToStart($command);
        }

        if ($inheritStdio) {
            $stdout = '';
            $stderr = '';
            $exitCode = self::waitFor($process, $command, $timeout, $startTime);
        } else {
            fclose($pipes[0]);
            [$stdout, $stderr] = self::readPipes($process, $pipes, $command, $timeout, $startTime);
            $exitCode = self::waitFor($process, $command, $timeout, $startTime);
        }

        proc_close($process);

        return new ShellResult(
            args: $command,
            cwd: $cwd,
            returnCode: $exitCode,
            stdout: $stdout,
            stderr: $stderr,
            startTime: $startTime,
            endTime: microtime(true),
        );
    }

    /**
     * @param resource               $process
     * @param array<int, resource>   $pipes
     * @param string[]               $command
     *
     * @return array{0: string, 1: string}
     */
    private static function readPipes(
        $process,
        array $pipes,
        array $command,
        ?float $timeout,
        float $startTime,
    ): array {
        $buffers = [1 => '', 2 => ''];
        $open = [1 => $pipes[1], 2 => $pipes[2]];

        foreach ($open as $stream) {
            stream_set_blocking($stream, false);
        }

        while ([] !== $open) {
            self::assertNotTimedOut($process, $command, $timeout, $startTime);

            $read = array_values($open);
            $write = null;
            $except = null;

            if (false === stream_select($read, $write, $except, 0, self::SELECT_TIMEOUT_MICROSECONDS)) {
                break;
            }

            foreach ($open as $index => $stream) {
                if (! in_array($stream, $read, true)) {
                    continue;
                }

                $chunk = fread($stream, self::READ_CHUNK_SIZE);

                if (false === $chunk || ('' === $chunk && feof($stream))) {
                    fclose($stream);
                    unset($open[$index]);

                    continue;
                }

                $buffers[$index] .= $chunk;
            }
        }

        return [$buffers[1], $buffers[2]];
    }

    /**
     * @param resource $process
     * @param string[] $command
     */
    private static function waitFor($process, array $command, ?float $timeout, float $startTime): int
    {
        $status = proc_get_status($process);

        while ($status['running']) {
            self::assertNotTimedOut($process, $command, $timeout, $startTime);
            usleep(self::SELECT_TIMEOUT_MICROSECONDS);
            $status = proc_get_status($process);
        }

        return $status['exitcode'];
    }

    /**
     * @param resource $process
     * @param string[] $command
     */
    private static function assertNotTimedOut(
        $process,
        array $command,
        ?float $timeout,
        float $startTime,
    ): void {
        if (null === $timeout || (microtime(true) - $startTime) < $timeout) {
            return;
        }

        proc_terminate($process);

        throw ProcessFailedException::timedOut($command, $timeout);
    }
}
