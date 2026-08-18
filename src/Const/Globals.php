<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Const;

final class Globals
{
    public const CORE_COMMAND_NAME = 'wex';

    public const DIR_NAME_WEX = '.wex';
    public const DIR_NAME_TMP = 'tmp';
    public const DIR_NAME_OUTPUT = 'output';
    public const DIR_NAME_BIN = 'bin';
    public const FILE_NAME_APP_MANAGER = 'app-manager';

    public const COMMAND_SEPARATOR_ADDON = '::';
    public const COMMAND_SEPARATOR_GROUP = '/';
    public const COMMAND_SEPARATOR_FUNCTION_PARTS = '__';

    public const COMMAND_CHAR_APP = '.';
    public const COMMAND_CHAR_SERVICE = '@';
    public const COMMAND_CHAR_USER = '~';

    public const OPTION_FORCE_REQUEST_ID = '--force-request-id';
    public const OPTION_IGNORE_MISSING_COMMAND = '--ignore-missing-command';
    public const OPTION_INDENTATION_LEVEL = '--indentation-level';
    public const OPTION_OUTPUT_FORMAT = '--output-format';
    public const OPTION_OUTPUT_TARGET = '--output-target';
    public const OPTION_SUBPROCESS = '--subprocess';
}
