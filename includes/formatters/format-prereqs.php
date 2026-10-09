<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formats program prerequisites by combining two source fields:
 *  - customFields['P5PyW']: an array of plain-text, school-wide requirements
 *  - customFields['VGtKv']: program-specific requirements, already HTML from
 *    Coursedog's WYSIWYG editor
 *
 * NOTE: this formatter needs the *entire* customFields object, not a single
 * extracted field — the dispatcher must special-case the 'prereqs' type to
 * pass $blob['customFields'] directly rather than a single field value.
 */
function mtech_shortcodes_format_prereqs($custom_fields) {
    if (!is_array($custom_fields)) {
        return esc_html('-');
    }

    $school_prereqs = isset($custom_fields['P5PyW']) && is_array($custom_fields['P5PyW'])
        ? $custom_fields['P5PyW']
        : array();

    $program_prereqs_html = isset($custom_fields['VGtKv']) && is_string($custom_fields['VGtKv'])
        ? trim($custom_fields['VGtKv'])
        : '';

    $output = '';

    // School-wide prerequisites first — plain strings, so we build our own
    // safe <ul> rather than trusting/injecting raw text.
    if (!empty($school_prereqs)) {
        $items = '';
        foreach ($school_prereqs as $item) {
            if (!is_string($item) || trim($item) === '') {
                continue;
            }
            $items .= '<li>' . esc_html(trim($item)) . '</li>';
        }
        if ($items !== '') {
            $output .= '<ul>' . $items . '</ul>';
        }
    }

    // Program-specific prerequisites — already HTML from Coursedog's WYSIWYG.
    // Sanitized with wp_kses_post rather than trusted as-is (same reasoning
    // applied to the certs formatter).
    if ($program_prereqs_html !== '') {
        $output .= wp_kses_post($program_prereqs_html);
    }

    if ($output === '') {
        return esc_html('-');
    }

    return $output;
}