<?php

namespace Formwork\Parsers\Extensions\CommonMark;

use Formwork\App;
use Formwork\Utils\Uri;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\Config\ConfigurationInterface;

class LinkBaseProcessor
{
    protected ConfigurationInterface $config;

    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    public function __invoke(DocumentParsedEvent $e): void
    {
        foreach ($e->getDocument()->iterator() as $node) {
            if (!($node instanceof Link || $node instanceof Image)) {
                continue;
            }

            $baseRoute = $this->config->get('formwork/baseRoute');

            $uri = $node->getUrl();

            // Process only if scheme is either null, 'http' or 'https'
            if (in_array(Uri::scheme($uri), [null, 'http', 'https'], true) && (empty(Uri::host($uri)) && $uri[0] !== '#')) {
                $relativeUri = Uri::resolveRelative($uri, $baseRoute);
                $uri = App::instance()->site()->uri($relativeUri, includeLanguage: false);
            }

            $node->setUrl($uri);
        }
    }
}
