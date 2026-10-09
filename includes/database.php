<?php

if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_schools = $wpdb->prefix . 'mtech_shortcodes_schools';
    $sql_schools = "CREATE TABLE $table_schools (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY name (name)
    ) $charset_collate;";
    dbDelta( $sql_schools );

    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';
    $sql_programs = "CREATE TABLE $table_programs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        school_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        slug varchar(100) NOT NULL,
        shortcodes_program_id varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY school_id (school_id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    dbDelta( $sql_programs );


    // id: unique key for shortcode record
    // program_id: references mtech_shortcodes_programs.id
    // type: e.g. "cost", "registration", "default", "prereqs"
    // field: field key from the Coursedog API response, e.g. "Wx9fb"
    $table_shortcode_items = $wpdb->prefix . 'mtech_shortcodes_shortcodes';
    $sql_shortcodes = "CREATE TABLE $table_shortcode_items (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        program_id mediumint(9) NOT NULL,
        type varchar(255) NOT NULL,
        field varchar(255) NOT NULL,
        search tinyint(1) NOT NULL DEFAULT 0,
        search_query varchar(255),
        effective_dates_range varchar(255),
        PRIMARY KEY  (id),
        UNIQUE KEY program_type (program_id, type),
        KEY program_id (program_id)
    ) $charset_collate;";
    dbDelta( $sql_shortcodes );

    mtech_shortcodes_seed_schools();
    mtech_shortcodes_seed_programs();
}

function mtech_shortcodes_seed_schools() {
    global $wpdb;
    $table_schools = $wpdb->prefix . 'mtech_shortcodes_schools';

    $schools = array(
        'Apprenticeships',
        'Healthcare',
        'Services',
        'Technology',
        'Trades',
    );

    foreach ($schools as $school_name) {
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_schools WHERE name = %s",
                $school_name
            )
        );

        if (!$exists) {
            $wpdb->insert(
                $table_schools,
                array('name' => $school_name),
                array('%s')
            );
        }
    }
}

function mtech_shortcodes_seed_programs() {
    global $wpdb;
    $table_schools = $wpdb->prefix . 'mtech_shortcodes_schools';
    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';

    $programs = array(
        'Electrical Apprenticeship' => 'Apprenticeships',
        'Advanced EMT'              => 'Healthcare',
        'Artisan Baking'            => 'Services',
        'Data Technology'           => 'Technology',
        'Automation Technology'     => 'Trades',
    );

    foreach ($programs as $program_name => $school_name) {
        $school_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_schools WHERE name = %s",
                $school_name
            )
        );

        if (!$school_id) {
            // School not found — skip rather than insert a broken reference
            continue;
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_programs WHERE name = %s AND school_id = %d",
                $program_name,
                $school_id
            )
        );

        if (!$exists) {
            $wpdb->insert(
                $table_programs,
                array(
                    'name'      => $program_name,
                    'school_id' => $school_id,
                ),
                array('%s', '%d')
            );
        }
    }
}

function mtech_shortcodes_db_get_program_by_slug($slug) {
    global $wpdb;
    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';

    $program = $wpdb->get_row($wpdb->prepare(
        "SELECT id, shortcodes_program_id FROM $table_programs WHERE slug = %s",
        $slug
    ));

    return $program; // null if not found, otherwise an object with ->id and ->shortcodes_program_id
}

function mtech_shortcodes_db_get_shortcode($program_id, $type) {
    global $wpdb;
    $table_shortcode_items = $wpdb->prefix . 'mtech_shortcodes_shortcodes';

    $shortcode_row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_shortcode_items WHERE program_id = %d AND type = %s",
        $program_id,
        $type
    ));

    return $shortcode_row; // null if not found
}
