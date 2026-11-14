<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Wexample\PhpWex\Exceptions\ProcessFailedException;
use Wexample\PhpWex\Helper\ShellHelper;

class ShellHelperTest extends TestCase
{
    public function testOutputAndExitCodeAreCollected(): void
    {
        $result = ShellHelper::run(['sh', '-c', 'printf out; printf err >&2; exit 3']);

        $this->assertSame('out', $result->stdout);
        $this->assertSame('err', $result->stderr);
        $this->assertSame(3, $result->returnCode);
    }

    public function testAnArgumentIsPassedAsIsWithoutAShell(): void
    {
        $result = ShellHelper::run(['printf', '%s', '$HOME; echo injected']);

        $this->assertSame('$HOME; echo injected', $result->stdout);
        $this->assertSame(0, $result->returnCode);
    }

    public function testATimeoutStopsTheCommand(): void
    {
        $this->expectException(ProcessFailedException::class);

        ShellHelper::run(['sleep', '5'], timeout: 0.2);
    }
}
