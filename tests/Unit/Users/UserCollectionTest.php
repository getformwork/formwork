<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Authentication\Authenticator;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Users\Fixtures\BuildsUsers;
use Formwork\Users\RoleCollection;
use Formwork\Users\User;
use Formwork\Users\UserCollection;
use Formwork\Users\Users;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCollection::class)]
#[CoversClass(Users::class)]
#[CoversClass(RoleCollection::class)]
final class UserCollectionTest extends TestCase
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

    public function testRolesAreExposed(): void
    {
        $roles = $this->roles();
        $users = new Users([], $roles);

        $this->assertSame($roles, $users->roles());
        $this->assertSame(['admin', 'editor', 'user'], $users->roles()->keys());
    }

    public function testAvailableRolesMapIdsToTitles(): void
    {
        $this->assertSame(
            ['admin' => 'Administrator', 'editor' => 'Editor', 'user' => 'User'],
            $this->users()->availableRoles(),
        );
    }

    public function testUsersAreKeyedByUsername(): void
    {
        $users = $this->users();
        $alice = $this->makeUser(['username' => 'alice'], $users);
        $bob = $this->makeUser(['username' => 'bob'], $users);

        $this->assertCount(2, $users);
        $this->assertSame($alice, $users->get('alice'));
        $this->assertSame($bob, $users->get('bob'));
        $this->assertNull($users->get('carol'));
    }

    public function testOnlyUsersCanBeAdded(): void
    {
        $users = $this->users();

        $this->expectException(\Throwable::class);
        $users->set('alice', 'not a user');
    }

    public function testLoggedInUserIsNullWhenNobodyIsLoggedIn(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'alice'], $users);

        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn(null);
        foreach ($users as $user) {
            $user->authenticator = $authenticator;
        }

        $this->assertNull($users->loggedIn());
    }

    public function testLoggedInUserIsTheOneKnownToTheAuthenticator(): void
    {
        $users = $this->users();
        $alice = $this->makeUser(['username' => 'alice'], $users);
        $bob = $this->makeUser(['username' => 'bob'], $users);

        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn($bob);
        $alice->authenticator = $authenticator;
        $bob->authenticator = $authenticator;

        $this->assertSame($bob, $users->loggedIn());
    }

    public function testUsersCanBeFoundByEmail(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'alice', 'email' => 'alice@example.test'], $users);
        $bob = $this->makeUser(['username' => 'bob', 'email' => 'bob@example.test'], $users);

        $this->assertSame($bob, $users->find(fn(User $user): bool => $user->email() === 'bob@example.test'));
        $this->assertNull($users->find(fn(User $user): bool => $user->email() === 'carol@example.test'));
    }
}
