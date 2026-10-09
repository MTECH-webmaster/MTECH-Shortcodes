<?php
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_format_materials_required($raw_value) {
    if (empty($raw_value)) {
        return '';
    }

    return wp_kses_post($raw_value);
}

function mtech_shortcodes_format_materials_optional($raw_value) {
    if (empty($raw_value)) {
        return '';
    }

    return wp_kses_post($raw_value);
}