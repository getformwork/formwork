<?php

namespace Formwork\Parsers\Extensions\CommonMark;

use Formwork\Cms\Site;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

class FormworkExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $configurationBuilder): void
    {
        $configurationBuilder->addSchema('formwork', Expect::structure([
            'site'             => Expect::type(Site::class),
            'imageAltProperty' => Expect::string('alt'),
            'baseRoute'        => Expect::string('/'),
        ]));
    }

    public function register(EnvironmentBuilderInterface $environmentBuilder): void
    {
        $environmentBuilder->addEventListener(DocumentParsedEvent::class, new ImageAltProcessor($environmentBuilder->getConfiguration()));
        $environmentBuilder->addEventListener(DocumentParsedEvent::class, new LinkBaseProcessor($environmentBuilder->getConfiguration()));
    }
}
