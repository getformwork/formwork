<?php

namespace PhpCsFixer;

$finder = Finder::create()
    ->in(dirname(__DIR__))
    ->exclude(['cache', 'formwork/views', 'panel/node_modules', 'panel/views', 'site/templates']);

$config = new Config();

return $config
    ->setRules([
        '@auto' => true,
        '@autoPHPMigration' => true,
        'align_multiline_comment' => true,
        'binary_operator_spaces' => ['default' => 'single_space', 'operators' => ['=>' => 'align_single_space_minimal']],
        'class_attributes_separation' => true,
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'include' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_casing' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => ['tokens' => ['use', 'extra']],
        'no_null_property_initialization' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_braces' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'phpdoc_align' => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_line_span' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'phpdoc_var_without_name' => true,
        'single_quote' => true,
        'string_implicit_backslashes' => true,
    ])
    ->setFinder($finder);
