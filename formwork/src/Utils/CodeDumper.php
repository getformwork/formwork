<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use PhpToken;

class CodeDumper
{
    use StaticClass;

    protected static string $css = <<<'CSS'
            .__formwork-code {
                position: relative;
                z-index: 10000;
                margin: 8px;
                padding: 12px 8px;
                border-radius: 4px;
                background-color: #f0f0f0;
                font-family: SFMono-Regular, "SF Mono", "Cascadia Mono", "Liberation Mono", Menlo, Consolas, monospace;
                font-size: 13px;
                overflow-x: auto;
                text-align: left;
            }

            .__formwork-code .__line {
                color: #aaa;
                user-select: none;
            }

            .__formwork-code .__highlighted-line {
                background-color: #f7e7cf;
                border-radius: 4px;
            }

            .__formwork-code .__type-number {
                color: #75438a;
            }

            .__formwork-code .__type-string {
                color: #b35e14;
            }

            .__formwork-code .__type-null {
                color: #75438a;
            }

            .__formwork-code .__type-comment {
                color: #777;
            }

            .__formwork-code .__type-name {
                color: #047d65;
            }

            .__formwork-code .__type-var {
                color: #1d75b3;
            }

            .__formwork-code .__type-keyword {
                color: #dd4a68;
            }
        CSS;

    protected static bool $stylesDumped = false;

    public static function dumpLine(string $file, int $line, int $contextLines = 5): void
    {
        if (!static::$stylesDumped) {
            echo '<style>' . static::$css . '</style>';
            static::$stylesDumped = true;
        }
        echo '<pre class="__formwork-code">', static::highlightLine(static::highlightPhpCode(FileSystem::read($file)), $line, $contextLines), '</pre>';
    }

    /**
     * @see https://github.com/nette/tracy/blob/v2.10.7/src/Tracy/BlueScreen/CodeHighlighter.php Some parts are taken from `nette/tracy` code highlighter with adaptations
     */
    protected static function highlightLine(string $html, int $line, int $contextLines = 5): string
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
     * @see https://github.com/nette/tracy/blob/v2.10.7/src/Tracy/BlueScreen/CodeHighlighter.php Some parts are taken from `nette/tracy` code highlighter with adaptations
     */
    protected static function highlightPhpCode(string $code): string
    {
        $code = str_replace("\r\n", "\n", $code);
        $code = (string) preg_replace('/(__halt_compiler\s*\(\)\s*;).*/is', '$1', $code);
        $code = rtrim($code);

        $last = $html = '';

        foreach (PhpToken::tokenize($code) as $phpToken) {
            $next = match ($phpToken->id) {
                T_ATTRIBUTE, T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML => '__type-comment',
                T_LINE, T_FILE, T_DIR, T_TRAIT_C, T_METHOD_C, T_FUNC_C, T_NS_C, T_CLASS_C,
                T_STRING, T_ARRAY, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE => '__type-name',
                T_LNUMBER, T_DNUMBER => '__type-number',
                T_VARIABLE => '__type-var',
                T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING => '__type-string',
                T_ABSTRACT, T_AS, T_BREAK, T_CALLABLE, T_CASE, T_CATCH, T_CLASS, T_CLONE, T_CLOSE_TAG, T_CONST, T_CONTINUE, T_DECLARE,
                T_DEFAULT, T_DO, T_ECHO, T_ELSE, T_ELSEIF, T_EMPTY, T_ENDDECLARE, T_ENDFOR, T_ENDFOREACH, T_ENDIF, T_ENDSWITCH, T_ENDWHILE,
                T_ENUM, T_EVAL, T_EXIT, T_EXTENDS, T_FINAL, T_FINALLY, T_FN, T_FOR, T_FOREACH, T_FUNCTION, T_GLOBAL, T_GOTO, T_IF,
                T_IMPLEMENTS, T_INCLUDE_ONCE, T_INCLUDE, T_INSTANCEOF, T_INTERFACE, T_ISSET, T_LIST, T_LOGICAL_AND, T_LOGICAL_OR,
                T_LOGICAL_XOR, T_MATCH, T_NAMESPACE, T_NEW, T_OPEN_TAG_WITH_ECHO, T_OPEN_TAG, T_PRINT, T_PRIVATE, T_PROTECTED, T_PUBLIC,
                T_READONLY, T_REQUIRE_ONCE, T_REQUIRE, T_RETURN, T_STATIC, T_SWITCH, T_THROW, T_TRAIT, T_TRY, T_UNSET, T_USE, T_VAR,
                T_WHILE, T_YIELD_FROM, T_YIELD, => '__type-keyword',
                T_WHITESPACE => $last,
                default      => '',
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
