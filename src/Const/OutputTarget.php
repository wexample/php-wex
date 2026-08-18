<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Const;

enum OutputTarget: string
{
    case STDOUT = 'stdout';
    case FILE = 'file';
    case NONE = 'none';
}
