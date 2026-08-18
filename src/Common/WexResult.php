<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Common;

/**
 * A ShellResult enriched with the decoded payload wex wrote to its request
 * output file. The payload is null when the command produced no output.
 */
final readonly class WexResult extends ShellResult
{
    /**
     * @param string[] $args
     */
    public function __construct(
        array $args,
        ?string $cwd,
        int $returnCode,
        string $stdout,
        string $stderr,
        float $startTime,
        float $endTime,
        public string $requestId,
        public mixed $output = null,
    ) {
        parent::__construct($args, $cwd, $returnCode, $stdout, $stderr, $startTime, $endTime);
    }

    public static function fromShellResult(
        ShellResult $result,
        string $requestId,
        mixed $output,
    ): self {
        return new self(
            $result->args,
            $result->cwd,
            $result->returnCode,
            $result->stdout,
            $result->stderr,
            $result->startTime,
            $result->endTime,
            $requestId,
            $output,
        );
    }

    public function hasOutput(): bool
    {
        return null !== $this->output;
    }
}
