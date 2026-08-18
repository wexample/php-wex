<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Exceptions;

class WexBinaryNotFoundException extends WexException
{
    public static function forBinary(string $binary): self
    {
        return new self(
            sprintf('Unable to locate the wex binary "%s" in PATH.', $binary)
        );
    }
}
