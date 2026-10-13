<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Config\Config;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Role::class)]
final class RoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testIdAndPermissionsAreExposed(): void
    {
        $permissions = new Permissions(['panel' => true]);

        $role = new Role('editor', 'Editor', $permissions, $this->translations());

        $this->assertSame('editor', $role->id());
        $this->assertSame($permissions, $role->permissions());
    }

    public function testPlainTitlesAreReturnedAsTheyAre(): void
    {
        $this->assertSame('Editor', (new Role('editor', 'Editor', new Permissions([]), $this->translations()))->title());
    }

    public function testTitlesAreTranslatedWithTheCurrentTranslation(): void
    {
        $translations = $this->translations();
        $role = new Role('editor', '{{role.editor}}', new Permissions([]), $translations);

        $this->assertSame('Editor', $role->title());

        $translations->setCurrent('it');

        $this->assertSame('Redattore', $role->title());
    }

    public function testTitlesCanMixTextAndTranslations(): void
    {
        $role = new Role('editor', 'Role: {{role.editor}}!', new Permissions([]), $this->translations());

        $this->assertSame('Role: Editor!', $role->title());
    }

    private function translations(): Translations
    {
        FileSystem::write(TESTS_TMP_PATH . '/en.yaml', "role.editor: Editor\n");
        FileSystem::write(TESTS_TMP_PATH . '/it.yaml', "role.editor: Redattore\n");

        $translations = new Translations(new Config(['system' => ['translations' => ['fallback' => 'en']]], resolved: true));
        $translations->loadFromPath(TESTS_TMP_PATH);

        return $translations;
    }
}
