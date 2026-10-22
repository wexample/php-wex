<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Common;

/**
 * Structured result of a shell command execution.
 */
readonly class ShellResult
{
    /**
     * @param string[] $args
     */
    public function __construct(
        public array $args,
        public ?string $cwd,
        public int $returnCode,
        public string $stdout,
        public string $stderr,
        public float $startTime,
        public float $endTime,
    ) {
    }

    public function duration(): float
    {
        return $this->endTime - $this->startTime;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->returnCode;
    }
}
