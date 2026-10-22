<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Common;

use Wexample\PhpWex\Const\CommandType;
use Wexample\PhpWex\Const\Globals;
use Wexample\PhpWex\Exceptions\InvalidCommandAddressException;

/**
 * Structured identity of a wex command, converting between the representations
 * used by the CLI: command string, Python function name and file path.
 */
final readonly class CommandAddress
{
    /**
     * @param string|null $scope addon name for ADDON, service name for SERVICE, null otherwise
     */
    public function __construct(
        public CommandType $type,
        public ?string $scope,
        public string $group,
        public string $name,
    ) {
    }

    public static function fromString(string $command): self
    {
        $command = trim($command);

        foreach ([CommandType::APP, CommandType::SERVICE, CommandType::USER, CommandType::ADDON] as $type) {
            if (! preg_match($type->pattern(), $command, $matches)) {
                continue;
            }

            return $type->hasScope()
                ? new self($type, '' !== $matches[1] ? $matches[1] : null, $matches[2], $matches[3])
                : new self($type, null, $matches[1], $matches[2]);
        }

        throw InvalidCommandAddressException::forCommand($command);
    }

    /**
     * Build from a Python command function name, e.g. "core__registry__build".
     */
    public static function fromFunctionName(string $functionName): self
    {
        $parts = explode(Globals::COMMAND_SEPARATOR_FUNCTION_PARTS, $functionName, 3);

        if (3 !== count($parts) || in_array('', $parts, true)) {
            throw InvalidCommandAddressException::forFunctionName($functionName);
        }

        return new self(CommandType::ADDON, $parts[0], $parts[1], $parts[2]);
    }

    public function toString(): string
    {
        $scope = null !== $this->scope
            ? $this->scope.Globals::COMMAND_SEPARATOR_ADDON
            : '';

        return $this->type->prefix()
            .$scope
            .$this->group
            .Globals::COMMAND_SEPARATOR_GROUP
            .$this->name;
    }

    /**
     * Return the within-addon key, e.g. "registry/build".
     */
    public function toCommandKey(): string
    {
        return $this->group.Globals::COMMAND_SEPARATOR_GROUP.$this->name;
    }

    /**
     * Return the Python function name, e.g. "core__registry__build".
     */
    public function toFunctionName(): string
    {
        if (null === $this->scope) {
            throw InvalidCommandAddressException::forCommand($this->toString());
        }

        return implode(
            Globals::COMMAND_SEPARATOR_FUNCTION_PARTS,
            [$this->scope, $this->group, $this->name]
        );
    }

    /**
     * Return the path relative to a "commands/" or "tests/" base dir.
     */
    public function toRelativePath(string $extension = 'py'): string
    {
        return $this->group.'/'.$this->name.'.'.$extension;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
