<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Tests\TestCase;
use Formwork\Users\Utils\Password;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Password::class)]
final class PasswordTest extends TestCase
{
    public function testClassCannotBeInstantiated(): void
    {
        $this->expectException(LogicException::class);
        new Password();
    }

    public function testHashesVerifyTheirPassword(): void
    {
        $hash = Password::hash('correct horse battery staple');

        $this->assertTrue(Password::verify('correct horse battery staple', $hash));
    }

    public function testWrongPasswordsAreRejected(): void
    {
        $hash = Password::hash('correct horse battery staple');

        $this->assertFalse(Password::verify('correct horse battery stapl', $hash));
        $this->assertFalse(Password::verify('Correct horse battery staple', $hash));
        $this->assertFalse(Password::verify('', $hash));
        $this->assertFalse(Password::verify('correct horse battery staple ', $hash));
    }

    public function testHashesAreSalted(): void
    {
        $this->assertNotSame(Password::hash('password'), Password::hash('password'));
    }

    public function testHashesDoNotContainThePassword(): void
    {
        $this->assertStringNotContainsString('hunter2hunter2', Password::hash('hunter2hunter2'));
    }

    public function testHashesUseAModernAlgorithm(): void
    {
        $info = password_get_info(Password::hash('password'));

        $this->assertContains($info['algoName'], ['bcrypt', 'argon2i', 'argon2id']);
        $this->assertFalse(password_needs_rehash(Password::hash('password'), PASSWORD_DEFAULT));
    }

    #[DataProvider('passwordProvider')]
    public function testUnusualPasswordsRoundTrip(string $password): void
    {
        $this->assertTrue(Password::verify($password, Password::hash($password)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function passwordProvider(): iterable
    {
        yield 'unicode' => ['pässwörd-日本語-🔐'];
        yield 'spaces only' => ['        '];
        yield 'quotes and backslashes' => ['"\'\`'];
        yield 'long password' => [str_repeat('a', 72)];
        yield 'password with a null byte' => ["pass\0word"];
    }

    public function testLongPasswordsAreNotSilentlyTruncated(): void
    {
        // bcrypt only uses the first 72 bytes: passwords sharing them must not be interchangeable
        $hash = Password::hash(str_repeat('a', 80) . 'one');

        $this->assertFalse(Password::verify(str_repeat('a', 80) . 'two', $hash));
    }

    #[DataProvider('invalidHashProvider')]
    public function testInvalidHashesNeverVerify(string $hash): void
    {
        $this->assertFalse(Password::verify('password', $hash));
        $this->assertFalse(Password::verify('', $hash));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHashProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'plain text' => ['password'];
        yield 'truncated hash' => ['$2y$10$abcdefghijklmnopqrstuv'];
        yield 'unknown algorithm' => ['$9$10$abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRS'];
    }
}
