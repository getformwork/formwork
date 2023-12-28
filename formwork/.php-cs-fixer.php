<?php

namespace PhpCsFixer;

$finder = Finder::create()
    ->in(dirname(__DIR__))
    ->exclude(['formwork/views', 'panel/node_modules', 'panel/views', 'site/templates']);

$config = new Config();

return $config
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP83Migration' => true,
        'align_multiline_comment' => true,
        'array_syntax' => true,
        'binary_operator_spaces' => ['default' => 'single_space', 'operators' => ['=>' => 'align_single_space_minimal']],
        'cast_spaces' => true,
        'class_attributes_separation' => true,
        'escape_implicit_backslashes' => true,
        'fully_qualified_strict_types' => ['import_symbols' => true, 'leading_backslash_in_global_namespace' => false],
        'function_declaration' => true,
        'include' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_casing' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_null_property_initialization' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_braces' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
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
        'single_line_empty_body' => false,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);
