<?php

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}
// Define in which folders to search and which folders to exclude
// Exclude some directories that are excluded by Git anyways to speed up the sniffing
$finder = Symfony\CS\Finder::create()
    ->exclude('.Build')
    ->exclude('Documentation')
    ->exclude('Libraries')
    ->in(__DIR__);

// Return a Code Sniffing configuration using
// all sniffers needed for PSR-2
// and additionally:
//  - Remove leading slashes in use clauses.
//  - PHP single-line arrays should not have trailing comma.
//  - Single-line whitespace before closing semicolon are prohibited.
//  - Remove unused use statements in the PHP source code
//  - Ensure Concatenation to have at least one whitespace around
//  - Remove trailing whitespace at the end of blank lines.
return Symfony\CS\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'remove_leading_slash_use', // no_leading_import_slash
        'single_array_no_trailing_comma', // no_trailing_comma_in_singleline_array
        'spaces_before_semicolon', // no_singleline_whitespace_before_semicolons
        'unused_use', // no_unused_imports
        'concat_with_spaces',
        'whitespacy_lines', // no_whitespace_in_blank_line
        'ordered_use', // ordered_imports
        'single_quote',
        'duplicate_semicolon', // no_empty_statement
        'extra_empty_lines', // no_extra_consecutive_blank_lines
        'phpdoc_no_package',
        'phpdoc_scalar',
        'no_empty_lines_after_phpdocs', // no_blank_lines_after_phpdoc
        'short_array_syntax',
        'array_element_white_space_after_comma', // whitespace_after_comma_in_array
        'function_typehint_space',
        'hash_to_slash_comment',
        'join_function', // no_alias_functions
        'lowercase_cast',
        'namespace_no_leading_whitespace', // no_leading_namespace_whitespace
        'native_function_casing',
        'no_empty_statement',
        'self_accessor',
        'short_bool_cast', // no_short_bool_cast
        'unneeded_control_parentheses' // no_unneeded_control_parentheses
    ])
    ->finder($finder);