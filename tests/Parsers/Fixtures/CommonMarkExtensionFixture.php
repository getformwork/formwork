<?php

namespace Formwork\Tests\Parsers\Fixtures;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class CommonMarkExtensionFixture implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void {}
}
