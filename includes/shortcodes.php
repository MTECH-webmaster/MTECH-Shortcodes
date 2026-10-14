<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_shortcode_handler($atts) {
    $atts = shortcode_atts(
        array(
            'program_slug' => '',
            'type'    => '',
        ),
        $atts,
        'mtech-shortcodes'
    );

    $program_slug = sanitize_text_field($atts['program_slug']);
    $type    = sanitize_text_field($atts['type']);

    if (empty($program_slug) || empty($type)) {
        return '';
    }

    // Check cache
    // Retrieve all schedule rows that below to the program ID passed as an argument
    // Format as a table
    // Cache
    // return

    return 'test';

    // $token = mtech_shortcodes_get_api_token_from_db();
    // if (is_wp_error($token)) {
    //     mtech_shortcodes_log($token->get_error_message());
    //     return '';
    // }

    // // Check shortcode transient
    // $shortcode_transient = mtech_shortcodes_get_shortcode_transient($program_slug, $type);
    // if ($shortcode_transient !== false) {
    //     return trim($shortcode_transient);
    // }

    // // RETRIEVE SHORTCODE DATA FROM DATABASE HERE BECAUSE IT WILL BE NEEDED REGARDLESS OF BLOB TANSIENT EXISINT OR A COURSEDOG PROGRAM ID BEING USED.
    // /////////////////////////////////////
    // // Get program row using program slug
    // $program_row = mtech_shortcodes_db_get_program_by_slug($program_slug);
    // if (is_null($program_row)) {
    //     mtech_shortcodes_log("Program with slug '{$program_slug}' not found in database.");
    //     return '';
    // }

    // // Get shortcodes program ID for query
    // $shortcodes_program_id_from_db = $program_row->shortcodes_program_id;

    // // Get program ID (auto-increment, not Coursedog)
    // $program_id_from_db = $program_row->id;
    // if (empty($program_id_from_db)) {
    //     mtech_shortcodes_log("Program ID for slug '{$program_slug}' not found in database.");
    //     return '';
    // }
    
    // // Get shortcode row/object
    // $shortcode_object_from_db = mtech_shortcodes_db_get_shortcode($program_id_from_db, $type);
    // if (is_null($shortcode_object_from_db)) {
    //     mtech_shortcodes_log("Shortcode for program ID '{$program_id_from_db}' and type '{$type}' not found in database.");
    //     return '';
    // }

    // // Get field name for query
    // $field = $shortcode_object_from_db->field;
    // if (empty($field)) {
    //     mtech_shortcodes_log("Field for program ID '{$program_id_from_db}' and type '{$type}' not found in database.");
    //     return '';
    // }
    // /////////////////////////////////////

    // $blob_program_data = "";

    // // Check blob transient and retrieve if it doesn't exist
    // $blob_program_data_transient = mtech_shortcodes_get_program_blob_transient($program_slug);
    // if ($blob_program_data_transient !== false) {
    //     $blob_program_data = $blob_program_data_transient;
    // }
    // else {
    //     // If no shortcodes program ID is present, search and fetch
    //     if (is_null($shortcodes_program_id_from_db)) {
    //         $blob_program_data = mtech_shortcodes_search_and_fetch_program_data($shortcode_object_from_db->search_query, $shortcode_object_from_db->effective_dates_range, $token);
    //         if (is_wp_error($blob_program_data)) {
    //             mtech_shortcodes_log($blob_program_data->get_error_message());
    //             return '';
    //         }
    //     }
    //     // If shortcodes program ID is present, fetch by ID
    //     else {
    //         $blob_program_data = mtech_shortcodes_fetch_program_data_by_id($shortcodes_program_id_from_db, $token);
    //         if (is_wp_error($blob_program_data)) {
    //             mtech_shortcodes_log($blob_program_data->get_error_message());
    //             return '';
    //         }
    //     }
        
    //     mtech_shortcodes_set_program_blob_transient($program_slug, $blob_program_data);
    // }

    // $field_data = mtech_shortcodes_format_program_data($blob_program_data, $field, $type);
    // mtech_shortcodes_set_shortcode_transient($program_slug, $type, $field_data);

    // return trim($field_data);
}
add_shortcode('mtech-shortcodes', 'mtech_shortcodes_shortcode_handler');