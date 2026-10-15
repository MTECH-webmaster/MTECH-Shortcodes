<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_shortcode_handler($atts) {
    $atts = shortcode_atts(
        array(
            'program_id' => '',
        ),
        $atts,
        'mtech-shortcodes'
    );

    $program_id = sanitize_text_field($atts['program_id']);

    if (empty($program_id)) {
        return '';
    }

    // Check cache
    $schedule = mtech_shortcodes_get_schedule_transient($program_id);
    if ($schedule) {
        return $schedule;
    }

    global $wpdb;
    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';
    $table_schedules = $wpdb->prefix . 'mtech_shortcodes_schedules';

    $program_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE id = %d", $program_id)
    );
    if (!$program_exists) {
        return '';
    }

    // Retrieve all schedule rows that belong to the program ID passed as an argument
    $schedule_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, locations, start_date, hours, days
                FROM $table_schedules
                WHERE program_id = %d
                ORDER BY id ASC",
            $program_id
        )
    );

    if (empty($schedule_rows)) {
        return '';
    }

    // Format as a table
    $formatted = mtech_shortcodes_format_program_data($schedule_rows, 'schedule');
    mtech_shortcodes_set_schedule_transient($program_id, $formatted);

    return $formatted;
}
add_shortcode('mtech-shortcodes', 'mtech_shortcodes_shortcode_handler');
