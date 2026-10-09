<?php
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_format_certs($raw_value) {
    if (empty($raw_value)) {
        return '';
    }

    return wp_kses_post($raw_value);
}