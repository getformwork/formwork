<?php

namespace Formwork\Tests\Unit\Model;

use Formwork\Data\Attributes\Getter;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

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
}
