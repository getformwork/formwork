<?php

namespace Formwork\Tests\Unit\Security;

use Formwork\Http\Request;
use Formwork\Security\CsrfToken;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Authentication\Fixtures\InMemorySession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(CsrfToken::class)]
final class CsrfTokenTest extends TestCase
{
    private InMemorySession $session;

    private CsrfToken $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new InMemorySession();
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']);
        (new ReflectionProperty(Request::class, 'session'))->setValue($request, $this->session);
        $this->csrfToken = new CsrfToken($request);
    }

    public function testTokensDoNotExistUntilGenerated(): void
    {
        $this->assertFalse($this->csrfToken->has('panel'));
        $this->assertNull($this->csrfToken->get('panel'));
    }

    public function testGeneratedTokensAreStoredInTheSession(): void
    {
        $token = $this->csrfToken->generate('panel');

        $this->assertTrue($this->csrfToken->has('panel'));
        $this->assertSame($token, $this->csrfToken->get('panel'));
    }

    public function testTokensAreStrongRandomBase64Strings(): void
    {
        $token = $this->csrfToken->generate('panel');

        $this->assertSame(48, strlen($token));
        $this->assertSame(36, strlen((string) base64_decode($token, true)));
    }

    public function testEveryGenerationProducesADifferentToken(): void
    {
        $tokens = [];
        for ($i = 0; $i < 20; $i++) {
            $tokens[] = $this->csrfToken->generate('panel');
        }

        $this->assertCount(20, array_unique($tokens));
    }

    public function testGeneratingAgainReplacesThePreviousToken(): void
    {
        $first = $this->csrfToken->generate('panel');
        $second = $this->csrfToken->generate('panel');

        $this->assertNotSame($first, $second);
        $this->assertSame($second, $this->csrfToken->get('panel'));
        $this->assertFalse($this->csrfToken->validate('panel', $first));
        $this->assertTrue($this->csrfToken->validate('panel', $second));
    }

    public function testTokensAreIndependentByName(): void
    {
        $panel = $this->csrfToken->generate('panel');
        $form = $this->csrfToken->generate('form');

        $this->assertNotSame($panel, $form);
        $this->assertTrue($this->csrfToken->validate('panel', $panel));
        $this->assertFalse($this->csrfToken->validate('panel', $form));
        $this->assertFalse($this->csrfToken->validate('form', $panel));
    }

    public function testGetCanGenerateMissingTokens(): void
    {
        $token = $this->csrfToken->get('panel', autoGenerate: true);

        $this->assertNotNull($token);
        $this->assertSame($token, $this->csrfToken->get('panel'));
    }

    public function testGetDoesNotRegenerateExistingTokens(): void
    {
        $token = $this->csrfToken->generate('panel');

        $this->assertSame($token, $this->csrfToken->get('panel', autoGenerate: true));
    }

    public function testValidTokensAreAccepted(): void
    {
        $token = $this->csrfToken->generate('panel');

        $this->assertTrue($this->csrfToken->validate('panel', $token));
    }

    public function testValidTokensCanBeUsedMoreThanOnce(): void
    {
        $token = $this->csrfToken->generate('panel');

        $this->assertTrue($this->csrfToken->validate('panel', $token));
        $this->assertTrue($this->csrfToken->validate('panel', $token));
    }

    #[DataProvider('invalidTokenProvider')]
    public function testInvalidTokensAreRejected(string $token): void
    {
        $this->csrfToken->generate('panel');

        $this->assertFalse($this->csrfToken->validate('panel', $token));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTokenProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'random string' => ['not the token'];
        yield 'zero' => ['0'];
        yield 'whitespace' => [' '];
        yield 'null byte' => ["\0"];
    }

    public function testTokensWithDifferentCaseOrWhitespaceAreRejected(): void
    {
        $token = $this->csrfToken->generate('panel');

        $this->assertFalse($this->csrfToken->validate('panel', strtolower($token) === $token ? strtoupper($token) : strtolower($token)));
        $this->assertFalse($this->csrfToken->validate('panel', $token . ' '));
        $this->assertFalse($this->csrfToken->validate('panel', ' ' . $token));
        $this->assertFalse($this->csrfToken->validate('panel', substr($token, 0, -1)));
    }

    public function testTokensAreRejectedWhenNoneWasGenerated(): void
    {
        $this->assertFalse($this->csrfToken->validate('panel', ''));
        $this->assertFalse($this->csrfToken->validate('panel', 'anything'));
    }

    public function testEmptyTokensAreNeverValid(): void
    {
        $this->assertFalse($this->csrfToken->validate('panel', ''));

        $this->session->set('_formwork_csrf_tokens', ['panel' => '']);

        $this->assertFalse($this->csrfToken->validate('panel', ''));
    }

    public function testTokensCanBeDestroyed(): void
    {
        $token = $this->csrfToken->generate('panel');
        $other = $this->csrfToken->generate('form');

        $this->csrfToken->destroy('panel');

        $this->assertFalse($this->csrfToken->has('panel'));
        $this->assertFalse($this->csrfToken->validate('panel', $token));
        $this->assertTrue($this->csrfToken->validate('form', $other));
    }

    public function testDestroyingAMissingTokenIsHarmless(): void
    {
        $this->csrfToken->destroy('missing');

        $this->assertFalse($this->csrfToken->has('missing'));
    }

    public function testTokensAreNamespacedInTheSession(): void
    {
        $this->csrfToken->generate('panel');

        $this->assertArrayHasKey('_formwork_csrf_tokens.panel', $this->session->values);
        $this->assertArrayNotHasKey('panel', $this->session->values);
    }

    public function testNamesWithDotsDoNotBreakOtherTokens(): void
    {
        $outer = $this->csrfToken->generate('outer');
        $inner = $this->csrfToken->generate('outer.inner');

        $this->assertTrue($this->csrfToken->validate('outer.inner', $inner));
        $this->assertTrue($this->csrfToken->validate('outer', $outer));
    }
}
