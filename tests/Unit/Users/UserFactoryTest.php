<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Files\FileFactory;
use Formwork\Http\Request;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Users\Fixtures\BuildsUsers;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserFactory::class)]
final class UserFactoryTest extends TestCase
{
    use BuildsUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testFactoryBuildsUsersWithTheGivenData(): void
    {
        $user = $this->factory()->make(['username' => 'alice', 'fullname' => 'Alice Smith']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('alice', $user->username());
        $this->assertSame('Alice Smith', $user->fullname());
    }

    public function testFactoryBuildsEmptyUsers(): void
    {
        $user = $this->factory()->make([]);

        $this->assertNull($user->username());
        $this->assertSame('en', $user->language());
    }

    public function testEveryCallBuildsANewUser(): void
    {
        $factory = $this->factory();

        $this->assertNotSame($factory->make(['username' => 'alice']), $factory->make(['username' => 'alice']));
    }

    public function testUsersShareTheCollectionOfTheContainer(): void
    {
        $users = $this->users();
        $user = $this->factory($users)->make(['username' => 'alice', 'role' => 'admin']);

        $this->assertSame('admin', $user->role()->id());
        $this->assertTrue($user->isAdmin());
    }

    private function factory(?Users $users = null): UserFactory
    {
        $container = new Container();
        $container->define(Users::class, $users ?? $this->users());
        $container->define(Config::class, new Config(['system' => ['users' => ['paths' => ['accounts' => $this->accountsPath, 'images' => $this->imagesPath]]]], resolved: true));
        $container->define(Request::class, new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']));
        $container->define(FileFactory::class, App::instance()->getService(FileFactory::class));

        return new UserFactory($container);
    }
}
