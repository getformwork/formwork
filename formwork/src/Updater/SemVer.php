<?php

namespace Formwork\Updater;

use InvalidArgumentException;
use Stringable;

final class SemVer implements Stringable
{
    /**
     * Regex matching version components
     *
     * @see https://semver.org/
     */
    private const string SEMVER_REGEX = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * Valid operators to compare versions
     *
     * @var list<string>
     */
    private const array COMPARISON_OPERATORS = ['<', '<=', '==', '>=', '>', '!=', '~', '^'];

    /**
     * Valid prerelease tags, compatible with version_compare()
     *
     * @var list<string>
     */
    private const array PRERELEASE_TAGS = ['dev', 'alpha', 'beta', 'RC', 'pl'];

    public function __construct(
        private int $major = 0,
        private int $minor = 0,
        private int $patch = 0,
        private ?string $prerelease = null,
        private ?string $buildMetadata = null,
    ) {
        if ($this->major < 0 || $this->minor < 0 || $this->patch < 0) {
            throw new InvalidArgumentException('$major, $minor and $patch arguments must be non-negative integers');
        }
        if ($this->prerelease !== null) {
            $this->prerelease = $this->normalizePrerelease($this->prerelease);
        }
    }

    public function __toString(): string
    {
        return sprintf(
            '%u.%u.%u%s%s',
            $this->major,
            $this->minor,
            $this->patch,
            $this->prerelease !== null ? "-{$this->prerelease}" : '',
            $this->buildMetadata !== null ? "+{$this->buildMetadata}" : ''
        );
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
        return $this->prerelease;
    }

    /**
     * Get version version build metadata string
     */
    public function buildMetadata(): ?string
    {
        return $this->buildMetadata;
    }

    /**
     * Return an instance with only the version core, i.e. `major.minor.patch`
     */
    public function versionCore(): self
    {
        return new self($this->major, $this->minor, $this->patch);
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
        return new self($this->major, $this->minor, $this->patch, null, $this->buildMetadata);
    }

    /**
     * Return whether the version has build metadata
     */
    public function hasBuildMetadata(): bool
    {
        return $this->buildMetadata !== null;
    }

    /**
     * Return an instance without version build metadata
     */
    public function withoutBuildMetadata(): self
    {
        return new self($this->major, $this->minor, $this->patch, $this->prerelease);
    }

    /**
     * Return an instance representing the next major version
     */
    public function nextMajor(): self
    {
        return new self($this->major + 1, 0, 0);
    }

    /**
     * Return an instance representing the next minor version
     */
    public function nextMinor(): self
    {
        return new self($this->major, $this->minor + 1, 0);
    }

    /**
     * Return an instance representing the next patch version
     */
    public function nextPatch(): self
    {
        return new self($this->major, $this->minor, $this->patch + 1);
    }

    /**
     * Return the version as a string that can be used with version_compare()
     */
    public function toComparableString(): string
    {
        return (string) $this->withoutBuildMetadata();
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
            return $this->compareWith($version, '<=') && $this->nextMinor()->compareWith($version->versionCore(), '>');
        }
        if ($operator === '^') {
            return $this->compareWith($version, '<=') && $this->nextMajor()->compareWith($version->versionCore(), '>');
        }
        return version_compare($this->toComparableString(), $version->toComparableString(), $operator);
    }

    /**
     * Compare this instance with a version from a string
     */
    public function compareWithString(string $version, string $operator): bool
    {
        return $this->compareWith(self::fromString($version), $operator);
    }

    /**
     * Create a new SemVer instance from a string
     */
    public static function fromString(string $version): self
    {
        if (!preg_match(self::SEMVER_REGEX, $version, $matches, PREG_UNMATCHED_AS_NULL)) {
            throw new InvalidArgumentException(sprintf('Invalid version string: "%s"', $version));
        }
        return new self((int) ($matches['major']), (int) ($matches['minor']), (int) ($matches['patch']), $matches['prerelease'], $matches['buildmetadata']);
    }

    /**
     * Normalize prerelease tag
     */
    private function normalizePrerelease(string $prerelease): string
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
}
