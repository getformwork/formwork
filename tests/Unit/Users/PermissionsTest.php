<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Tests\TestCase;
use Formwork\Users\Permissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Permissions::class)]
final class PermissionsTest extends TestCase
{
    public function testEmptyPermissionsGrantNothing(): void
    {
        $permissions = new Permissions([]);

        $this->assertFalse($permissions->has('panel'));
        $this->assertFalse($permissions->has('panel.pages.create'));
    }

    public function testExactPermissionsAreReturned(): void
    {
        $permissions = new Permissions(['panel.pages.create' => true, 'panel.pages.delete' => false]);

        $this->assertTrue($permissions->has('panel.pages.create'));
        $this->assertFalse($permissions->has('panel.pages.delete'));
    }

    #[DataProvider('inheritanceProvider')]
    public function testPermissionsAreInheritedFromTheUpperLevel(array $map, string $permission, bool $expected): void
    {
        $this->assertSame($expected, (new Permissions($map))->has($permission));
    }

    /**
     * @return iterable<string, array{array<string, bool>, string, bool}>
     */
    public static function inheritanceProvider(): iterable
    {
        yield 'granted by the parent' => [['panel' => true], 'panel.pages.create', true];
        yield 'denied by the parent' => [['panel' => false], 'panel.pages.create', false];
        yield 'granted by the grandparent' => [['panel' => true], 'panel.options.updates.install', true];
        yield 'nearest level wins over the parent' => [['panel' => true, 'panel.pages' => false], 'panel.pages.create', false];
        yield 'exact permission wins over the parent' => [['panel' => false, 'panel.pages.create' => true], 'panel.pages.create', true];
        yield 'exact denial wins over the parent' => [['panel' => true, 'panel.pages.create' => false], 'panel.pages.create', false];
        yield 'nearest level wins over the root' => [['panel' => false, 'panel.backup' => true], 'panel.backup.download', true];
        yield 'siblings are unrelated' => [['panel.pages' => true], 'panel.files.upload', false];
        yield 'children do not grant their parent' => [['panel.pages.create' => true], 'panel.pages', false];
        yield 'prefix without a separator is unrelated' => [['panel.page' => true], 'panel.pages.create', false];
        yield 'unknown root' => [['panel' => true], 'other', false];
        yield 'root permission' => [['panel' => true], 'panel', true];
    }

    public function testEmptyPermissionNamesAreNotGranted(): void
    {
        $this->assertFalse((new Permissions(['panel' => true]))->has(''));
    }

    public function testPermissionNamesAreCaseSensitive(): void
    {
        $permissions = new Permissions(['panel.pages' => true]);

        $this->assertFalse($permissions->has('Panel.pages'));
        $this->assertFalse($permissions->has('panel.PAGES'));
    }

    public function testTrailingDotsAreStrippedLikeLevels(): void
    {
        $permissions = new Permissions(['panel' => true]);

        $this->assertTrue($permissions->has('panel.pages.'));
    }

    public function testResultsAreBooleans(): void
    {
        $permissions = new Permissions(['panel' => true]);

        $this->assertTrue($permissions->has('panel.anything'));
        $this->assertFalse($permissions->has('nothing'));
    }

    public function testNonBooleanValuesDoNotGrantPermissionsByAccident(): void
    {
        // Permissions loaded from configuration files may contain strings
        $permissions = new Permissions(['panel.pages' => '', 'panel.files' => '0', 'panel.users' => 0]);

        $this->assertFalse($permissions->has('panel.pages'));
        $this->assertFalse($permissions->has('panel.files'));
        $this->assertFalse($permissions->has('panel.users'));
    }
}
