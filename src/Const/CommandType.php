<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Const;

enum CommandType: string
{
    case ADDON = 'addon';
    case APP = 'app';
    case SERVICE = 'service';
    case USER = 'user';

    public function pattern(): string
    {
        return match ($this) {
            self::ADDON => '#^(?:([a-zA-Z0-9-]+)::)?([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)$#',
            self::APP => '#^\.([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)$#',
            self::SERVICE => '#^@([a-zA-Z0-9-]+)::([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)$#',
            self::USER => '#^~([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)$#',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::ADDON => '',
            self::APP => Globals::COMMAND_CHAR_APP,
            self::SERVICE => Globals::COMMAND_CHAR_SERVICE,
            self::USER => Globals::COMMAND_CHAR_USER,
        };
    }

    /**
     * Whether the type carries a scope segment before the group, i.e. an addon
     * name for ADDON or a service name for SERVICE.
     */
    public function hasScope(): bool
    {
        return self::ADDON === $this || self::SERVICE === $this;
    }
}
