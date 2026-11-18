<?php

namespace Formwork\Tests\Unit\Files\Fixtures;

use Formwork\Files\File;

final class AssociatedFileFixture extends File
{
    public string $origin = 'constructed';

    public function fromPath(string $path): static
    {
        $file = new self($path);
        $file->origin = 'created';

        return $file;
    }
}
