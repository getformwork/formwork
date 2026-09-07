<?php

namespace Formwork\Languages;

use Formwork\Http\Request;
use Formwork\Services\Container;

final class LanguagesFactory
{
    public function __construct(
        private Container $container,
        private Request $request,
    ) {}

    /**
     * Create a new Languages instance
     *
     * @param array{available: list<string>, httpPreferred: bool, default?: string} $config
     */
    public function make(array $config): Languages
    {
        ['available' => $available, 'httpPreferred' => $httpPreferred] = $config;

        if (preg_match('~^/(' . implode('|', $available) . ')/~i', $this->request->uri(), $matches)) {
            $requested = $current = $matches[1];
        }

        if ($httpPreferred) {
            $languages = $this->request->languages();
            foreach (array_keys($languages) as $code) {
                if (in_array($code, $available, true)) {
                    $preferred = $code;
                    break;
                }
            }
        }

        return $this->container->build(Languages::class, ['options' => [
            'available' => $available,
            'default'   => $config['default'] ?? $available[0] ?? null,
            'current'   => $current ?? null,
            'requested' => $requested ?? null,
            'preferred' => $preferred ?? null,
        ]]);
    }
}
