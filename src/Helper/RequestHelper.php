<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Helper;

use DateTimeImmutable;

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
}
