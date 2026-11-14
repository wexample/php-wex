<?php

declare(strict_types=1);

namespace Wexample\PhpWex\Tests\Unit\Common;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wexample\PhpWex\Common\CommandAddress;
use Wexample\PhpWex\Const\CommandType;
use Wexample\PhpWex\Exceptions\InvalidCommandAddressException;

class CommandAddressTest extends TestCase
{
    /** @return array<string, array{string, CommandType, ?string, string, string}> */
    public static function commands(): array
    {
        return [
            'addon' => ['core::registry/build', CommandType::ADDON, 'core', 'registry', 'build'],
            'addon without scope' => ['registry/build', CommandType::ADDON, null, 'registry', 'build'],
            'app' => ['.test/run', CommandType::APP, null, 'test', 'run'],
            'service' => ['@mysql-8::db/dump', CommandType::SERVICE, 'mysql-8', 'db', 'dump'],
            'user' => ['~my-group/do-it', CommandType::USER, null, 'my-group', 'do-it'],
        ];
    }

    #[DataProvider('commands')]
    public function testEachTypeIsReadAndWrittenBack(
        string $command,
        CommandType $type,
        ?string $scope,
        string $group,
        string $name,
    ): void {
        $address = CommandAddress::fromString('  '.$command."\n");

        $this->assertSame($type, $address->type);
        $this->assertSame($scope, $address->scope);
        $this->assertSame($group, $address->group);
        $this->assertSame($name, $address->name);
        $this->assertSame($command, $address->toString());
        $this->assertSame($command, (string) $address);
        $this->assertSame($group.'/'.$name, $address->toCommandKey());
        $this->assertSame($group.'/'.$name.'.py', $address->toRelativePath());
    }

    /** @return array<string, array{string}> */
    public static function invalidCommands(): array
    {
        return [
            'empty' => [''],
            'no name' => ['core::registry'],
            'app with scope' => ['.core::test/run'],
            'service without scope' => ['@db/dump'],
            'too deep' => ['core::a/b/c'],
            'forbidden character' => ['core::reg_istry/build'],
        ];
    }

    #[DataProvider('invalidCommands')]
    public function testAStringOfNoTypeIsRefused(string $command): void
    {
        $this->expectException(InvalidCommandAddressException::class);

        CommandAddress::fromString($command);
    }

    public function testAFunctionNameIsAnAddonCommand(): void
    {
        $address = CommandAddress::fromFunctionName('core__registry__build');

        $this->assertSame('core::registry/build', $address->toString());
        $this->assertSame('core__registry__build', $address->toFunctionName());
    }

    public function testAMalformedFunctionNameIsRefused(): void
    {
        $this->expectException(InvalidCommandAddressException::class);

        CommandAddress::fromFunctionName('core__registry');
    }

    public function testACommandWithoutScopeHasNoFunctionName(): void
    {
        $this->expectException(InvalidCommandAddressException::class);

        CommandAddress::fromString('.test/run')->toFunctionName();
    }
}
