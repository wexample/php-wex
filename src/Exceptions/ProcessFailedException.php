<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Exceptions;

class ProcessFailedException extends WexException
{
    public static function unableToStart(array $command): self
    {
        return new self(
            sprintf('Unable to start process: %s', implode(' ', $command))
        );
    }

    public static function unableToCreateOutputDirectory(string $path): self
    {
        return new self(
            sprintf('Unable to create the output directory "%s".', $path)
        );
    }

    public static function timedOut(array $command, float $timeout): self
    {
        return new self(
            sprintf('Process timed out after %.3fs: %s', $timeout, implode(' ', $command))
        );
    }
}
