<?php

namespace Formwork\Utils;

use ReflectionReference;
use UnexpectedValueException;
use UnitEnum;

class Debug
{
    protected const INDENT_SPACES = 2;

    protected static string $css = <<<'CSS'
        .__formwork-dump {
            position: relative;
            z-index: 10000;
            margin: 8px;
            padding: 12px 8px;
            border-radius: 4px;
            background-color: #f0f0f0;
            font-family: SFMono-Regular, "SF Mono", "Cascadia Mono", "Liberation Mono", Menlo, Consolas, monospace;
            font-size: 13px;
            overflow-x: auto;
        }

        .__formwork-dump .__type-bool {
            color: #75438a;
        }

        .__formwork-dump .__type-number {
            color: #75438a;
        }

        .__formwork-dump .__type-string {
            color: #b35e14;
        }

        .__formwork-dump .__type-null {
            color: #75438a;
        }

        .__formwork-dump .__note,
        .__formwork-dump .__ref {
            color: #777;
            cursor: default;
            font-size: 0.875em;
        }

        .__formwork-dump .__visibility {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 4px;
            margin-right: 4px;
            color: #777;
            cursor: default;
            font-size: 0.75em;
        }

        .__formwork-dump .__visibility-public {
            background-color: #cfe4f7;
        }

        .__formwork-dump .__visibility-protected {
            background-color: #f7e7cf;
        }

        .__formwork-dump .__visibility-private {
            background-color: #f1cff7;
        }

        .__formwork-dump .__type-name,
        .__formwork-dump .__type-array {
            color: #047d65;
        }

        .__formwork-dump .__type-property {
            color: #1d75b3;
        }

        .__formwork-dump .__ref:target {
            background-color: #ff0;
        }

        .__formwork-dump .__ref a,
        .__formwork-dump .__ref a:hover {
            color: #1d75b3;
        }

        .__formwork-dump-collapsed {
            display: none;
        }

        .__formwork-dump-toggle {
            color: #777;
            cursor: default;
            vertical-align: 1px;
        }
        CSS;

    protected static string $js = <<<'JS'
        function __formwork_dump_toggle(self, recursive = true) {
            const ref = document.getElementById(self.dataset.target);
            self.innerHTML = ref.classList.toggle("__formwork-dump-collapsed") ? "▼" : "▲";

            if (recursive && window.event.shiftKey) {
                const refs = ref.querySelectorAll(".__formwork-dump-toggle");
                for (const ref of refs) {
                    if (ref !== self) {
                        __formwork_dump_toggle(ref, false);
                    }
                }
            }
        }

        function __formwork_dump_goto(target) {
            if (!target) {
                return;
            }

            const targetElement = document.getElementById(target);

            let containerElement = targetElement;
            while ((containerElement = containerElement.closest("div.__formwork-dump-collapsed"))) {
                __formwork_dump_toggle(document.querySelector(`.__formwork-dump-toggle[data-target="${containerElement.id}"]`));
            }

            targetElement.scrollIntoView();
        }

        window.addEventListener("hashchange", () => {
            const target = window.location.hash.slice(1);
            __formwork_dump_goto(target);
        });
        JS;

    /**
     * @var array<int>
     */
    protected static array $refs = [];

    protected static int $counter = 0;

    protected static bool $stylesDumped = false;

    public static function dump(mixed ...$data): void
    {
        if (!headers_sent()) {
            ob_start();
        }
        if (!static::$stylesDumped) {
            echo '<style>' . static::$css . '</style>', '<script>' . static::$js . '</script>';
            static::$stylesDumped = true;
        }
        foreach ($data as $d) {
            echo static::dumpToString($d);
        }
        echo '<script>__formwork_dump_goto(window.location.hash.slice(1))</script>';
    }

    public static function dd(mixed ...$data): void
    {
        static::dump(...$data);
        exit;
    }

    public static function dumpToString(mixed $data): string
    {
        return sprintf('<pre class="__formwork-dump">%s</pre>', static::outputData($data));
    }

