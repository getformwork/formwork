<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Authentication\Authenticator;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Users\Fixtures\TestableUserFixture;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\RoleCollection;
use Formwork\Users\User;
use Formwork\Users\UserCollection;
use Formwork\Users\Users;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCollection::class)]
#[CoversClass(Users::class)]
#[CoversClass(RoleCollection::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class UserCollectionTest extends TestCase
{
    use BuildsUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
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
        $testableUser = $this->makeUser(['username' => 'user'], $users);
        $anotherUser = $this->makeUser(['username' => 'another-user'], $users);

        $this->assertCount(2, $users);
        $this->assertSame($testableUser, $users->get('user'));
        $this->assertSame($anotherUser, $users->get('another-user'));
        $this->assertNull($users->get('third-user'));
    }

    public function testOnlyUsersCanBeAdded(): void
    {
        $users = $this->users();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type Formwork\Users\User, string given');
        $users->set('user', $this->unchecked('not a user'));
    }

    public function testLoggedInUserIsNullWhenNobodyIsLoggedIn(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'user'], $users);

        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn(null);
        foreach ($users as $user) {
            $this->assertInstanceOf(TestableUserFixture::class, $user);
            $user->authenticator = $authenticator;
        }

        $this->assertNull($users->loggedIn());
    }

    public function testLoggedInUserIsTheOneKnownToTheAuthenticator(): void
    {
        $users = $this->users();
        $testableUser = $this->makeUser(['username' => 'user'], $users);
        $anotherUser = $this->makeUser(['username' => 'another-user'], $users);

        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn($anotherUser);
        $testableUser->authenticator = $authenticator;
        $anotherUser->authenticator = $authenticator;

        $this->assertSame($anotherUser, $users->loggedIn());
    }

    public function testUsersCanBeFoundByEmail(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'user', 'email' => 'user@example.test'], $users);
        $testableUser = $this->makeUser(['username' => 'another-user', 'email' => 'another-user@example.test'], $users);

        $this->assertSame($testableUser, $users->find(fn(User $user): bool => $user->email() === 'another-user@example.test'));
        $this->assertNull($users->find(fn(User $user): bool => $user->email() === 'third-user@example.test'));
    }
}
