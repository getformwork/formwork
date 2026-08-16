<?php

namespace Formwork\Debug;

use Formwork\Traits\StaticClass;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PhpToken;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use SensitiveParameter;
use SensitiveParameterValue;

final class CodeDumper
{
    use StaticClass;

    /**
     * CSS styles for code highlighting
     */
    private static string $css = <<<'CSS'
            .__formwork-code {
                position: relative;
                z-index: 10000;
                margin: 8px;
                padding: 12px 8px;
                border-radius: 4px;
                background-color: #f0f0f0;
                color: #333;
                font-family: SFMono-Regular, "SF Mono", "Cascadia Mono", "Liberation Mono", Menlo, Consolas, monospace;
                font-size: 13px;
                overflow-x: auto;
                text-align: left;
            }

            .color-scheme-dark .__formwork-code {
                background-color: #333;
                color: #f0f0f0;
            }

            .__formwork-code .__line {
                color: #aaa;
                user-select: none;
            }

            .__formwork-code .__highlighted-line {
                background-color: #f7e7cf;
                border-radius: 4px;
            }

            .color-scheme-dark .__formwork-code .__highlighted-line {
                background-color: #665600;
                color: #f0f0f0;
            }

            .__formwork-code .__type-number {
                color: #75438a;
            }

            .color-scheme-dark .__formwork-code .__type-number {
                color: #d48cf2;
            }

            .__formwork-code .__type-string {
                color: #b35e14;
            }

            .color-scheme-dark .__formwork-code .__type-string {
                color: #ea9143;
            }

            .__formwork-code .__type-null {
                color: #75438a;
            }

            .color-scheme-dark .__formwork-code .__type-null {
                color: #d48cf2;
            }

            .__formwork-code .__type-comment {
                color: #777;
            }

            .color-scheme-dark .__formwork-code .__type-comment {
                color: #888;
            }

            .__formwork-code .__type-name {
                color: #047d65;
            }

            .color-scheme-dark .__formwork-code .__type-name {
                color: #07dfb3;
            }

            .__formwork-code .__type-var {
                color: #1d75b3;
            }

            .color-scheme-dark .__formwork-code .__type-var {
                color: #40abf7;
            }

            .__formwork-code .__type-keyword {
                color: #dd4a68;
            }

            .color-scheme-dark .__formwork-code .__type-keyword {
                color: #ff5c7c;
            }

            .__formwork-trace-call {
                margin: 8px 0;
                padding: 12px 8px;
                border-radius: 4px;
                background-color: #f0f0f0;
                color: #333;
                font-family: SFMono-Regular, "SF Mono", "Cascadia Mono", "Liberation Mono", Menlo, Consolas, monospace;
                font-size: 13px;
            }

            .color-scheme-dark .__formwork-trace-call {
                background-color: #333;
                color: #f0f0f0;
            }

            .__formwork-trace-call .__name {
                color: #047d65;
            }

            .color-scheme-dark .__formwork-trace-call .__name {
                color:  #07dfb3;
            }

            .__formwork-trace-params {
                overflow-x: auto;
                margin-bottom: 16px;
            }

            .__formwork-trace-params table {
                width: 100%;
                border-collapse: collapse;
            }

            .__formwork-trace-params td {
                padding: 8px 0;
                border-top: 1px solid #ddd;
                border-bottom: 1px solid #ddd;
            }

            .__formwork-trace-params .__param-name {
                width: 20%;
                vertical-align: top;
                font-family: SFMono-Regular, "SF Mono", "Cascadia Mono", "Liberation Mono", Menlo, Consolas, monospace;
                font-size: 13px;
                overflow-wrap: break-word;
                color: #1d75b3;
                padding-right: 8px;
            }

            .color-scheme-dark .__formwork-trace-params .__param-name {
                color:  #40abf7;
            }

            .__formwork-trace-params code {
                padding: 2px 4px;
                border-radius: 4px;
                font-size: inherit;
            }

            .__formwork-trace-params .__formwork-dump {
                margin: 0;
            }

            .__formwork-trace-params .__param-type {
                display: inline-block;
                padding: 1px 4px;
                border-radius: 4px;
                margin-left: 4px;
                color: #777;
                cursor: default;
                font-size: 0.75em;
            }

            .__formwork-trace-params .__type-default {
                background-color: #d4f7cf;
            }

        CSS;

    /**
     * Whether CSS styles have been dumped
     */
    private static bool $stylesDumped = false;

