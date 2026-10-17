<?php

namespace Formwork\Languages;

use Formwork\Http\Request;
use Formwork\Services\Container;
use Formwork\Utils\Str;

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

        if (preg_match('~^/(' . implode('|', array_map(preg_quote(...), $available)) . ')/~i', $this->request->uri(), $matches)) {
            $requested = $current = strtolower($matches[1]);
        }

        if ($httpPreferred) {
            $languages = $this->request->languages();
            foreach (array_keys($languages) as $code) {
                // Try candidates: full code and base language
                // (e.g., "en-US" => ["en-us", "en"])
                $code = strtolower($code);
                foreach ([$code, Str::before($code, '-')] as $candidate) {
                    if (in_array($candidate, $available, true)) {
                        $preferred = $candidate;
                        break 2;
                    }
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
