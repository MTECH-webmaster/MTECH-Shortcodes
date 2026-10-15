<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_get_schedule_transient($program_id) {
    $schedule_transient_name = "schedule_transient_" . $program_id;
    $transient = get_transient($schedule_transient_name);
    return $transient;
}

function mtech_shortcodes_set_schedule_transient($program_id, $data) {
    $schedule_transient_name = "schedule_transient_" . $program_id;
    $was_set = set_transient($schedule_transient_name, $data, 604800); // 604800 seconds = 1 week
    return $was_set;
}

function mtech_shortcodes_delete_schedule_transient($program_id) {
    $schedule_transient_name = "schedule_transient_" . $program_id;
    $was_deleted = delete_transient($schedule_transient_name);
    return $was_deleted;
}