    /**
     * Dump a file line with context
     */
    public static function dumpLine(string $file, int $line, int $contextLines = 5): void
    {
        self::dumpStyles();
        echo '<pre class="__formwork-code">', self::highlightLine(self::highlightPhpCode(FileSystem::read($file)), $line, $contextLines), '</pre>';
    }

    /**
     * Dump a backtrace frame
     *
     * @param array{file?: string, line?: int, function?: string, class?: string, object?: object, type?: string, args?: list<mixed>} $frame Backtrace frame
     */
    public static function dumpBacktraceFrame(array $frame, int $contextLines = 5): void
    {
        if (!isset($frame['file'], $frame['line'])) {
            throw new InvalidArgumentException('Backtrace frame must contain "file" and "line" keys to dump code.');
        }

        self::dumpStyles();
        self::dumpLine($frame['file'], $frame['line'], $contextLines);

        if (!isset($frame['function'])) {
            return;
        }

        $result = sprintf('<pre class="__formwork-trace-call"><span class="__name">%s</span>%s<span class="__name">%s</span>()</pre>', $frame['class'] ?? '', $frame['type'] ?? '', $frame['function']);

        $parameterCount = 0;

        $result .= '<div class="__formwork-trace-params"><table>' . "\n";

        try {
            $reflection = isset($frame['class']) ? new ReflectionMethod($frame['class'], $frame['function']) : new ReflectionFunction($frame['function']);
            $parameterCount = count($reflection->getParameters());

            foreach ($reflection->getParameters() as $i => $reflectionParameter) {
                $name = ($reflectionParameter->isVariadic() ? '...$' : '$') . $reflectionParameter->getName();
                $values = array_slice($frame['args'] ?? [], $i, $reflectionParameter->isVariadic() ? null : 1);
                $default = false;

                if ($values === [] && $reflectionParameter->isDefaultValueAvailable()) {
                    $values = [$reflectionParameter->getDefaultValue()];
                    $default = true;
                }

                foreach ($values as $j => $value) {
                    if ($reflectionParameter->getAttributes(SensitiveParameter::class)) {
                        $value = new SensitiveParameterValue($value);
                    }
                    $result .= "<tr class=\"__row\">\n";
                    if ($j === 0) {
                        $result .= sprintf('<td class="__param-name" rowspan="%d"><code>%s</code>', count($values), $name);
                        if ($default) {
                            $result .= '<span class="__param-type __type-default">default</span>';
                        }
                        $result .= "</td>\n";
                    }

                    ob_start();
                    Debug::dump($value);

                    $result .= sprintf("<td>%s</td></tr>\n", ob_get_clean());
                }
            }
        } catch (ReflectionException) {
            // Unable to reflect function/method, skip parameter dumping
        }

        if (isset($frame['args']) && $parameterCount < count($frame['args'])) {
            foreach (array_slice($frame['args'], $parameterCount) as $i => $value) {
                $result .= sprintf("<tr class=\"__row\">\n<td class=\"__param-name\"><code>#%d</code></td>\n<td>%s</td></tr>\n", $parameterCount + $i, Debug::dumpToString($value));
            }
        }
        $result .= '</table></div>';

        echo $result;
    }

    /**
     * Dump styles
     */
    private static function dumpStyles(): void
    {
        if (!self::$stylesDumped) {
            echo '<style>' . self::$css . '</style>';
            self::$stylesDumped = true;
        }
    }

