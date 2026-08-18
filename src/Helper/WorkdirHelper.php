<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Helper;

use Wexample\PhpWex\Const\Globals;

final class WorkdirHelper
{
    /**
     * wex resolves its workdir as the current directory of the calling process,
     * without ever walking up the tree.
     */
    public static function workdirPath(string $cwd): string
    {
        return rtrim($cwd, '/').'/'.Globals::DIR_NAME_WEX;
    }

    public static function appManagerPath(string $cwd): string
    {
        return implode('/', [
            self::workdirPath($cwd),
            Globals::DIR_NAME_BIN,
            Globals::FILE_NAME_APP_MANAGER,
        ]);
    }
}