    protected static function outputData(mixed $data, int $indent = 0): string
    {
        switch (gettype($data)) {
            case 'boolean':
                return '<span class="__type-bool">' . ($data ? 'true' : 'false') . '</span>';

            case 'double':
            case 'integer':
                return '<span class="__type-number">' . $data . '</span>';

            case 'string':
                $binary = !mb_check_encoding($data, 'UTF-8');
                $multiline = (bool) preg_match('/\n|\r/', $data);

                $data = htmlspecialchars($data);

                if ($multiline && ($lines = preg_split('/^|[\r\n]+/', $data))) {
                    return '<span class="__type-string">' . ($binary ? 'b' : '') . '"""' . implode("\n" . str_repeat(' ', $indent + self::INDENT_SPACES), $lines) . "\n" . str_repeat(' ', $indent) . '"""</span>';
                }
                return '<span class="__type-string">' . ($binary ? 'b' : '') . '"' . $data . '"</span>';

            case 'NULL':
                return '<span class="__type-null">null</span>';

            case 'array':
                if ($data === []) {
                    return sprintf("<span class=\"__type-array\">array</span>(<span class=\"__note\">0</span>) [\n%s]</span>", str_repeat(' ', $indent));
                }

                $parts = [];
                $associative = !array_is_list($data);

                foreach ($data as $key => $value) {
                    $reference = ReflectionReference::fromArrayElement($data, $key) !== null;

                    $parts[] = str_repeat(' ', $indent + self::INDENT_SPACES)
                        . ($associative ? static::outputData($key) . ' => ' : '')
                        . ($reference ? '<span class="__note" title="Reference">&</span>' : '')
                        . static::outputData($value, $indent + self::INDENT_SPACES)
                        . ',';
                }

                return sprintf("<span class=\"__type-array\">array</span>(<span class=\"__note\">%d</span>) [<span class=\"__formwork-dump-toggle\" onclick=\"__formwork_dump_toggle(this)\" data-target=\"__formwork-dump-id-%2\$d\">▼</span>\n<div class=\"__formwork-dump-collapsed\" id=\"__formwork-dump-id-%d\">%s</div>%s]", count($data), ++static::$counter, implode("\n", $parts), str_repeat(' ', $indent));

            case 'object':
                if ($data instanceof UnitEnum) {
                    return sprintf('<span class="__type-name">%s</span>::<span class="__type-name">%s</span>', $data::class, $data->name);
                }

                $id = spl_object_id($data);

                $class = $data::class;
                $parts = [];

                if (in_array($id, static::$refs)) {
                    return sprintf('<span class="__type-name">%s</span>(<span class="__note">#%d</span>) { <span class="__ref"><a href="#__formwork-dump-ref-%2$d" title="Go to reference">...</a></span> }</span>', $class, $id);
                }

                static::$refs[] = $id;

                foreach ((array) $data as $property => $value) {
                    if (str_starts_with($property, "\0*\0")) {
                        $property = '<span class="__visibility __visibility-protected" title="Protected property">protected</span><span class="__type-property">' . substr($property, 3) . '</span>';
                    } elseif (str_starts_with($property, "\0{$class}\0")) {
                        $property = '<span class="__visibility __visibility-private" title="Private property">private</span><span class="__type-property">' . substr($property, strlen("\0{$class}\0")) . '</span>';
                    } else {
                        $property = '<span class="__visibility __visibility-public" title="Public property">public</span><span class="__type-property">' . $property . '</span>';
                    }

                    $parts[] = str_repeat(' ', $indent + self::INDENT_SPACES)
                        . $property . ': '
                        . static::outputData($value, $indent + self::INDENT_SPACES);
                }

                return sprintf(
                    "<span class=\"__type-name\">%s</span>(<span class=\"__ref\" id=\"__formwork-dump-ref-%d\">#%2\$d</span>) {<span class=\"__formwork-dump-toggle\" onclick=\"__formwork_dump_toggle(this)\" data-target=\"__formwork-dump-id-%3\$d\">▼</span>\n<div class=\"__formwork-dump-collapsed\" id=\"__formwork-dump-id-%d\">%s</div>%s}",
                    $class,
                    $id,
                    ++static::$counter,
                    implode("\n", $parts),
                    str_repeat(' ', $indent)
                );

            case 'resource':
                return sprintf('<span class="__type-name">resource</span>(<span class="__type-name">%s</span> <span class="__note">#%d</span>)', get_resource_type($data), get_resource_id($data));
        }

        throw new UnexpectedValueException('Unexpected value for debug');
    }
}
