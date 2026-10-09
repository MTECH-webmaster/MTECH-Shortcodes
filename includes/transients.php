<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_get_program_blob_transient($program_slug) {
    $blob_transient_name = $program_slug . "_blob";
    $transient = get_transient($blob_transient_name);
    return $transient;
}

function mtech_shortcodes_set_program_blob_transient($program_slug, $blob_program_data) {
    $blob_transient_name = $program_slug . "_blob";
    set_transient($blob_transient_name, $blob_program_data, 7200);
}

function mtech_shortcodes_get_shortcode_transient($program_slug, $type) {
    $shortcode_transient_name = $program_slug . $type;
    $transient = get_transient($shortcode_transient_name);
    return $transient;
}

function mtech_shortcodes_set_shortcode_transient($program_slug, $type, $field_data) {
    $shortcode_transient_name = $program_slug . $type;
    set_transient($shortcode_transient_name, $field_data, 7200);
}

function mtech_shortcodes_delete_shortcode_transient($program_slug, $type) {
    $shortcode_transient_name = $program_slug . $type;
    $was_deleted = delete_transient($shortcode_transient_name);
    return $was_deleted;
}