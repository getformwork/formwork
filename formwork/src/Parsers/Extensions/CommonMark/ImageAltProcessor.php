<?php

namespace Formwork\Parsers\Extensions\CommonMark;

use Formwork\App;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\Config\ConfigurationInterface;

class ImageAltProcessor
{
    public function __construct(protected ConfigurationInterface $configuration)
    {
    }

    public function __invoke(DocumentParsedEvent $documentParsedEvent): void
    {
        foreach ($documentParsedEvent->getDocument()->iterator() as $node) {
            if (!$node instanceof Image) {
                continue;
            }

            $baseRoute = $this->configuration->get('formwork/baseRoute');

            $site = App::instance()->site();

            $uri = $node->getUrl();

            $key = $this->configuration->get('formwork/imageAltProperty');

            $alt = $site->findPage($baseRoute)?->files()->get($uri)?->get($key);

            if ($alt !== null) {
                $node->data->set('attributes/alt', $alt);
            }
        }
    }
}
