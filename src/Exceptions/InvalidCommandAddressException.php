<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Exceptions;

class InvalidCommandAddressException extends WexException
{
    public static function forCommand(string $command): self
    {
        return new self(
            sprintf(
                'Unable to parse "%s" as a wex command. Expected one of: '
                .'"addon::group/name", ".group/name", "@service::group/name", "~group/name".',
                $command
            )
        );
    }

    public static function forFunctionName(string $functionName): self
    {
        return new self(
            sprintf(
                'Unable to parse "%s" as a wex command function name. Expected "addon__group__name".',
                $functionName
            )
        );
    }
}
