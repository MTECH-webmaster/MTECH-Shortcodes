<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_save_schedule_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $program_id   = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;
    $shortcode_id = isset($_POST['shortcode_id']) && $_POST['shortcode_id'] !== '' ? absint($_POST['shortcode_id']) : 0;

    if (!$program_id || !isset($_POST['mtech_schedule_nonce']) ||
        !wp_verify_nonce($_POST['mtech_schedule_nonce'], 'mtech_shortcodes_save_schedule_' . $program_id)) {
        wp_die('Invalid request', 400);
    }

    global $wpdb;
    $table_programs   = $wpdb->prefix . 'mtech_shortcodes_programs';
    $table_schedules = $wpdb->prefix . 'mtech_shortcodes_schedules';

    // Confirm the program actually exists before attaching a shortcode to it
    $program_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE id = %d", $program_id)
    );
    if (!$program_exists) {
        wp_die('Invalid program', 400);
    }

    $locations = isset($_POST['locations']) ? sanitize_text_field($_POST['locations']) : '';
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $hours = isset($_POST['hours']) ? sanitize_text_field($_POST['hours']) : '';
    $days = isset($_POST['days']) ? sanitize_text_field($_POST['days']) : '';

    // type and field are required columns (NOT NULL) — reject rather than
    // let $wpdb->insert() fail silently on a missing required value
    // if ($type === '' || $field === '') {
    //     wp_die('Type and Field are required', 400);
    // }

    $data = array(
        'program_id'             => $program_id,
        'locations'              => $locations,
        'start_date'             => $start_date,
        'hours'                  => $hours,
        'days'                   => $days,
    );

    $formats = array('%d', '%s', '%s', '%s', '%s');

    if ($shortcode_id) {
        // Updating an existing row
        $result = $wpdb->update(
            $table_schedules,
            $data,
            array('id' => $shortcode_id),
            $formats,
            array('%d')
        );
    } else {
        // Inserting a new row — check the (program_id, id) unique constraint
        // ourselves first, so we can show a clear error rather than a raw DB failure
        $duplicate = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_schedules WHERE program_id = %d AND id = %d",
                $program_id,
                $shortcode_id
            )
        );
        if ($duplicate) {
            wp_die('A schedule item with this ID already exists for this program.', 400);
        }

        $result = $wpdb->insert($table_schedules, $data, $formats);
    }

    if ($result === false) {
        wp_die('Database error while saving shortcode.', 500);
    }

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-schedules');
    $redirect = add_query_arg('mtech_saved', '1', remove_query_arg('mtech_saved', $redirect));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_save_schedule', 'mtech_shortcodes_save_schedule_handler');

