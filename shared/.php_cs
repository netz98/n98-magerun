<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return Symfony\CS\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(
        array_merge(
            explode(
                ',',
                'single_blank_line_before_namespace,no_blank_lines_after_class_opening,unused_use,ordered_use,' .
                'concat_with_spaces,spaces_cast,trailing_spaces,unalign_equals'
            ),
            array(
                'array_element_no_space_before_comma', 'array_element_white_space_after_comma',
                'multiline_array_trailing_comma',
                'align_double_arrow',
                'trim_array_spaces',
                'spaces_cast',
                'function_typehint_space', 'join_function',
                'blankline_after_open_tag', 'duplicate_semicolon',
                'extra_empty_lines', 'no_blank_lines_after_class_opening',
                'operators_spaces',
                'remove_leading_slash_use',
                'remove_lines_between_uses',
                'newline_after_open_tag',
                'ordered_use',
                'standardize_not_equal',
                /* php 5.3 compat */ 'class_keyword_remove', 'long_array_syntax ',
            )
        )
    )
    ->finder($finder)
    ->setUsingCache(true)
;
