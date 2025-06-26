<?php

use Phan\Issue;

return [

    // Analyze code assuming PHP 8.2 features and behavior (e.g. deprecations, removed features).
    'target_php_version' => '8.2',

    // Minimum PHP version required — ensures code is only compatible with PHP 8.2 and above.
    'minimum_target_php_version' => '8.2',

    // Enables expensive checks for backward-incompatible changes from older PHP versions.
    // Useful for identifying features that no longer work in PHP 8.2.
    'backward_compatibility_checks' => true,

    // These directories will be parsed but excluded from static analysis.
    // Useful for skipping third-party/vendor/test code while retaining class info.
    'exclude_analysis_directory_list' => [
        'vendor/',
        'tests/',
    ],

    // Allows `null` to be cast to any type and vice versa.
    // Helps reduce false positives in loosely typed code.
    'null_casts_as_any_type' => true,

    // Allows automatic casting between scalar types (int, float, string, bool).
    // Acceptable for codebases with weak typing.
    'scalar_implicit_cast' => true,

    // When false (as here), Phan does a deeper analysis and rescans functions as needed.
    'quick_mode' => false,

    // Don't warn about variables used in the global scope that weren’t explicitly declared.
    'ignore_undeclared_variables_in_global_scope' => true,

    // Suppress specific issue types to avoid noise during analysis.
    'suppress_issue_types' => [
        'PhanPossiblyUndeclaredVariable',
        'PhanUndeclaredProperty',
        'PhanUndeclaredTypeThrowsType'
    ],

    // Only report this specific type of issue, in addition to what's not suppressed.
    // Here, we explicitly allow syntax errors to show up regardless.
//    'whitelist_issue_types' => [
//        'PhanSyntaxError',
//    ],

    // Only show issues with normal severity or higher.
    // Ignores informational or stylistic warnings.
    'minimum_severity' => Issue::SEVERITY_NORMAL,

    // Number of parallel processes Phan can use to speed up analysis.
    'processes' => 2,
];
