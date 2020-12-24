<?php

namespace Formwork\Parsers;

use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use LogicException;
use UnexpectedValueException;

class PHP extends AbstractEncoder
{
    /**
     * Number of spaces used to indent arrays
     *
     * @var int
     */
    protected const INDENT_SPACES = 4;

    /**
     * Class names of objects which cannot be encoded
     *
     * @var array
     */
    protected const UNENCODABLE_CLASSES = [\Closure::class, \Reflector::class, \ReflectionGenerator::class, \ReflectionType::class, \IteratorIterator::class, \RecursiveIteratorIterator::class];

    /**
     * @inheritdoc
     */
    public static function parse(string $data, array $options = []): array
    {
        throw new LogicException('Parsing a string of PHP code is not allowed');
    }

    /**
     * @inheritdoc
     */
    public static function parseFile(string $file, array $options = [])
    {
        return include $file;
    }

    /**
     * @inheritdoc
     */
    public static function encode(array $data, array $options = []): string
    {
        return static::encodeData($data);
    }

    /**
     * @inheritdoc
     */
    public static function encodeToFile(array $data, string $file, array $options = []): bool
    {
        if (function_exists('opcache_invalidate') && ($options['invalidateOPcache'] ?? true)) {
            // Invalidate OPcache when a file is encoded again
            opcache_invalidate($file, true);
        }
        return FileSystem::write($file, "<?php\n\nreturn " . static::encodeData($data) . ";\n");
    }

    /**
     * Encodes the given data like var_export() would do, but uses the short array syntax, avoids unneeded integer
     * array keys, outputs lowercase null and serializes objects which don't implement the __set_state() method
     */
    protected static function encodeData($data, int $indent = 0): string
    {
        switch (($type = gettype($data))) {
            case 'array':
                if ($data === []) {
                    return '[]';
                }

                $parts = [];

                foreach ($data as $key => $value) {
                    $parts[] = str_repeat(' ', $indent + self::INDENT_SPACES)
                        . (Arr::isAssociative($data) ? static::encodeData($key) . ' => ' : '')
                        . static::encodeData($value, $indent + self::INDENT_SPACES);
                }

                return '[' . Str::wrap(implode(",\n", $parts), "\n") . str_repeat(' ', $indent) . ']';

            case 'boolean':
            case 'double':
            case 'integer':
            case 'string':
                return var_export($data, true);

            case 'NULL':
                return 'null';

            case 'object':
                $class = get_class($data);

                // stdClass objects are encoded as object casts
                if ($class === \stdClass::class) {
                    return '(object) ' . static::encodeData((array) $data, $indent);
                }

                foreach (self::UNENCODABLE_CLASSES as $c) {
                    if ($data instanceof $c) {
                        throw new UnexpectedValueException(sprintf('Objects of class "%s" cannot be encoded', $class));
                    }
                }

                // Check if the class has a callable __set_state() magic method
                if (method_exists($data, '__set_state') && is_callable([$data, '__set_state'])) {
                    $properties = [];
                    foreach ((array) $data as $property => $value) {
                        // Private and protected properties begin with the class name or an asterisk enclosed
                        // between two NUL bytes, so we need to skip that sequence
                        $properties[Str::afterLast($property, "\0")] = $value;
                    }
                    return '\\' . $class . '::__set_state(' . static::encodeData($properties, $indent) . ')';
                }

                // In the end we try to serialize the object
                return 'unserialize(' . static::encodeData(serialize($data), $indent) . ')';

            default:
                // Resources and unknown types cannot be encoded
                throw new UnexpectedValueException(sprintf('Data of type "%s" cannot be encoded', $type));
        }
    }
}
