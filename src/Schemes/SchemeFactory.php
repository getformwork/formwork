<?php

namespace Formwork\Schemes;

use Formwork\Services\Container;
use Formwork\Utils\Str;

final class SchemeFactory
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Create a new Scheme instance
     *
     * @param array<string, mixed> $data
     */
    public function make(string $id, array $data = []): Scheme
    {
        if (Str::startsWith($id, 'pages.') && isset($data['options']['allowTags'])) {
            trigger_error('The Scheme option "allowTags" is deprecated since Formwork 2.2.0, use "allowTaxonomy"', E_USER_DEPRECATED);
            $data['options']['allowTaxonomy'] = $data['options']['allowTags'];
        }
        return $this->container->build(Scheme::class, compact('id', 'data'));
    }
}
