<?php

namespace Formwork\Tests\Unit\View;

use Error;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\View\Fixtures\RendererTarget;
use Formwork\View\Renderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Renderer::class)]
final class RendererTest extends TestCase
{
    private const string DIR = __DIR__ . '/Fixtures/renderer';

    public function testScriptReceivesTheVariablesAndIsBoundToTheInstance(): void
    {
        $result = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'World'], new RendererTarget());

        $this->assertSame(['name' => 'World', 'self' => RendererTarget::class], $result);
    }

    public function testScriptWithoutAReturnStatementReturnsOne(): void
    {
        $this->assertSame(1, Renderer::load(self::DIR . '/no-return.php', [], new RendererTarget()));
    }

    public function testPrivateMembersAreNotAccessibleWithoutAContext(): void
    {
        $this->expectException(Error::class);

        Renderer::load(self::DIR . '/private-access.php', [], new RendererTarget());
    }

    public function testPrivateMembersAreAccessibleWithTheClassContext(): void
    {
        $this->assertSame('private-value', Renderer::load(self::DIR . '/private-access.php', [], new RendererTarget(), RendererTarget::class));
    }

    public function testStaticContextBindsTheScopeToTheInstanceClass(): void
    {
        $this->assertSame(RendererTarget::class . '|' . RendererTarget::class, Renderer::load(self::DIR . '/static-scope.php', [], new RendererTarget(), RendererTarget::class));
    }

    public function testVariablesAreIsolatedBetweenCalls(): void
    {
        $target = new RendererTarget();

        Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'First'], $target);
        $second = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'Second'], $target);

        $this->assertSame('Second', $second['name']);
    }

    public function testVariablesCannotReplaceTheScriptThatIsBeingIncluded(): void
    {
        $result = Renderer::load(self::DIR . '/expected.php', ['_filename' => self::DIR . '/hijacked.php'], new RendererTarget());

        $this->assertSame('expected file', $result);
    }

    public function testVariablesCannotReplaceTheVariablesArray(): void
    {
        $result = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'Kept', '_vars' => ['name' => 'Replaced']], new RendererTarget());

        $this->assertSame('Kept', $result['name']);
    }

    public function testAVariableNamedThisIsRejectedOrIgnoredButNeverRebindsTheInstance(): void
    {
        try {
            $result = Renderer::load(self::DIR . '/returns-vars.php', ['name' => 'x', 'this' => 'other'], new RendererTarget());
        } catch (Error) {
            $this->addToAssertionCount(1);
            return;
        }

        $this->assertSame(RendererTarget::class, $result['self']);
    }
}
