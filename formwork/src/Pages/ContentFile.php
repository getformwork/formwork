<?php

namespace Formwork\Pages;

use Formwork\Files\File;
use Formwork\Parsers\Yaml;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

class ContentFile extends File
{
    /**
     * Data from the Yaml frontmatter
     */
    protected array $frontmatter;

    /**
     * Content below the frontmatter
     */
    protected string $content;

    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->load();
    }

    /**
     * Return whether the content file is empty
     */
    public function isEmpty()
    {
        return $this->frontmatter === [];
    }

    /**
     * Get the data from the Yaml frontmatter
     */
    public function frontmatter(): array
    {
        return $this->frontmatter;
    }

    /**
     * Get the content
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Load data from the content file
     */
    protected function load(): void
    {
        $contents = FileSystem::read($this->path);

        if (!preg_match('/(?:\s|^)-{3}\s*(.+?)\s*-{3}\s*(.*?)\s*$/s', $contents, $matches)) {
            throw new UnexpectedValueException('Invalid page format');
        }

        [, $frontmatter, $content] = $matches;

        $this->frontmatter = Yaml::parse($frontmatter);

        $this->content = str_replace("\r\n", "\n", $content);
    }
}
