<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/format-schedule.php';

function mtech_shortcodes_get_formatters() {
    return array(
        'schedule' => 'mtech_shortcodes_format_schedule',
    );
}

function mtech_shortcodes_format_program_data($data, $type) {
    $formatters = mtech_shortcodes_get_formatters();

    if (!isset($formatters[$type])) {
        mtech_shortcodes_log("No formatter registered for type '{$type}'");
        return '';
    }

    return call_user_func($formatters[$type], $data);
}