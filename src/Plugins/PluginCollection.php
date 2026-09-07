<?php

namespace Formwork\Plugins;

use Formwork\Data\AbstractCollection;

/**
 * @extends AbstractCollection<Plugin>
 *
 * @since 2.3.0
 */
class PluginCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Plugin::class;
}
