<?php

namespace Formwork\Tests\Unit\View;

use Error;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\View\Fixtures\RendererTargetFixture;
use Formwork\View\Renderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Renderer::class)]
final class RendererTest extends TestCase
{
    private const string DIR = __DIR__ . '/Fixtures/renderer';

    public function testScriptReceivesTheVariablesAndIsBoundToTheInstance(): void
    {
        $result = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'World'], new RendererTargetFixture());

        $this->assertSame(['name' => 'World', 'self' => RendererTargetFixture::class], $result);
    }

    public function testScriptWithoutAReturnStatementReturnsOne(): void
    {
        $this->assertSame(1, Renderer::load(self::DIR . '/no-return.php', [], new RendererTargetFixture()));
    }

    public function testPrivateMembersAreNotAccessibleWithoutAContext(): void
    {
        $this->expectException(Error::class);

        Renderer::load(self::DIR . '/private-access.php', [], new RendererTargetFixture());
    }

    public function testPrivateMembersAreAccessibleWithTheClassContext(): void
    {
        $this->assertSame('private-value', Renderer::load(self::DIR . '/private-access.php', [], new RendererTargetFixture(), RendererTargetFixture::class));
    }

    public function testStaticContextBindsTheScopeToTheInstanceClass(): void
    {
        $this->assertSame(RendererTargetFixture::class . '|' . RendererTargetFixture::class, Renderer::load(self::DIR . '/static-scope.php', [], new RendererTargetFixture(), RendererTargetFixture::class));
    }

    public function testVariablesAreIsolatedBetweenCalls(): void
    {
        $rendererTarget = new RendererTargetFixture();

        Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'First'], $rendererTarget);
        $second = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'Second'], $rendererTarget);

        $this->assertSame('Second', $second['name']);
    }

    public function testVariablesCannotReplaceTheScriptThatIsBeingIncluded(): void
    {
        $result = Renderer::load(self::DIR . '/expected.php', ['_filename' => self::DIR . '/hijacked.php'], new RendererTargetFixture());

        $this->assertSame('expected file', $result);
    }

    public function testVariablesCannotReplaceTheVariablesArray(): void
    {
        $result = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'Kept', '_vars' => ['name' => 'Replaced']], new RendererTargetFixture());

        $this->assertSame('Kept', $result['name']);
    }

    public function testAVariableNamedThisIsRejectedOrIgnoredButNeverRebindsTheInstance(): void
    {
        try {
            $result = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'x', 'this' => 'other'], new RendererTargetFixture());
        } catch (Error) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $this->assertSame(RendererTargetFixture::class, $result['self']);
    }
}
