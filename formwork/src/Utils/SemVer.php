<?php

namespace Formwork\Utils;

use InvalidArgumentException;

class SemVer
{
    /**
     * Regex matching version components
     *
     * @var string
     */
    protected const SEMVER_REGEX = '/^(?<major>0|(?:[1-9]\d*))(?:\.(?<minor>0|(?:[1-9]\d*))?(?:\.(?<patch>0|(?:[1-9]\d*)))?(?:\-(?<prerelease>[0-9A-Z\.-]+))?(?:\+(?<metadata>[0-9A-Z\.-]+))?)?$/i';

    /**
     * Valid operators to compare versions
     *
     * @var array
     */
    protected const COMPARISON_OPERATORS = ['<', '<=', '==', '>=', '>', '!=', '~', '^'];

    /**
     * Valid prerelease tags, compatible with version_compare()
     *
     * @var array
     */
    protected const PRERELEASE_TAGS = ['dev', 'alpha', 'beta', 'RC', 'pl'];

    /**
     * Major version number
     *
     * @var int
     */
    protected $major = 0;

    /**
     * Minor version number
     *
     * @var int
     */
    protected $minor = 0;

    /**
     * Patch version number
     *
     * @var int
     */
    protected $patch = 0;

    /**
     * Version prerelease stability
     *
     * @var string
     */
    protected $prerelease;

    /**
     * Version metadata string
     *
     * @var string
     */
    protected $metadata;

    /**
     * Create a new SemVer instance
     */
    public function __construct(int $major, int $minor, int $patch, string $prerelease = null, string $metadata = null)
    {
        if ($major < 0 || $minor < 0 || $patch < 0) {
            throw new InvalidArgumentException('$major, $minor and $patch arguments must be non-negative integers');
        }
        if ($prerelease !== null) {
            $prerelease = $this->normalizePrerelease($prerelease);
        }
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->prerelease = $prerelease;
        $this->metadata = $metadata;
    }

    /**
     * Get major version number
     */
    public function major(): int
    {
        return $this->major;
    }

    /**
     * Get minor version number
     */
    public function minor(): int
    {
        return $this->minor;
    }

    /**
     * Get patch version number
     */
    public function patch(): int
    {
        return $this->patch;
    }

    /**
     * Get version prerelease stability
     */
    public function prerelease(): ?string
    {
        return $this->prerelease();
    }

    /**
     * Get version version metadata string
     */
    public function metadata(): ?string
    {
        return $this->metadata();
    }

    /**
     * Return an instance with only major, minor and patch numbers
     */
    public function onlyNumbers(): self
    {
        return new static($this->major, $this->minor, $this->patch);
    }

    /**
     * Return whether the version is a prerelease
     */
    public function isPrerelease(): bool
    {
        return $this->prerelease !== null;
    }

    /**
     * Return an instance without version prerelease stability
     */
    public function withoutPrerelease(): self
    {
        return new static($this->major, $this->minor, $this->patch, null, $this->metadata);
    }

    /**
     * Return whether the version has metadata
     */
    public function hasMetadata(): bool
    {
        return $this->metadata !== null;
    }

    /**
     * Return an instance without version metadata
     */
    public function withoutMetadata(): self
    {
        return new static($this->major, $this->minor, $this->patch, $this->prerelease);
    }

    /**
     * Return an instance representing the next major version
     */
    public function nextMajor(): self
    {
        return new static($this->major + 1, 0, 0);
    }

    /**
     * Return an instance representing the next minor version
     */
    public function nextMinor(): self
    {
        return new static($this->major, $this->minor + 1, 0);
    }

    /**
     * Return an instance representing the next patch version
     */
    public function nextPatch(): self
    {
        return new static($this->major, $this->minor, $this->patch + 1);
    }

    /**
     * Return the version as a string that can be used with version_compare()
     */
    public function toComparableString(): string
    {
        return (string) $this->withoutMetadata();
    }

    /**
     * Compare this instance with another
     */
    public function compareWith(self $version, string $operator): bool
    {
        if (!in_array($operator, self::COMPARISON_OPERATORS)) {
            throw new InvalidArgumentException(sprintf('Invalid operator for version comparison: "%s". Use one of the following: "%s"', $operator, implode('", "', self::COMPARISON_OPERATORS)));
        }
        if ($operator === '~') {
            return $this->compareWith($version, '<=') && $this->nextMinor()->compareWith($version->onlyNumbers(), '>');
        }
        if ($operator === '^') {
            return $this->compareWith($version, '<=') && $this->nextMajor()->compareWith($version->onlyNumbers(), '>');
        }
        return version_compare($this->toComparableString(), $version->toComparableString(), $operator);
    }

    /**
     * Compare this instance with a version from a string
     */
    public function compareWithString(string $version, string $operator): bool
    {
        return $this->compareWith(static::fromString($version), $operator);
    }

    /**
     * Create a new SemVer instance from a string
     */
    public static function fromString(string $version): self
    {
        if (!preg_match(self::SEMVER_REGEX, $version, $matches, PREG_UNMATCHED_AS_NULL)) {
            throw new InvalidArgumentException(sprintf('Invalid version string: "%s"', $version));
        }
        return new static((int) ($matches['major']), (int) ($matches['minor']), (int) ($matches['patch']), $matches['prerelease'], $matches['metadata']);
    }

    /**
     * Normalize prerelease tag
     */
    protected function normalizePrerelease(string $prerelease): string
    {
        $parts = explode('.', $prerelease, 2);

        switch ($parts[0]) {
            case 'a':
                $parts[0] = 'alpha';
                break;
            case 'b':
                $parts[0] = 'beta';
                break;
            case 'rc':
                $parts[0] = 'RC';
                break;
            case 'p':
            case 'patch':
                $parts[0] = 'pl';
                break;
        }

        if (!in_array($parts[0], self::PRERELEASE_TAGS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid prerelease tag: "%s". Use one of the following: "%s"', $parts[0], implode('", "', self::PRERELEASE_TAGS)));
        }

        return implode('.', $parts);
    }

    public function __toString(): string
    {
        return sprintf(
            '%u.%u.%u%s%s',
            $this->major,
            $this->minor,
            $this->patch,
            $this->prerelease !== null ? '-' . $this->prerelease : '',
            $this->metadata !== null ? '+' . $this->metadata : ''
        );
    }
}
