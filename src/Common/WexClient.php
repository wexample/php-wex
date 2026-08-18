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
 * response as JSON into ".wex/tmp/output/<request-id>", which this client then
 * reads and removes. stdout stays available for human-readable rendering.
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
        $cwd = $this->resolveWorkingDirectory();

        $result = $this->execute(
            [
                Globals::OPTION_FORCE_REQUEST_ID, $requestId,
                Globals::OPTION_OUTPUT_FORMAT, OutputFormat::JSON->value,
                Globals::OPTION_OUTPUT_TARGET, OutputTarget::FILE->value,
                Globals::OPTION_SUBPROCESS,
                $address->toString(),
                ...$arguments,
            ],
            $inheritStdio,
        );

        return WexResult::fromShellResult(
            $result,
            $requestId,
            $this->consumeOutput($cwd, $requestId),
        );
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

    public function isAvailable(): bool
    {
        return null !== ShellHelper::findExecutable($this->binary);
    }

    /**
     * @throws WexBinaryNotFoundException
     */
    public function resolveBinary(): string
    {
        $path = ShellHelper::findExecutable($this->binary);

        if (null === $path) {
            throw WexBinaryNotFoundException::forBinary($this->binary);
        }

        return $path;
    }

    private function resolveWorkingDirectory(): string
    {
        return $this->workingDirectory ?? (string) getcwd();
    }

    /**
     * Read then delete the response file wex wrote for this request. Returns
     * null when the command produced no output.
     */
    private function consumeOutput(string $cwd, string $requestId): mixed
    {
        $workdir = WorkdirHelper::findClosestWorkdir($cwd);

        if (null === $workdir) {
            return null;
        }

        $path = WorkdirHelper::outputFilePath($workdir, $requestId);

        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        unlink($path);

        if (false === $content || '' === $content) {
            return null;
        }

        return json_decode($content, true);
    }
}
