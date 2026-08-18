<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Exceptions;

class WexBinaryNotFoundException extends WexException
{
    public static function forPath(string $path): self
    {
        return new self(
            sprintf('The wex binary "%s" does not exist or is not executable.', $path)
        );
    }
}