    /**
     * Highlight a line in a code snippet
     *
     * @see https://github.com/nette/tracy/blob/v2.10.7/src/Tracy/BlueScreen/CodeHighlighter.php Some parts are taken from `nette/tracy` code highlighter with adaptations
     */
    private static function highlightLine(string $html, int $line, int $contextLines = 5): string
    {
        $html = str_replace("\r\n", "\n", $html);
        $lines = explode("\n", $html);
        $linesCount = count($lines);

        $startLine = $contextLines < 0 ? 1 : max(1, $line - $contextLines);
        $endLine = min($linesCount, $contextLines < 0 ? $linesCount : $line + $contextLines);

        $openTags = $closeTags = [];
        $lineDigits = ceil(log($endLine, 10));

        $result = '';

        for ($i = 0; $i < $linesCount; $i++) {
            $lineNumber = $i + 1;

            if ($lineNumber === $startLine) {
                $result = implode('', $openTags);
            }

            if ($lineNumber === $line) {
                $result .= implode('', $closeTags);
            }

            preg_replace_callback('/<\/?(\w+)[^>]*>/', function ($match) use (&$openTags, &$closeTags) {
                if ($match[0][1] === '/') {
                    array_pop($openTags);
                    array_shift($closeTags);
                } else {
                    $openTags[] = $match[0];
                    array_unshift($closeTags, "</$match[1]>");
                }
                return '';
            }, $lines[$i]);

            if ($lineNumber < $startLine) {
                continue;
            }

            $result .= $lineNumber === $line
                ? sprintf("<mark class=\"__highlighted-line\"><span class=\"__line\">%{$lineDigits}d </span>%s</mark>\n%s", $lineNumber, $lines[$i], implode(' ', $openTags))
                : sprintf("<span class=\"__line\">%{$lineDigits}d </span>%s\n", $lineNumber, $lines[$i]);

            if ($lineNumber === $endLine) {
                break;
            }
        }

        return $result . implode(' ', $closeTags);
    }

    /**
     * Highlight PHP code
     *
     * @see https://github.com/nette/tracy/blob/v2.10.7/src/Tracy/BlueScreen/CodeHighlighter.php Some parts are taken from `nette/tracy` code highlighter with adaptations
     */
    private static function highlightPhpCode(string $code): string
    {
        if (!extension_loaded('tokenizer')) {
            throw new RuntimeException(sprintf('%s() requires the extension "tokenizer" to be enabled', __METHOD__));
        }

        $code = str_replace("\r\n", "\n", $code);
        $code = (string) preg_replace('/(__halt_compiler\s*\(\)\s*;).*/is', '$1', $code);
        $code = rtrim($code);

        $last = $html = '';

        foreach (PhpToken::tokenize($code) as $phpToken) {
            $next = match ($phpToken->id) {
                T_ATTRIBUTE, T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML => '__type-comment',
                T_LINE, T_FILE, T_DIR, T_TRAIT_C, T_METHOD_C, T_FUNC_C, T_NS_C, T_CLASS_C,
                T_STRING, T_ARRAY, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE => '__type-name',
                T_LNUMBER, T_DNUMBER                                                         => '__type-number',
                T_VARIABLE                                                                   => '__type-var',
                T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING                        => '__type-string',
                T_ABSTRACT, T_AS, T_BREAK, T_CALLABLE, T_CASE, T_CATCH, T_CLASS, T_CLONE, T_CLOSE_TAG, T_CONST, T_CONTINUE, T_DECLARE,
                T_DEFAULT, T_DO, T_ECHO, T_ELSE, T_ELSEIF, T_EMPTY, T_ENDDECLARE, T_ENDFOR, T_ENDFOREACH, T_ENDIF, T_ENDSWITCH, T_ENDWHILE,
                T_ENUM, T_EVAL, T_EXIT, T_EXTENDS, T_FINAL, T_FINALLY, T_FN, T_FOR, T_FOREACH, T_FUNCTION, T_GLOBAL, T_GOTO, T_IF,
                T_IMPLEMENTS, T_INCLUDE_ONCE, T_INCLUDE, T_INSTANCEOF, T_INTERFACE, T_ISSET, T_LIST, T_LOGICAL_AND, T_LOGICAL_OR,
                T_LOGICAL_XOR, T_MATCH, T_NAMESPACE, T_NEW, T_OPEN_TAG_WITH_ECHO, T_OPEN_TAG, T_PRINT, T_PRIVATE, T_PROTECTED, T_PUBLIC,
                T_READONLY, T_REQUIRE_ONCE, T_REQUIRE, T_RETURN, T_STATIC, T_SWITCH, T_THROW, T_TRAIT, T_TRY, T_UNSET, T_USE, T_VAR,
                T_WHILE, T_YIELD_FROM, T_YIELD, => '__type-keyword',
                T_WHITESPACE                    => $last,
                default                         => '',
            };

            if ($last !== $next) {
                if ($last !== '') {
                    $html .= '</span>';
                }
                $last = $next;
                if ($last !== '') {
                    $html .= '<span class="' . $last . '">';
                }
            }

            $html .= strtr($phpToken->text, ['<' => '&lt;', '>' => '&gt;', '&' => '&amp;', "\t" => ' ']);
        }
        if ($last !== '') {
            $html .= '</span>';
        }

        return $html;
    }
}
