<?php

namespace Formwork\Services\Loaders;

use Formwork\Config;
use Formwork\Http\Request;
use Formwork\Languages\Languages;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

class LanguagesServiceLoader implements ServiceLoaderInterface
{
    public function __construct(protected Config $config, protected Request $request)
    {

    }

    public function load(Container $container): Languages
    {
        /**
         * @var array<string> $available
         */
        $available = (array) $this->config->get('system.languages.available');

        if (preg_match('~^/(' . implode('|', $available) . ')/~i', $this->request->uri(), $matches)) {
            $requested = $current = $matches[1];
        }

        if ($this->config->get('system.languages.httpPreferred')) {
            $languages = $this->request->languages();
            foreach (array_keys($languages) as $code) {
                if (in_array($code, $available, true)) {
                    $preferred = $code;
                    break;
                }
            }
        }

        return $container->build(Languages::class, ['options' => [
            'available' => $available,
            'default'   => $this->config->get('system.languages.default', $available[0] ?? null),
            'current'   => $current ?? null,
            'requested' => $requested ?? null,
            'preferred' => $preferred ?? null,
        ]]);
    }
}
