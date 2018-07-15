<?php

namespace PhpCsFixer;

$finder = Finder::create()
    ->exclude('admin/views')
    ->in(__DIR__);

return Config::create()
    ->setRules(array(
        '@PSR1' => true,
        '@PSR2' => true,
        'cast_spaces' => array('space' => 'single'),
        'concat_space' => array('spacing' => 'one'),
        'escape_implicit_backslashes' => true,
        'include' => true,
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'ordered_class_elements' => array('constant_public', 'constant_protected', 'constant_private', 'property_public', 'property_protected', 'property_private', 'construct', 'method_public', 'method_protected', 'method_private', 'destruct', 'magic'),
        'single_quote' => true
    ))
    ->setFinder($finder);
