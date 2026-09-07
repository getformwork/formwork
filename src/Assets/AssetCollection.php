<?php

namespace Formwork\Assets;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Str;

/**
 * @extends AbstractCollection<Asset>
 */
class AssetCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Asset::class;

    protected bool $mutable = true;

    /**
     * Get stylesheets from the collection
     */
    public function stylesheets(): static
    {
        return $this->filter(function (Asset $asset) {
            return $asset->mimeType() === 'text/css';
        });
    }

    /**
     * Get scripts from the collection
     */
    public function scripts(): static
    {
        return $this->filter(function (Asset $asset) {
            return $asset->mimeType() === 'text/javascript';
        });
    }

    /**
     * Get images from the collection
     */
    public function images(): static
    {
        return $this->filter(function (Asset $asset) {
            return Str::startsWith($asset->mimeType(), 'image/');
        });
    }
}
