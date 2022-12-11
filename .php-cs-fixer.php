<?php

namespace PhpCsFixer;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['formwork/views', 'panel/views', 'site/templates']);

$config = new Config();

return $config
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space', 'operators' => ['=>' => 'align_single_space_minimal']],
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => true,
        'concat_space' => ['spacing' => 'one'],
        'escape_implicit_backslashes' => true,
        'fully_qualified_strict_types' => true,
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
        'no_superfluous_phpdoc_tags' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_align' => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
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
        'single_quote' => true
    ])
    ->setFinder($finder);
