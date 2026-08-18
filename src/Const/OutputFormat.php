<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Const;

enum OutputFormat: string
{
    case STR = 'str';
    case JSON = 'json';
    case YAML = 'yaml';
}
