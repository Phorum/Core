<?php

return [
    'target_php_version' => '8.4',

    'directory_list' => [
        '.',
    ],

    'exclude_file_regex' => '@^(vendor|tests)/|mods/smtp_mail/phpmailer@',

    'exclude_analysis_directory_list' => [
        'vendor/',
    ],

    'analyzed_file_extensions' => ['php'],

    // Stubs for extensions in use
    'autoload_internal_extension_signatures' => [
        'mysqli'    => 'vendor/phan/phan/internal_stubs/mysqli.phan_php',
        'gd'        => 'vendor/phan/phan/internal_stubs/gd.phan_php',
        'memcached' => 'vendor/phan/phan/internal_stubs/memcached.phan_php',
    ],

    // Focus on deprecations and real errors; suppress noise from legacy
    // procedural globals patterns that Phan can't fully reason about.
    'minimum_severity' => 0,

    'suppress_issue_types' => [
        // Phorum uses $PHORUM global extensively - too noisy to track
        'PhanUndeclaredVariable',
        'PhanUndeclaredGlobalVariable',
        // Legacy procedural code has many untyped returns
        'PhanTypeMismatchReturn',
        'PhanTypeMismatchArgument',
        'PhanTypeMismatchArgumentInternal',
        // Template files use variables set by eval'd template code
        'PhanUndeclaredVariableDim',
        // Old-style class declarations without strict typing
        'PhanTypeObjectUncastableToString',
    ],

    'plugins' => [
        'AlwaysReturnPlugin',
        'DuplicateExpressionPlugin',
    ],

    // Report deprecated function/method usage
    'warn_about_undocumented_throw_statements' => false,
    'redundant_condition_detection' => false,
    'dead_code_detection' => false,

    'quick_mode' => false,
];
