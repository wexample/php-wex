<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Helper;

use DateTimeImmutable;
use Wexample\PhpWex\Exceptions\ProcessFailedException;

final class RequestHelper
{
    /**
     * Build a request id matching the format wex uses internally:
     * "YYYYmmdd-HHMMSS-ffffff-<pid>".
     */
    public static function generateId(): string
    {
        return (new DateTimeImmutable())->format('Ymd-His-u').'-'.getmypid();
    }

    /**
     * Responses may carry secrets, so they are collected from a directory only
     * the current user can traverse rather than from a guessable path directly
     * under the system temporary directory.
     *
     * @throws ProcessFailedException
     */
    public static function createOutputDirectory(): string
    {
        $path = sys_get_temp_dir().'/wex-'.bin2hex(random_bytes(8));

        if (! mkdir($path, 0o700) && ! is_dir($path)) {
            throw ProcessFailedException::unableToCreateOutputDirectory($path);
        }

        return $path;
    }

    public static function discardOutputDirectory(string $path): void
    {
        foreach (glob($path.'/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($path);
    }
}
