<?php

namespace Formwork\Parsers\Extensions\CommonMark;

use Formwork\Utils\Uri;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\Config\ConfigurationInterface;

class LinkBaseProcessor
{
    public function __construct(
        protected ConfigurationInterface $configuration,
    ) {}

    public function __invoke(DocumentParsedEvent $documentParsedEvent): void
    {
        foreach ($documentParsedEvent->getDocument()->iterator() as $node) {
            if (!$node instanceof Link && !$node instanceof Image) {
                continue;
            }

            $baseRoute = $this->configuration->get('formwork/baseRoute');

            $site = $this->configuration->get('formwork/site');

            $uri = $node->getUrl();

            // Process only if scheme is either null, 'http' or 'https'
            if (in_array(Uri::scheme($uri), [null, 'http', 'https'], true) && ((Uri::host($uri) === null || Uri::host($uri) === '') && $uri[0] !== '#')) {
                $relativeUri = Uri::resolveRelative($uri, $baseRoute);
                $uri = $site->uri($relativeUri, includeLanguage: false);
            }

            $node->setUrl($uri);
        }
    }
}
