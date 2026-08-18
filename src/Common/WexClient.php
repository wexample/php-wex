<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Common;

use Wexample\PhpWex\Const\Globals;
use Wexample\PhpWex\Const\OutputFormat;
use Wexample\PhpWex\Const\OutputTarget;
use Wexample\PhpWex\Exceptions\WexBinaryNotFoundException;
use Wexample\PhpWex\Helper\RequestHelper;
use Wexample\PhpWex\Helper\ShellHelper;
use Wexample\PhpWex\Helper\WorkdirHelper;

/**
 * Runs wex commands as subprocesses.
 *
 * Structured results are not read from stdout: wex is asked to write its
 * response as JSON into a private temporary file, which this client then reads
 * and removes. stdout stays available for human-readable rendering.
 *
 * The working directory only decides which commands wex exposes, an app adding
 * its own on top of the addon ones.
 */
final class WexClient
{
    public function __construct(
        private readonly string $binary = Globals::CORE_COMMAND_NAME,
        private readonly ?string $workingDirectory = null,
        private readonly ?float $timeout = null,
    ) {
    }

    /**
     * Target another app through its own ".wex/bin/app-manager" entrypoint,
     * the way a suite drives its sub-packages.
     *
     * The shim resolves the core binary itself (CORE_BIN from /etc/wex.conf,
     * then PATH), so the app stays free to pin a specific wex install and the
     * caller needs nothing but the app path.
     */
    public static function forApp(string $appPath, ?float $timeout = null): self
    {
        return new self(
            binary: WorkdirHelper::appManagerPath($appPath),
            workingDirectory: $appPath,
            timeout: $timeout,
        );
    }

    /**
     * Run a command and return its decoded response.
     *
     * @param string[] $arguments
     */
    public function run(
        CommandAddress|string $command,
        array $arguments = [],
        bool $inheritStdio = false,
    ): WexResult {
        $address = is_string($command) ? CommandAddress::fromString($command) : $command;
        $requestId = RequestHelper::generateId();
        $directory = RequestHelper::createOutputDirectory();

        try {
            $result = $this->execute(
                [
                    Globals::OPTION_FORCE_REQUEST_ID, $requestId,
                    Globals::OPTION_OUTPUT_FORMAT, OutputFormat::JSON->value,
                    Globals::OPTION_OUTPUT_TARGET, OutputTarget::FILE->value,
                    Globals::OPTION_OUTPUT_FILE, $directory.'/'.$requestId,
                    Globals::OPTION_SUBPROCESS,
                    $address->toString(),
                    ...$arguments,
                ],
                $inheritStdio,
            );

            $output = self::readOutput($directory.'/'.$requestId);
        } finally {
            RequestHelper::discardOutputDirectory($directory);
        }

        return WexResult::fromShellResult($result, $requestId, $output);
    }

    /**
     * Run the wex binary with raw arguments, without capturing a structured
     * response.
     *
     * @param string[] $arguments
     */
    public function execute(array $arguments, bool $inheritStdio = false): ShellResult
    {
        return ShellHelper::run(
            [$this->resolveBinary(), ...$arguments],
            $this->resolveWorkingDirectory(),
            $this->timeout,
            $inheritStdio,
        );
    }

    /**
     * Bare names are left to the OS to resolve against PATH; explicit paths
     * must point at a real executable.
     *
     * @throws WexBinaryNotFoundException
     */
    public function resolveBinary(): string
    {
        if (!str_contains($this->binary, '/')) {
            return $this->binary;
        }

        if (!is_executable($this->binary)) {
            throw WexBinaryNotFoundException::forPath($this->binary);
        }

        return $this->binary;
    }

    private function resolveWorkingDirectory(): string
    {
        return $this->workingDirectory ?? (string) getcwd();
    }

    /**
     * Read the response file wex wrote for this request. Returns null when the
     * command produced no output.
     */
    private static function readOutput(string $path): mixed
    {
        if (!is_file($path)) {
            return null;
        }

        $content = trim((string) file_get_contents($path));

        return '' === $content ? null : json_decode($content, true);
    }
}
