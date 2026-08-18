<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Helper;

use Wexample\PhpWex\Const\Globals;

final class WorkdirHelper
{
    /**
     * Walk up from the given path until a directory holding a ".wex" folder is
     * found. This mirrors how wex resolves the workdir of the calling process.
     */
    public static function findClosestWorkdir(string $from): ?string
    {
        $current = realpath($from);

        if (false === $current) {
            return null;
        }

        while (true) {
            if (is_dir($current.'/'.Globals::DIR_NAME_WEX)) {
                return $current;
            }

            $parent = dirname($current);

            if ($parent === $current) {
                return null;
            }

            $current = $parent;
        }
    }

    /**
     * Path of the file wex writes when invoked with an output target of "file".
     */
    public static function outputFilePath(string $workdir, string $requestId): string
    {
        return implode('/', [
            rtrim($workdir, '/'),
            Globals::DIR_NAME_WEX,
            Globals::DIR_NAME_TMP,
            Globals::DIR_NAME_OUTPUT,
            $requestId,
        ]);
    }

    public static function appManagerPath(string $workdir): string
    {
        return implode('/', [
            rtrim($workdir, '/'),
            Globals::DIR_NAME_WEX,
            Globals::DIR_NAME_BIN,
            Globals::FILE_NAME_APP_MANAGER,
        ]);
    }
}