function mtech_shortcodes_save_schedules_bulk_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $program_id = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;

    if (!$program_id || !isset($_POST['mtech_schedules_bulk_nonce']) ||
        !wp_verify_nonce($_POST['mtech_schedules_bulk_nonce'], 'mtech_shortcodes_save_schedules_bulk_' . $program_id)) {
        wp_die('Invalid request', 400);
    }

    global $wpdb;
    $table_programs   = $wpdb->prefix . 'mtech_shortcodes_programs';
    $table_schedules = $wpdb->prefix . 'mtech_shortcodes_schedules';

    $program_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE id = %d", $program_id)
    );
    if (!$program_exists) {
        wp_die('Invalid program', 400);
    }

    $submitted = isset($_POST['schedules']) && is_array($_POST['schedules']) ? $_POST['schedules'] : array();

    if (empty($submitted)) {
        // Nothing submitted — treat as a no-op rather than an error
        // mtech_shortcodes_redirect_with_flag('mtech_saved');
    }

    // Fetch the IDs actually belonging to this program, so a tampered POST
    // can't update another program's shortcode rows
    $owned_ids = $wpdb->get_col(
        $wpdb->prepare("SELECT id FROM $table_schedules WHERE program_id = %d", $program_id)
    );
    $owned_ids = array_map('absint', $owned_ids);

    // Validate everything before writing anything
    $updates = array();
    $seen_types = array();

    foreach ($submitted as $row) {
        $shortcode_id = isset($row['id']) ? absint($row['id']) : 0;

        if (!$shortcode_id || !in_array($shortcode_id, $owned_ids, true)) {
            wp_die('Invalid shortcode reference', 400);
        }

        $locations  = isset($row['locations']) ? sanitize_text_field($row['locations']) : '';
        $start_date = isset($row['start_date']) ? sanitize_text_field($row['start_date']) : '';
        $hours      = isset($row['hours']) ? sanitize_text_field($row['hours']) : '';
        $days       = isset($row['days']) ? sanitize_text_field($row['days']) : '';

        // if ($locations === '' || $start_date === '') {
        //     wp_die('Locations and start date are required for every schedule item.', 400);
        // }

        // The (program_id, locations) unique key means two rows in this same
        // submission can't both claim the same locations
        // if (in_array($locations, $seen_types, true)) {
        //     wp_die('Duplicate locations "' . esc_html($locations) . '" — each shortcode locations must be unique within a program.', 400);
        // }
        // $seen_types[] = $type;

        $updates[$shortcode_id] = array(
            'locations'                  => $locations,
            'start_date'                 => $start_date,
            'hours'                     => $hours,
            'days'                      => $days,
        );
    }

    $formats = array('%s', '%s', '%s', '%s');

    $wpdb->query('START TRANSACTION');

    foreach ($updates as $shortcode_id => $data) {
        $result = $wpdb->update(
            $table_schedules,
            $data,
            array('id' => $shortcode_id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            wp_die('Database error while saving shortcodes.', 500);
        }
    }

    $wpdb->query('COMMIT');

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-schedules');
    $redirect = add_query_arg('mtech_saved', '1', remove_query_arg(array('mtech_saved', 'mtech_deleted', 'mtech_program_added', 'mtech_program_removed', 'mtech_program_updated'), $redirect));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_save_schedules_bulk', 'mtech_shortcodes_save_schedules_bulk_handler');

function mtech_shortcodes_delete_shortcode_transient_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $program_slug   = isset($_POST['program_slug']) ? sanitize_text_field($_POST['program_slug']) : 0;
    $shortcode_type = isset($_POST['shortcode_type']) ? sanitize_text_field($_POST['shortcode_type']) : 0;

    if (!$shortcode_type || !isset($_POST['mtech_shortcodes_delete_nonce']) ||
        !wp_verify_nonce($_POST['mtech_shortcodes_delete_nonce'], 'mtech_shortcodes_delete_shortcode_transient_' . $program_slug . $shortcode_type)) {
        wp_die('Invalid request', 400);
    }

    $was_deleted = mtech_shortcodes_delete_shortcode_transient($program_slug, $shortcode_type);

    if ($was_deleted === true) {
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
        $redirect = add_query_arg('mtech_shortcode_transient_deleted', '1', remove_query_arg(array('mtech_saved', 'mtech_deleted'), $redirect));
        }
    else {
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
        $redirect = add_query_arg('mtech_shortcode_transient_deleted', '0', remove_query_arg(array('mtech_saved', 'mtech_deleted'), $redirect));
    }

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_delete_shortcode_transient', 'mtech_shortcodes_delete_shortcode_transient_handler');

function mtech_shortcodes_delete_shortcode_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $program_id   = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;
    $shortcode_id = isset($_POST['shortcode_id']) ? absint($_POST['shortcode_id']) : 0;

    if (!$shortcode_id || !isset($_POST['mtech_shortcodes_delete_nonce']) ||
        !wp_verify_nonce($_POST['mtech_shortcodes_delete_nonce'], 'mtech_shortcodes_delete_shortcode_' . $shortcode_id)) {
        wp_die('Invalid request', 400);
    }

    global $wpdb;
    $table_shortcodes = $wpdb->prefix . 'mtech_shortcodes_schedules';

    $result = $wpdb->delete(
        $table_shortcodes,
        array('id' => $shortcode_id),
        array('%d')
    );

    if ($result === false) {
        wp_die('Database error while deleting shortcode.', 500);
    }

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
    $redirect = add_query_arg('mtech_deleted', '1', remove_query_arg(array('mtech_saved', 'mtech_deleted'), $redirect));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_delete_shortcode', 'mtech_shortcodes_delete_shortcode_handler');

function mtech_shortcodes_add_program_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $school_id = isset($_POST['school_id']) ? absint($_POST['school_id']) : 0;

    if (!$school_id || !isset($_POST['mtech_shortcodes_add_program_nonce']) ||
        !wp_verify_nonce($_POST['mtech_shortcodes_add_program_nonce'], 'mtech_shortcodes_add_program_' . $school_id)) {
        wp_die('Invalid request', 400);
    }

    global $wpdb;
    $table_schools  = $wpdb->prefix . 'mtech_shortcodes_schools';
    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';

    // Confirm the school actually exists before attaching a program to it
    $school_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_schools WHERE id = %d", $school_id)
    );
    if (!$school_exists) {
        wp_die('Invalid school', 400);
    }

    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    if ($name === '') {
        wp_die('Program name is required', 400);
    }

    $slug = isset($_POST['slug']) ? mtech_shortcodes_sanitize_program_slug($_POST['slug']) : '';
    if ($slug === '') {
        wp_die('A valid program slug is required', 400);
    }

    $duplicate_slug = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE slug = %s", $slug)
    );
    if ($duplicate_slug) {
        wp_die('This slug is already in use by another program.', 400);
    }

    $shortcodes_program_id = isset($_POST['shortcodes_program_id']) ? sanitize_text_field($_POST['shortcodes_program_id']) : '';

    $data = array(
        'school_id' => $school_id,
        'name'      => $name,
        'slug'      => $slug,
        // Store NULL rather than an empty string when no Coursedog ID is given,
        // so the column's UNIQUE-among-non-null behavior stays meaningful
        'shortcodes_program_id' => $shortcodes_program_id !== '' ? $shortcodes_program_id : null,
    );
    $formats = array('%d', '%s', '%s', $shortcodes_program_id !== '' ? '%s' : null);

    $result = $wpdb->insert($table_programs, $data, $formats);

    if ($result === false) {
        wp_die('Database error while adding program.', 500);
    }

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
    $redirect = add_query_arg(
        'mtech_program_added', '1',
        remove_query_arg(array('mtech_saved', 'mtech_deleted', 'mtech_program_added', 'mtech_program_removed'), $redirect)
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_add_program', 'mtech_shortcodes_add_program_handler');

function mtech_shortcodes_duplicate_program_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    // school_id and program_id of program being duplicated
    $school_id = isset($_POST['school_id']) ? absint($_POST['school_id']) : 0;
    $program_id = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;

    if (!$school_id || !$program_id || !isset($_POST['mtech_shortcodes_duplicate_program_nonce']) ||
        !wp_verify_nonce($_POST['mtech_shortcodes_duplicate_program_nonce'], 'mtech_shortcodes_duplicate_program_' . $program_id)) {
        wp_die('Invalid request', 400);
    }

    // DB tables
    global $wpdb;
    $table_schools  = $wpdb->prefix . 'mtech_shortcodes_schools';
    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';

    // Confirm the school actually exists before attaching a program to it
    $school_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_schools WHERE id = %d", $school_id)
    );
    if (!$school_exists) {
        wp_die('Invalid school', 400);
    }

    // name, slug, and shortcodes_program_id for new program
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    if ($name === '') {
        wp_die('Program name is required', 400);
    }

    $slug = isset($_POST['slug']) ? mtech_shortcodes_sanitize_program_slug($_POST['slug']) : '';
    if ($slug === '') {
        wp_die('A valid program slug is required', 400);
    }

    $duplicate_slug = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE slug = %s", $slug)
    );
    if ($duplicate_slug) {
        wp_die('This slug is already in use by another program.', 400);
    }

    $shortcodes_program_id = isset($_POST['shortcodes_program_id']) ? sanitize_text_field($_POST['shortcodes_program_id']) : '';

    // search, search_query, and effective_dates_range for new program's shortcodes
    $search = !empty($_POST['search']) ? 1 : 0;
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';
    $effective_dates_range = isset($_POST['effective_dates_range']) ? sanitize_text_field($_POST['effective_dates_range']) : '';


    /////////////////////////////
    //  START ADD NEW PROGRAM  //
    /////////////////////////////

    // assemble data for new program row and insert it into the DB
    $data = array(
        'school_id' => $school_id,
        'name'      => $name,
        'slug'      => $slug,
        'shortcodes_program_id' => $shortcodes_program_id !== '' ? $shortcodes_program_id : null,
    );
    $formats = array('%d', '%s', '%s', $shortcodes_program_id !== '' ? '%s' : null);

    $result = $wpdb->insert($table_programs, $data, $formats);

    if ($result === false) {
        wp_die('Database error while adding program.', 500);
    }

    // Get the ID of the newly inserted program row
    $new_program_id = $wpdb->insert_id;

    ///////////////////////////
    //  END ADD NEW PROGRAM  //
    ///////////////////////////

    //////////////////////////////////
    //  START DUPLICATE SHORTCODES  //
    //////////////////////////////////

    $table_shortcodes = $wpdb->prefix . 'mtech_shortcodes_schedules';

    $shortcode_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, type, field, search, search_query, effective_dates_range
                FROM $table_shortcodes
                WHERE program_id = %d
                ORDER BY type ASC",
            $program_id
        )
    );

    foreach($shortcode_rows as $row) {
        $data = array(
            'program_id' => $new_program_id,
            'type' => $row->type,
            'field' => $row->field,
            'search' => $search,
            'search_query' => $search_query,
            'effective_dates_range' => $effective_dates_range,
        );
        $formats = array('%d', '%s', '%s', '%d', '%s', '%s');

        $result = $wpdb->insert($table_shortcodes, $data, $formats);

        if ($result === false) {
            wp_die('Database error while duplicating shortcodes.', 500);
        }
    }

    ////////////////////////////////
    //  END DUPLICATE SHORTCODES  //
    ////////////////////////////////

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
    $redirect = add_query_arg(
        'mtech_program_added', '1',
        remove_query_arg(array('mtech_saved', 'mtech_deleted', 'mtech_program_added', 'mtech_program_removed'), $redirect)
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_duplicate_program', 'mtech_shortcodes_duplicate_program_handler');

function mtech_shortcodes_remove_program_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $program_id = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;

    if (!$program_id || !isset($_POST['mtech_shortcodes_remove_program_nonce']) ||
        !wp_verify_nonce($_POST['mtech_shortcodes_remove_program_nonce'], 'mtech_shortcodes_remove_program_' . $program_id)) {
        wp_die('Invalid request', 400);
    }

    global $wpdb;
    $table_programs   = $wpdb->prefix . 'mtech_shortcodes_programs';
    $table_shortcodes = $wpdb->prefix . 'mtech_shortcodes_schedules';

    // Cascade: remove this program's shortcodes first, since there's no
    // database-level foreign key to do this automatically. Without this step,
    // deleting the program would leave orphaned shortcode rows behind.
    $wpdb->delete($table_shortcodes, array('program_id' => $program_id), array('%d'));

    $result = $wpdb->delete($table_programs, array('id' => $program_id), array('%d'));

    if ($result === false) {
        wp_die('Database error while removing program.', 500);
    }

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
    $redirect = add_query_arg(
        'mtech_program_removed', '1',
        remove_query_arg(array('mtech_saved', 'mtech_deleted', 'mtech_program_added', 'mtech_program_removed'), $redirect)
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_remove_program', 'mtech_shortcodes_remove_program_handler');

function mtech_shortcodes_sanitize_program_slug($raw_slug) {
    // sanitize_title() is WordPress's built-in slug normalizer — same logic
    // used for post/page slugs. Turns "Automation Technology!" into "automation-technology".
    return sanitize_title($raw_slug);
}

function mtech_shortcodes_edit_program_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    $program_id = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;

    if (!$program_id || !isset($_POST['mtech_shortcodes_edit_program_nonce']) ||
        !wp_verify_nonce($_POST['mtech_shortcodes_edit_program_nonce'], 'mtech_shortcodes_edit_program_' . $program_id)) {
        wp_die('Invalid request', 400);
    }

    global $wpdb;
    $table_programs = $wpdb->prefix . 'mtech_shortcodes_programs';

    $program_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE id = %d", $program_id)
    );
    if (!$program_exists) {
        wp_die('Invalid program', 400);
    }

    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    if ($name === '') {
        wp_die('Program name is required', 400);
    }

    $slug = isset($_POST['slug']) ? mtech_shortcodes_sanitize_program_slug($_POST['slug']) : '';
    if ($slug === '') {
        wp_die('A valid program slug is required', 400);
    }

    // Confirm the slug isn't already used by a *different* program
    $duplicate = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $table_programs WHERE slug = %s AND id != %d",
            $slug,
            $program_id
        )
    );
    if ($duplicate) {
        wp_die('This slug is already in use by another program.', 400);
    }

    $shortcodes_program_id = isset($_POST['shortcodes_program_id']) ? sanitize_text_field($_POST['shortcodes_program_id']) : '';

    $data = array(
        'name'                 => $name,
        'slug'                 => $slug,
        'shortcodes_program_id' => $shortcodes_program_id !== '' ? $shortcodes_program_id : null,
    );
    $formats = array('%s', '%s', $shortcodes_program_id !== '' ? '%s' : null);

    $result = $wpdb->update($table_programs, $data, array('id' => $program_id), $formats, array('%d'));

    if ($result === false) {
        wp_die('Database error while updating program.', 500);
    }

    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
    $redirect = add_query_arg(
        'mtech_program_updated', '1',
        remove_query_arg(array('mtech_saved', 'mtech_deleted', 'mtech_program_added', 'mtech_program_removed', 'mtech_program_updated'), $redirect)
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_edit_program', 'mtech_shortcodes_edit_program_handler');