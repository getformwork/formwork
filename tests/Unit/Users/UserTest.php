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
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
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
use Throwable;
use UnexpectedValueException;
use ValueError;

#[CoversClass(User::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class UserTest extends TestCase
{
    use BuildsUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
    }

    public function testDefaultsAreApplied(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $this->assertSame('alice', $testableUser->username());
        $this->assertNull($testableUser->fullname());
        $this->assertNull($testableUser->email());
        $this->assertSame('en', $testableUser->language());
        $this->assertSame('user', $testableUser->get('role'));
        $this->assertSame('auto', $testableUser->get('colorScheme'));
        $this->assertNull($testableUser->get('lastAccess'));
        $this->assertSame(User::MINIMUM_PASSWORD_LENGTH, 8);
    }

    public function testGivenDataOverridesTheDefaults(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'language' => 'it', 'fullname' => 'Alice Smith', 'email' => 'alice@example.test']);

        $this->assertSame('it', $testableUser->language());
        $this->assertSame('Alice Smith', $testableUser->fullname());
        $this->assertSame('alice@example.test', $testableUser->email());
    }

    public function testDottedDataKeysAreExpanded(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'preferences.theme' => 'dark']);

        $this->assertSame('dark', $testableUser->get('preferences.theme'));
    }

    public function testPasswordHashIsNeverExposed(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('secret-password')]);

        $this->assertArrayNotHasKey('hash', $testableUser->toArray());
        $this->assertArrayNotHasKey('hash', $testableUser->__debugInfo());
        $this->assertStringNotContainsString('$2y$', (string) json_encode($testableUser->toArray()));
        $this->assertStringNotContainsString('$2y$', print_r($testableUser, true));
    }

    public function testPasswordHashCannotBeRead(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('secret-password')]);

        $this->expectException(LogicException::class);
        $testableUser->get('hash');
    }

    public function testPasswordsAreHashedWhenSet(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $testableUser->set('password', 'a-new-password');

        $this->assertTrue($testableUser->verifyPassword('a-new-password'));
        $this->assertFalse($testableUser->verifyPassword('another-password'));
        $this->assertArrayNotHasKey('password', $testableUser->toArray());
    }

    public function testPasswordsAreSavedOnlyAsHashes(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $testableUser->set('password', 'a-new-password');
        $testableUser->save();

        $content = FileSystem::read($this->accountsPath . '/alice.yaml');

        $this->assertStringNotContainsString('a-new-password', $content);
        $this->assertStringContainsString('hash:', $content);
    }

    public function testShortPasswordsAreRejected(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('at least 8 characters');
        $testableUser->set('password', 'short');
    }

    public function testTooLongPasswordsAreRejectedAsInvalidValues(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('original-password')]);

        $invalidValueException = $this->captureException(InvalidValueException::class, static fn() => $testableUser->set('password', str_repeat('a', 73)));
        $this->assertSame('Password exceeds the maximum supported length', $invalidValueException->getMessage());

        $this->assertTrue($testableUser->verifyPassword('original-password'));
    }

    public function testPasswordsWithTheMinimumLengthAreAccepted(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $testableUser->set('password', '12345678');

        $this->assertTrue($testableUser->verifyPassword('12345678'));
    }

    public function testRejectedPasswordsDoNotReplaceTheCurrentOne(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'hash' => Password::hash('original-password')]);

        $this->captureException(InvalidValueException::class, static fn() => $testableUser->set('password', 'short'));

        $this->assertTrue($testableUser->verifyPassword('original-password'));
    }

    public function testUsersWithoutAPasswordCannotVerifyOne(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $this->expectException(UnexpectedValueException::class);
        $testableUser->verifyPassword('anything');
    }

    public function testUsersWithoutAPasswordNeverAuthenticateWithAnEmptyPassword(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        try {
            $result = $testableUser->verifyPassword('');
        } catch (UnexpectedValueException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    #[DataProvider('validUsernameProvider')]
    public function testUsernamesCanBeSetOnNewUsers(string $username): void
    {
        $testableUser = $this->makeUser([]);

        $testableUser->set('username', $username);

        $this->assertSame($username, $testableUser->username());
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
        $testableUser = $this->makeUser(['username' => 'alice']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot change username of an existing user');
        $testableUser->set('username', 'bob');
    }

    #[DataProvider('unsafeUsernameProvider')]
    public function testUsernamesCannotBeUsedToEscapeTheAccountsDirectory(string $username): void
    {
        $testableUser = $this->makeUser([]);

        try {
            $testableUser->set('username', $username);
            $testableUser->save();
        } catch (Throwable) {
            // Rejecting the username is the expected outcome
        }

        // The accounts directory is nested inside the temporary directory so that escaping it can be detected safely
        $outside = [];
        foreach (FileSystem::listRecursive(TESTS_TMP_PATH, FileSystem::LIST_FILES) as $item) {
            $file = FileSystem::joinPaths(TESTS_TMP_PATH, $item);
            if (!str_starts_with($file, $this->accountsPath . '/')) {
                $outside[] = $file;
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
        $testableUser = $this->makeUser([]);

        $this->expectException(InvalidValueException::class);
        $testableUser->set('username', '../escaped');
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
        $testableUser = $this->makeUser(['username' => 'alice', 'fullname' => 'Alice Smith']);

        $testableUser->save();

        $this->assertFileExists($this->accountsPath . '/alice.yaml');
        $saved = Yaml::parseFile($this->accountsPath . '/alice.yaml');
        $this->assertSame('alice', $saved['username']);
        $this->assertSame('Alice Smith', $saved['fullname']);
    }

    public function testDeletedUsersAreRemovedFromTheAccountsDirectory(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $testableUser->save();
        $other = $this->makeUser(['username' => 'bob']);
        $other->save();

        $testableUser->delete();

        $this->assertFileDoesNotExist($this->accountsPath . '/alice.yaml');
        $this->assertFileExists($this->accountsPath . '/bob.yaml');
    }

    #[DataProvider('emailProvider')]
    public function testEmailsAreValidated(string $email, bool $valid): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        if (!$valid) {
            $this->expectException(InvalidValueException::class);
        }

        $testableUser->set('email', $email);

        $this->assertSame($email, $testableUser->data()['email']);
    }

    public function testGettersReflectTheEmailChangedThroughTheSetter(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'email' => 'old@example.test']);

        $testableUser->set('email', 'new@example.test');

        $this->assertSame('new@example.test', $testableUser->email());
        $this->assertSame('new@example.test', $testableUser->get('email'));
        $this->assertSame('new@example.test', $testableUser->toArray()['email']);
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
        $testableUser = $this->makeUser(['username' => 'bob', 'email' => 'bob@example.test'], $users);

        $translatedException = $this->captureException(TranslatedException::class, static fn() => $testableUser->set('email', 'alice@example.test'));
        $this->assertSame('panel.users.user.cannotChangeEmail.alreadyUsed', $translatedException->getLanguageString());

        $this->assertSame('bob@example.test', $testableUser->data()['email']);
    }

    public function testEmailsDifferingOnlyInCaseAreTheSameAddress(): void
    {
        $users = $this->users();
        $this->makeUser(['username' => 'alice', 'email' => 'alice@example.test'], $users);
        $testableUser = $this->makeUser(['username' => 'bob', 'email' => 'bob@example.test'], $users);

        $this->expectException(TranslatedException::class);
        $testableUser->set('email', 'ALICE@Example.test');
    }

    public function testUsersCanKeepTheirOwnEmail(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'email' => 'alice@example.test']);

        $testableUser->set('email', 'alice@example.test');

        $this->assertSame('alice@example.test', $testableUser->data()['email']);
    }

    public function testRolesMustExist(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $testableUser->set('role', 'editor');
        $this->assertSame('editor', $testableUser->role()->id());

        $this->expectException(InvalidValueException::class);
        $testableUser->set('role', 'superuser');
    }

    public function testRejectedRolesDoNotChangeTheCurrentOne(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'role' => 'editor']);

        $this->captureException(InvalidValueException::class, static fn() => $testableUser->set('role', 'superuser'));

        $this->assertSame('editor', $testableUser->role()->id());
    }

    public function testRoleIsResolvedFromTheUsersCollection(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'role' => 'admin']);

        $this->assertInstanceOf(Role::class, $testableUser->role());
        $this->assertSame('admin', $testableUser->role()->id());
        $this->assertInstanceOf(Permissions::class, $testableUser->permissions());
    }

    public function testUsersWithAnUnknownRoleReportTheProblem(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'role' => 'ghost']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('invalid role assigned: "ghost"');
        $testableUser->role();
    }

    public function testUsersWithoutARoleReportTheProblem(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'role' => null]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('has no role assigned');
        $testableUser->role();
    }

    public function testPermissionsComeFromTheRole(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice', 'role' => 'admin']);
        $editor = $this->makeUser(['username' => 'bob', 'role' => 'editor']);

        $this->assertTrue($testableUser->permissions()->has('panel.users.create'));
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
        $testableUser = $this->makeUser(['username' => 'alice', 'colorScheme' => 'sepia']);

        $this->expectException(ValueError::class);
        $testableUser->colorScheme();
    }

    #[DataProvider('permissionMatrixProvider')]
    public function testUserManagementRules(string $actorRole, string $actorName, string $targetName, bool $canDelete, bool $canChangeOptions, bool $canChangePassword, bool $canChangeRole): void
    {
        $users = $this->users();
        $testableUser = $this->makeUser(['username' => $actorName, 'role' => $actorRole], $users);
        $target = $actorName === $targetName ? $testableUser : $this->makeUser(['username' => $targetName, 'role' => 'editor'], $users);

        $this->assertSame($canDelete, $testableUser->canDeleteUser($target), 'canDeleteUser');
        $this->assertSame($canChangeOptions, $testableUser->canChangeOptionsOf($target), 'canChangeOptionsOf');
        $this->assertSame($canChangePassword, $testableUser->canChangePasswordOf($target), 'canChangePasswordOf');
        $this->assertSame($canChangeRole, $testableUser->canChangeRoleOf($target), 'canChangeRoleOf');
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
        $testableUser = $this->makeUser(['username' => 'alice', 'role' => 'admin'], $users);
        $other = $this->makeUser(['username' => 'bob', 'role' => 'editor'], $users);

        $this->assertFalse($testableUser->canChangePasswordOf($other));
    }

    public function testLoggedInStateComesFromTheAuthenticator(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $other = $this->makeUser(['username' => 'bob']);
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn($testableUser);
        $testableUser->authenticator = $authenticator;
        $other->authenticator = $authenticator;

        $this->assertTrue($testableUser->isLoggedIn());
        $this->assertFalse($other->isLoggedIn());
    }

    public function testNobodyIsLoggedInWithoutAnAuthenticatedUser(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('getUser')->willReturn(null);
        $testableUser->authenticator = $authenticator;

        $this->assertFalse($testableUser->isLoggedIn());
    }

    public function testOnlyLoggedInUsersCanLogOut(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $other = $this->makeUser(['username' => 'bob']);
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->method('getUser')->willReturn($other);
        $authenticator->expects($this->never())->method('logout');
        $testableUser->authenticator = $authenticator;

        $this->expectException(UserNotLoggedException::class);
        $testableUser->logout();
    }

    public function testLoggedInUsersLogOutThroughTheAuthenticator(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->method('getUser')->willReturn($testableUser);
        $authenticator->expects($this->once())->method('logout');
        $testableUser->authenticator = $authenticator;

        $testableUser->logout();
    }

    public function testAuthenticationGoesThroughTheAuthenticator(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->expects($this->once())->method('login')->with('alice', 'the-password')->willReturn($testableUser);
        $testableUser->authenticator = $authenticator;

        $testableUser->authenticate('the-password');
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
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => 'notes.txt']);

        $this->expectException(UserImageNotFoundException::class);
        $testableUser->image();
    }

    #[DataProvider('traversingImageProvider')]
    public function testImageNamesCannotPointOutsideTheImagesDirectory(string $name): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        try {
            $testableUser->set('image', $name);
        } catch (Throwable) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertNull($testableUser->image(), 'A traversing image name must not resolve to a file');
        $this->assertStringNotContainsString('..', (string) $testableUser->get('image'));
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
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => '../outside.png']);

        try {
            $testableUser->delete();
        } catch (Throwable) {
        }

        $this->assertFileExists($outside);
    }

    public function testImageFilesInOtherDirectoriesWithTheSamePrefixAreRejected(): void
    {
        $siblingPath = $this->imagesPath . '-other';
        FileSystem::createDirectory($siblingPath);
        $this->createImage('avatar.png', $siblingPath);
        $file = App::instance()->getService(FileFactory::class)->make($siblingPath . '/avatar.png');
        $testableUser = $this->makeUser(['username' => 'alice']);

        $this->expectException(LogicException::class);
        $testableUser->set('image', $file);
    }

    public function testImagesFromTheImagesDirectoryAreStoredByName(): void
    {
        $this->createImage('avatar.png');
        $file = App::instance()->getService(FileFactory::class)->make($this->imagesPath . '/avatar.png');
        $testableUser = $this->makeUser(['username' => 'alice']);

        $testableUser->set('image', $file);

        $this->assertSame('avatar.png', $testableUser->data()['image']);
    }

    public function testReplacingTheImageDeletesThePreviousFile(): void
    {
        $this->createImage('old.png');
        $this->createImage('new.png');
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => 'old.png']);

        $testableUser->set('image', 'new.png');

        $this->assertFileDoesNotExist($this->imagesPath . '/old.png');
        $this->assertFileExists($this->imagesPath . '/new.png');
    }

    public function testSettingTheSameImageKeepsTheFile(): void
    {
        $this->createImage('avatar.png');
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);

        $testableUser->set('image', 'avatar.png');

        $this->assertFileExists($this->imagesPath . '/avatar.png');
    }

    public function testRemovingTheImageDeletesTheFile(): void
    {
        $this->createImage('avatar.png');
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);

        $testableUser->set('image', null);

        $this->assertFileDoesNotExist($this->imagesPath . '/avatar.png');
        $this->assertNull($testableUser->data()['image']);
    }

    public function testDeleteImageClearsAndSavesTheUser(): void
    {
        $this->createImage('avatar.png');
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);

        $testableUser->deleteImage();

        $this->assertFileDoesNotExist($this->imagesPath . '/avatar.png');
        $this->assertNull(Yaml::parseFile($this->accountsPath . '/alice.yaml')['image'] ?? null);
    }

    public function testDeletingAUserDeletesTheirImage(): void
    {
        $this->createImage('avatar.png');
        $testableUser = $this->makeUser(['username' => 'alice', 'image' => 'avatar.png']);
        $testableUser->save();

        $testableUser->delete();

        $this->assertFileDoesNotExist($this->imagesPath . '/avatar.png');
        $this->assertFileDoesNotExist($this->accountsPath . '/alice.yaml');
    }

    public function testDeletingTheImageOfAUserWithoutOneIsReported(): void
    {
        $testableUser = $this->makeUser(['username' => 'alice']);

        $this->expectException(TranslatedException::class);
        $testableUser->deleteImage();
    }

    private function createImage(string $name, ?string $directory = null): void
    {
        $image = imagecreatetruecolor(4, 4);
        imagepng($image, ($directory ?? $this->imagesPath) . '/' . $name);
    }
}
