<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Cms\App;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Exceptions\TranslatedException;
use Formwork\Files\FileFactory;
use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Users\Fixtures\BuildsUsers;
use Formwork\Users\ColorScheme;
use Formwork\Users\Exceptions\UserImageNotFoundException;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Users\User;
use Formwork\Users\Utils\Password;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;
use ValueError;

#[CoversClass(User::class)]
final class UserTest extends TestCase
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

    public function testDefaultsAreApplied(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $this->assertSame('alice', $user->username());
        $this->assertNull($user->fullname());
        $this->assertNull($user->email());
        $this->assertSame('en', $user->language());
        $this->assertSame('user', $user->get('role'));
        $this->assertSame('auto', $user->get('colorScheme'));
        $this->assertNull($user->get('lastAccess'));
        $this->assertSame(User::MINIMUM_PASSWORD_LENGTH, 8);
    }

    public function testGivenDataOverridesTheDefaults(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'language' => 'it', 'fullname' => 'Alice Smith', 'email' => 'alice@example.test']);

        $this->assertSame('it', $user->language());
        $this->assertSame('Alice Smith', $user->fullname());
        $this->assertSame('alice@example.test', $user->email());
    }

    public function testDottedDataKeysAreExpanded(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'preferences.theme' => 'dark']);

        $this->assertSame('dark', $user->get('preferences.theme'));
    }

    public function testPasswordHashIsNeverExposed(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('secret-password')]);

        $this->assertArrayNotHasKey('hash', $user->toArray());
        $this->assertArrayNotHasKey('hash', $user->__debugInfo());
        $this->assertStringNotContainsString('$2y$', (string) json_encode($user->toArray()));
        $this->assertStringNotContainsString('$2y$', print_r($user, true));
    }

    public function testPasswordHashCannotBeRead(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('secret-password')]);

        $this->expectException(LogicException::class);
        $user->get('hash');
    }

    public function testPasswordsAreHashedWhenSet(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $user->set('password', 'a-new-password');

        $this->assertTrue($user->verifyPassword('a-new-password'));
        $this->assertFalse($user->verifyPassword('another-password'));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testPasswordsAreSavedOnlyAsHashes(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $user->set('password', 'a-new-password');
        $user->save();

        $content = FileSystem::read($this->accountsPath . '/alice.yaml');

        $this->assertStringNotContainsString('a-new-password', $content);
        $this->assertStringContainsString('hash:', $content);
    }

    public function testShortPasswordsAreRejected(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('at least 8 characters');
        $user->set('password', 'short');
    }

    public function testPasswordsWithTheMinimumLengthAreAccepted(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $user->set('password', '12345678');

        $this->assertTrue($user->verifyPassword('12345678'));
    }

    public function testRejectedPasswordsDoNotReplaceTheCurrentOne(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('original-password')]);

        try {
            $user->set('password', 'short');
        } catch (InvalidValueException) {
        }

        $this->assertTrue($user->verifyPassword('original-password'));
    }

    public function testUsersWithoutAPasswordCannotVerifyOne(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $this->expectException(UnexpectedValueException::class);
        $user->verifyPassword('anything');
    }

    public function testUsersWithoutAPasswordNeverAuthenticateWithAnEmptyPassword(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        try {
            $result = $user->verifyPassword('');
        } catch (UnexpectedValueException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    #[DataProvider('validUsernameProvider')]
    public function testUsernamesCanBeSetOnNewUsers(string $username): void
    {
        $user = $this->makeUser([]);

        $user->set('username', $username);

        $this->assertSame($username, $user->username());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validUsernameProvider(): iterable
    {
        yield 'letters' => ['alice'];
        yield 'with digits' => ['alice42'];
        yield 'with separators' => ['alice.smith_jones-1'];
    }

    public function testUsernamesCannotBeChanged(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot change username of an existing user');
        $user->set('username', 'bob');
    }

    #[DataProvider('unsafeUsernameProvider')]
    public function testUsernamesCannotBeUsedToEscapeTheAccountsDirectory(string $username): void
    {
        $user = $this->makeUser([]);

        try {
            $user->set('username', $username);
            $user->save();
        } catch (\Throwable) {
            // Rejecting the username is the expected outcome
        }

        // The accounts directory is nested inside the temporary directory so that escaping it can be detected safely
        $outside = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(TESTS_TMP_PATH, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && !str_starts_with($file->getPathname(), $this->accountsPath . '/')) {
                $outside[] = $file->getPathname();
            }
        }

        $this->assertSame([], $outside, 'No account file may be written outside the accounts directory');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeUsernameProvider(): iterable
    {
        yield 'parent directory' => ['../escaped'];
        yield 'deep traversal' => ['../../../escaped'];
        yield 'absolute path' => ['/tmp/escaped'];
        yield 'subdirectory' => ['sub/directory'];
        yield 'backslash' => ['..\escaped'];
    }

    public function testUsernamesMustNotContainPathSeparators(): void
    {
        $user = $this->makeUser([]);

        $this->expectException(InvalidValueException::class);
        $user->set('username', '../escaped');
    }

    public function testUsersCannotBeSavedWithoutAUsername(): void
    {
        $this->expectException(LogicException::class);
        $this->makeUser([])->save();
    }

    public function testUsersCannotBeDeletedWithoutAUsername(): void
    {
        $this->expectException(LogicException::class);
        $this->makeUser([])->delete();
    }

    public function testSavedUsersAreWrittenToTheAccountsDirectory(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'fullname' => 'Alice Smith']);

        $user->save();

        $this->assertFileExists($this->accountsPath . '/alice.yaml');
        $saved = Yaml::parseFile($this->accountsPath . '/alice.yaml');
        $this->assertSame('alice', $saved['username']);
        $this->assertSame('Alice Smith', $saved['fullname']);
    }

    public function testDeletedUsersAreRemovedFromTheAccountsDirectory(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $user->save();
        $other = $this->makeUser(['username' => 'bob']);
        $other->save();

        $user->delete();

        $this->assertFileDoesNotExist($this->accountsPath . '/alice.yaml');
        $this->assertFileExists($this->accountsPath . '/bob.yaml');
    }

    #[DataProvider('emailProvider')]
    public function testEmailsAreValidated(string $email, bool $valid): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        if (!$valid) {
            $this->expectException(InvalidValueException::class);
        }

        $user->set('email', $email);

        $this->assertSame($email, $user->data()['email']);
    }

    public function testGettersReflectTheEmailChangedThroughTheSetter(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'email' => 'old@example.test']);

        $user->set('email', 'new@example.test');

        $this->assertSame('new@example.test', $user->email());
        $this->assertSame('new@example.test', $user->get('email'));
        $this->assertSame('new@example.test', $user->toArray()['email']);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function emailProvider(): iterable
    {
        yield 'simple' => ['alice@example.test', true];
        yield 'with plus' => ['alice+tag@example.test', true];
        yield 'subdomain' => ['alice@mail.example.test', true];
        yield 'missing at sign' => ['alice.example.test', false];
        yield 'missing domain' => ['alice@', false];
        yield 'missing local part' => ['@example.test', false];
        yield 'whitespace' => ['alice smith@example.test', false];
        yield 'two at signs' => ['alice@@example.test', false];
        yield 'empty' => ['', false];
        yield 'line break' => ["alice@example.test\nBcc: other@example.test", false];
    }

    public function testEmailsMustBeUnique(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'alice', 'email' => 'alice@example.test'], $users);
        $bob = $this->makeUser(['username' => 'bob', 'email' => 'bob@example.test'], $users);

        try {
            $bob->set('email', 'alice@example.test');
            $this->fail('The address is already used.');
        } catch (TranslatedException $exception) {
            $this->assertSame('panel.users.user.cannotChangeEmail.alreadyUsed', $exception->getLanguageString());
        }

        $this->assertSame('bob@example.test', $bob->data()['email']);
    }

    public function testEmailsDifferingOnlyInCaseAreTheSameAddress(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'alice', 'email' => 'alice@example.test'], $users);
        $bob = $this->makeUser(['username' => 'bob', 'email' => 'bob@example.test'], $users);

        $this->expectException(TranslatedException::class);
        $bob->set('email', 'ALICE@Example.test');
    }

    public function testUsersCanKeepTheirOwnEmail(): void
    {
        $alice = $this->makeUser(['username' => 'alice', 'email' => 'alice@example.test']);

        $alice->set('email', 'alice@example.test');

        $this->assertSame('alice@example.test', $alice->data()['email']);
    }

    public function testRolesMustExist(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $user->set('role', 'editor');
        $this->assertSame('editor', $user->role()->id());

        $this->expectException(InvalidValueException::class);
        $user->set('role', 'superuser');
    }

    public function testRejectedRolesDoNotChangeTheCurrentOne(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'role' => 'editor']);

        try {
            $user->set('role', 'superuser');
        } catch (InvalidValueException) {
        }

        $this->assertSame('editor', $user->role()->id());
    }

    public function testRoleIsResolvedFromTheUsersCollection(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'role' => 'admin']);

        $this->assertInstanceOf(Role::class, $user->role());
        $this->assertSame('admin', $user->role()->id());
        $this->assertInstanceOf(Permissions::class, $user->permissions());
    }

    public function testUsersWithAnUnknownRoleReportTheProblem(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'role' => 'ghost']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('invalid role assigned: "ghost"');
        $user->role();
    }

    public function testUsersWithoutARoleReportTheProblem(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'role' => null]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('has no role assigned');
        $user->role();
    }

    public function testPermissionsComeFromTheRole(): void
    {
        $admin = $this->makeUser(['username' => 'alice', 'role' => 'admin']);
        $editor = $this->makeUser(['username' => 'bob', 'role' => 'editor']);

        $this->assertTrue($admin->permissions()->has('panel.users.create'));
        $this->assertFalse($editor->permissions()->has('panel.users.create'));
        $this->assertTrue($editor->permissions()->has('panel.pages.create'));
    }

    public function testAdministratorsAreRecognizedByTheirRole(): void
    {
        $this->assertTrue($this->makeUser(['username' => 'alice', 'role' => 'admin'])->isAdmin());
        $this->assertFalse($this->makeUser(['username' => 'bob', 'role' => 'editor'])->isAdmin());
    }

    public function testColorSchemeIsResolved(): void
    {
        $this->assertSame(ColorScheme::Auto, $this->makeUser(['username' => 'alice'])->colorScheme());
        $this->assertSame(ColorScheme::Dark, $this->makeUser(['username' => 'bob', 'colorScheme' => 'dark'])->colorScheme());
    }

    public function testInvalidColorSchemesAreReported(): void
    {
        $user = $this->makeUser(['username' => 'alice', 'colorScheme' => 'sepia']);

        $this->expectException(ValueError::class);
        $user->colorScheme();
    }

    #[DataProvider('permissionMatrixProvider')]
    public function testUserManagementRules(string $actorRole, string $actorName, string $targetName, bool $canDelete, bool $canChangeOptions, bool $canChangePassword, bool $canChangeRole): void
    {
        $users = $this->users();
        $actor = $this->makeUser(['username' => $actorName, 'role' => $actorRole], $users);
        $target = $actorName === $targetName ? $actor : $this->makeUser(['username' => $targetName, 'role' => 'editor'], $users);

        $this->assertSame($canDelete, $actor->canDeleteUser($target), 'canDeleteUser');
        $this->assertSame($canChangeOptions, $actor->canChangeOptionsOf($target), 'canChangeOptionsOf');
        $this->assertSame($canChangePassword, $actor->canChangePasswordOf($target), 'canChangePasswordOf');
        $this->assertSame($canChangeRole, $actor->canChangeRoleOf($target), 'canChangeRoleOf');
    }

    /**
     * @return iterable<string, array{string, string, string, bool, bool, bool, bool}>
     */
    public static function permissionMatrixProvider(): iterable
    {
        yield 'administrator on another user' => ['admin', 'alice', 'bob', true, true, false, true];
        yield 'administrator on themselves' => ['admin', 'alice', 'alice', false, true, true, false];
        yield 'editor on another user' => ['editor', 'bob', 'carol', false, false, false, false];
        yield 'editor on themselves' => ['editor', 'bob', 'bob', false, true, true, false];
    }

    public function testNoUserCanChangeThePasswordOfAnotherUser(): void
    {
        $users = $this->users();
        $admin = $this->makeUser(['username' => 'alice', 'role' => 'admin'], $users);
        $other = $this->makeUser(['username' => 'bob', 'role' => 'editor'], $users);

        $this->assertFalse($admin->canChangePasswordOf($other));
    }

    public function testLoggedInStateComesFromTheAuthenticator(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $other = $this->makeUser(['username' => 'bob']);
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn($user);
        $user->authenticator = $authenticator;
        $other->authenticator = $authenticator;

        $this->assertTrue($user->isLoggedIn());
        $this->assertFalse($other->isLoggedIn());
    }

    public function testNobodyIsLoggedInWithoutAnAuthenticatedUser(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn(null);
        $user->authenticator = $authenticator;

        $this->assertFalse($user->isLoggedIn());
    }

    public function testOnlyLoggedInUsersCanLogOut(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $other = $this->makeUser(['username' => 'bob']);
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->method('getUser')->willReturn($other);
        $authenticator->expects($this->never())->method('logout');
        $user->authenticator = $authenticator;

        $this->expectException(UserNotLoggedException::class);
        $user->logout();
    }

    public function testLoggedInUsersLogOutThroughTheAuthenticator(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->method('getUser')->willReturn($user);
        $authenticator->expects($this->once())->method('logout');
        $user->authenticator = $authenticator;

        $user->logout();
    }

    public function testAuthenticationGoesThroughTheAuthenticator(): void
    {
        $user = $this->makeUser(['username' => 'alice']);
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->expects($this->once())->method('login')->with('alice', 'the-password')->willReturn($user);
        $user->authenticator = $authenticator;

        $user->authenticate('the-password');
    }

    public function testUsersWithoutImagesHaveNoImage(): void
    {
        $this->assertNull($this->makeUser(['username' => 'alice'])->image());
        $this->assertNull($this->makeUser(['username' => 'bob', 'image' => 'missing.png'])->image());
    }

    public function testImagesAreLoadedFromTheImagesDirectory(): void
    {
        $this->createImage('avatar.png');

        $image = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png'])->image();

        $this->assertNotNull($image);
        $this->assertSame($this->imagesPath . '/avatar.png', $image->path());
    }

    public function testNonImageFilesCannotBeUsedAsImages(): void
    {
        FileSystem::write($this->imagesPath . '/notes.txt', 'text');
        $user = $this->makeUser(['username' => 'alice', 'image' => 'notes.txt']);

        $this->expectException(UserImageNotFoundException::class);
        $user->image();
    }

    #[DataProvider('traversingImageProvider')]
    public function testImageNamesCannotPointOutsideTheImagesDirectory(string $name): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        try {
            $user->set('image', $name);
        } catch (\Throwable) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertNull($user->image(), 'A traversing image name must not resolve to a file');
        $this->assertStringNotContainsString('..', (string) $user->get('image'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function traversingImageProvider(): iterable
    {
        yield 'parent directory' => ['../outside.png'];
        yield 'deep traversal' => ['../../../outside.png'];
        yield 'subdirectory' => ['nested/avatar.png'];
    }

    public function testDeletingAnImageNeverRemovesFilesOutsideTheImagesDirectory(): void
    {
        $outside = TESTS_TMP_PATH . '/users/outside.png';
        $this->createImage('avatar.png');
        copy($this->imagesPath . '/avatar.png', $outside);
        $user = $this->makeUser(['username' => 'alice', 'image' => '../outside.png']);

        try {
            $user->delete();
        } catch (\Throwable) {
        }

        $this->assertFileExists($outside);
    }

    public function testImageFilesInOtherDirectoriesWithTheSamePrefixAreRejected(): void
    {
        $siblingPath = $this->imagesPath . '-other';
        FileSystem::createDirectory($siblingPath);
        $this->createImage('avatar.png', $siblingPath);
        $image = App::instance()->getService(FileFactory::class)->make($siblingPath . '/avatar.png');
        $user = $this->makeUser(['username' => 'alice']);

        $this->expectException(LogicException::class);
        $user->set('image', $image);
    }

    public function testImagesFromTheImagesDirectoryAreStoredByName(): void
    {
        $this->createImage('avatar.png');
        $image = App::instance()->getService(FileFactory::class)->make($this->imagesPath . '/avatar.png');
        $user = $this->makeUser(['username' => 'alice']);

        $user->set('image', $image);

        $this->assertSame('avatar.png', $user->data()['image']);
    }

    public function testReplacingTheImageDeletesThePreviousFile(): void
    {
        $this->createImage('old.png');
        $this->createImage('new.png');
        $user = $this->makeUser(['username' => 'alice', 'image' => 'old.png']);

        $user->set('image', 'new.png');

        $this->assertFileDoesNotExist($this->imagesPath . '/old.png');
        $this->assertFileExists($this->imagesPath . '/new.png');
    }

    public function testSettingTheSameImageKeepsTheFile(): void
    {
        $this->createImage('avatar.png');
        $user = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);

        $user->set('image', 'avatar.png');

        $this->assertFileExists($this->imagesPath . '/avatar.png');
    }

    public function testRemovingTheImageDeletesTheFile(): void
    {
        $this->createImage('avatar.png');
        $user = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);

        $user->set('image', null);

        $this->assertFileDoesNotExist($this->imagesPath . '/avatar.png');
        $this->assertNull($user->data()['image']);
    }

    public function testDeleteImageClearsAndSavesTheUser(): void
    {
        $this->createImage('avatar.png');
        $user = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);

        $user->deleteImage();

        $this->assertFileDoesNotExist($this->imagesPath . '/avatar.png');
        $this->assertNull(Yaml::parseFile($this->accountsPath . '/alice.yaml')['image'] ?? null);
    }

    public function testDeletingAUserDeletesTheirImage(): void
    {
        $this->createImage('avatar.png');
        $user = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);
        $user->save();

        $user->delete();

        $this->assertFileDoesNotExist($this->imagesPath . '/avatar.png');
        $this->assertFileDoesNotExist($this->accountsPath . '/alice.yaml');
    }

    public function testDeletingTheImageOfAUserWithoutOneIsReported(): void
    {
        $user = $this->makeUser(['username' => 'alice']);

        $this->expectException(TranslatedException::class);
        $user->deleteImage();
    }

    private function createImage(string $name, ?string $directory = null): void
    {
        $image = imagecreatetruecolor(4, 4);
        imagepng($image, ($directory ?? $this->imagesPath) . '/' . $name);
    }
}
