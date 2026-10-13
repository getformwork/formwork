<?php

namespace Formwork\Tests\Unit\Model;

use Attribute;
use Formwork\Data\Attributes\Getter;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionClass;

#[CoversClass(ReadonlyModelProperty::class)]
final class ReadonlyModelPropertyTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testLoadingTheAttributeTriggersADeprecationPointingToTheReplacement(): void
    {
        $messages = $this->captureDeprecations(static function (): void {
            class_exists(ReadonlyModelProperty::class);
        });

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('deprecated since Formwork 2.4.0', $messages[0]);
        $this->assertStringContainsString(Getter::class, $messages[0]);
    }

    public function testAttributeCanOnlyBeAppliedToProperties(): void
    {
        $flags = null;

        // The attribute class triggers a deprecation when it is loaded
        $this->captureDeprecations(function () use (&$flags): void {
            $flags = (new ReflectionClass(ReadonlyModelProperty::class))->getAttributes(Attribute::class)[0]->newInstance()->flags;
        });

        $this->assertSame(Attribute::TARGET_PROPERTY, $flags);
    }
}
