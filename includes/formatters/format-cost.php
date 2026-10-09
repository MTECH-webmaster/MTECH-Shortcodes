<?php
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_format_cost($raw_value) {
    if (empty($raw_value)) {
        return '';
    }

    $numberCleaned = preg_replace('/[^\d.]/', '', $raw_value);
    $float = floatval($numberCleaned);
    $intValue = intval(round($float));
    return "$" . number_format($intValue);
}