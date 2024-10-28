<?php
if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('config')
    ->exclude('public')
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('Documentation')
    ->exclude('Libraries')
    ->notName('ext_emconf.php')
    ->notName('ext_localconf.php')
    ->notName('ext_tables.php')
    ->notName('ComposerPackagesCommands.php');

$configFinder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->depth('0');

$finder->append($configFinder);

return (new PhpCsFixer\Config)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@DoctrineAnnotation' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        'cast_spaces' => ['space' => 'none'],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'declare_equal_normalize' => ['space' => 'none'],
        'declare_strict_types' => true,
        'dir_constant' => true,
        'function_typehint_space' => true,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'linebreak_after_opening_tag' => true,
        'lowercase_cast' => true,
        'class_attributes_separation' => [
            'elements' => ['const' => 'none', 'method' => 'one', 'property' => 'one', 'trait_import' => 'one', 'case' => 'one'],
        ],
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_blank_lines_before_namespace' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'multiline_whitespace_before_semicolons' => false,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unreachable_default_argument_value' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'non_printable_character' => true,
        'normalize_index_brace' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'phpdoc_var_without_name' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'self_accessor' => true,
        'short_scalar_cast' => true,
        'single_quote' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays']
        ],
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder);
