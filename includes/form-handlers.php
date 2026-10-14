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

// Sanitize one schedule row from $_POST. Returns only the editable columns.
function mtech_shortcodes_sanitize_schedule_row($row) {
    $fields = array('locations', 'start_date', 'hours', 'days');
    $clean  = array();

    foreach ($fields as $field) {
        $clean[$field] = isset($row[$field]) ? sanitize_text_field(wp_unslash($row[$field])) : '';
    }

    return $clean;
}

function mtech_shortcodes_save_schedules_bulk_handler() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        wp_die('Unauthorized', 403);
    }

    // VALIDATE REQUEST
    $program_id = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;
    if (!$program_id || !isset($_POST['mtech_schedules_bulk_nonce']) ||
        !wp_verify_nonce($_POST['mtech_schedules_bulk_nonce'], 'mtech_shortcodes_save_schedules_bulk_' . $program_id)) {
        wp_die('Invalid request', 400);
    }

    // DB TABLES
    global $wpdb;
    $table_programs  = $wpdb->prefix . 'mtech_shortcodes_programs';
    $table_schedules = $wpdb->prefix . 'mtech_shortcodes_schedules';

    // CHECK IF PROGRAM EXISTS
    $program_exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_programs WHERE id = %d", $program_id)
    );
    if (!$program_exists) {
        wp_die('Invalid program', 400);
    }

    // SET REQUIRED COLUMNS - Columns that must be non-empty on every saved row (e.g. array('locations', 'start_date')).
    $required_fields = array();

    // ARRAY VALUES FROM $_POST
    $submitted_existing = isset($_POST['schedules']) && is_array($_POST['schedules']) ? $_POST['schedules'] : array();
    $submitted_new      = isset($_POST['new_schedules']) && is_array($_POST['new_schedules']) ? $_POST['new_schedules'] : array();
    $submitted_delete   = isset($_POST['delete_schedules']) && is_array($_POST['delete_schedules']) ? $_POST['delete_schedules'] : array();

    // RETRIEVE IDs - IDs that actually belong to this program
    $owned_ids = $wpdb->get_col(
        $wpdb->prepare("SELECT id FROM $table_schedules WHERE program_id = %d", $program_id)
    );
    $owned_ids = array_map('absint', $owned_ids);

    ///////////////////////////////////////////////////////////////////////
    // ---------- VALIDATE EVERYTHING BEFORE WRITING ANYTHING ---------- //
    ///////////////////////////////////////////////////////////////////////

    // Deletes: keep only IDs this program owns. An ID that isn't owned is either
    // already deleted (e.g. by another user) or tampered with. Deleting it would be
    // a no-op either way, so we ignore it rather than fail the whole save.
    $delete_ids = array_values(array_unique(array_intersect(
        array_map('absint', $submitted_delete),
        $owned_ids
    )));
    $delete_lookup = array_flip($delete_ids); // for fast isset() checks

    // Existing rows -> updates (rows marked for deletion are skipped: delete wins)
    $updates = array();
    foreach ($submitted_existing as $row) {
        if (!is_array($row)) {
            wp_die('Malformed schedule data.', 400);
        }

        $schedule_id = isset($row['id']) ? absint($row['id']) : 0;
        if (!$schedule_id || !in_array($schedule_id, $owned_ids, true)) {
            wp_die('Invalid schedule reference.', 400);
        }

        // Skips current iteration if it is marked to be deleted.
        if (isset($delete_lookup[$schedule_id])) {
            continue;
        }

        $clean_row = mtech_shortcodes_sanitize_schedule_row($row);

        foreach ($required_fields as $field) {
            if ($clean_row[$field] === '') {
                wp_die('Missing required field "' . esc_html($field) . '" on an existing schedule item.', 400);
            }
        }

        $updates[$schedule_id] = $clean_row;
    }

    // New rows -> inserts (fully blank rows are skipped)
    $inserts = array();
    foreach ($submitted_new as $row) {
        if (!is_array($row)) {
            continue;
        }

        $clean_row = mtech_shortcodes_sanitize_schedule_row($row);

        if (implode('', $clean_row) === '') {
            continue;
        }

        foreach ($required_fields as $field) {
            if ($clean_row[$field] === '') {
                wp_die('Missing required field "' . esc_html($field) . '" on a new schedule item.', 400);
            }
        }

        $inserts[] = $clean_row;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // ---------- WRITE EVERYTHING IN ONE TRANSACTION: DELETES, UPDATES, INSERTS ----------  //
    ///////////////////////////////////////////////////////////////////////////////////////////

    $formats_update = array('%s', '%s', '%s', '%s');
    $formats_insert = array('%d', '%s', '%s', '%s', '%s');

    $removed_count = 0;
    $updated_count = 0;
    $added_count   = 0;

    $wpdb->query('START TRANSACTION');

    // Deletes first, so a unique key can be freed up before an insert reuses it
    foreach ($delete_ids as $schedule_id) {
        $result = $wpdb->delete(
            $table_schedules,
            array('id' => $schedule_id, 'program_id' => $program_id),
            array('%d', '%d')
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            wp_die('Database error while deleting schedule items.', 500);
        }

        $removed_count += (int) $result;
    }

    foreach ($updates as $schedule_id => $data) {
        $result = $wpdb->update(
            $table_schedules,
            $data,
            array('id' => $schedule_id, 'program_id' => $program_id),
            $formats_update,
            array('%d', '%d')
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            wp_die('Database error while updating schedule items.', 500);
        }

        // update() returns 0 when nothing changed, so this counts only rows that really changed
        if ($result > 0) {
            $updated_count++;
        }
    }

    foreach ($inserts as $data) {
        $result = $wpdb->insert(
            $table_schedules,
            array_merge(array('program_id' => $program_id), $data),
            $formats_insert
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            wp_die('Database error while adding schedule items.', 500);
        }

        $added_count++;
    }

    $wpdb->query('COMMIT');

    // REDIRECT
    $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-schedules');
    $redirect = remove_query_arg(
        array('mtech_saved', 'mtech_added', 'mtech_updated', 'mtech_removed', 'mtech_deleted', 'mtech_program_added', 'mtech_program_removed', 'mtech_program_updated'),
        $redirect
    );
    $redirect = add_query_arg(
        array(
            'mtech_saved'   => '1',
            'mtech_added'   => $added_count,
            'mtech_updated' => $updated_count,
            'mtech_removed' => $removed_count,
        ),
        $redirect
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mtech_shortcodes_save_schedules_bulk', 'mtech_shortcodes_save_schedules_bulk_handler');

// function mtech_shortcodes_delete_shortcode_transient_handler() {
//     if (!current_user_can('edit_mtech_shortcodes')) {
//         wp_die('Unauthorized', 403);
//     }

//     $program_slug   = isset($_POST['program_slug']) ? sanitize_text_field($_POST['program_slug']) : 0;
//     $shortcode_type = isset($_POST['shortcode_type']) ? sanitize_text_field($_POST['shortcode_type']) : 0;

//     if (!$shortcode_type || !isset($_POST['mtech_shortcodes_delete_nonce']) ||
//         !wp_verify_nonce($_POST['mtech_shortcodes_delete_nonce'], 'mtech_shortcodes_delete_shortcode_transient_' . $program_slug . $shortcode_type)) {
//         wp_die('Invalid request', 400);
//     }

//     $was_deleted = mtech_shortcodes_delete_shortcode_transient($program_slug, $shortcode_type);

//     if ($was_deleted === true) {
//         $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
//         $redirect = add_query_arg('mtech_shortcode_transient_deleted', '1', remove_query_arg(array('mtech_saved', 'mtech_deleted'), $redirect));
//         }
//     else {
//         $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
//         $redirect = add_query_arg('mtech_shortcode_transient_deleted', '0', remove_query_arg(array('mtech_saved', 'mtech_deleted'), $redirect));
//     }

//     wp_safe_redirect($redirect);
//     exit;
// }
// add_action('admin_post_mtech_shortcodes_delete_shortcode_transient', 'mtech_shortcodes_delete_shortcode_transient_handler');

// function mtech_shortcodes_delete_shortcode_handler() {
//     if (!current_user_can('edit_mtech_shortcodes')) {
//         wp_die('Unauthorized', 403);
//     }

//     $program_id   = isset($_POST['program_id']) ? absint($_POST['program_id']) : 0;
//     $shortcode_id = isset($_POST['shortcode_id']) ? absint($_POST['shortcode_id']) : 0;

//     if (!$shortcode_id || !isset($_POST['mtech_shortcodes_delete_nonce']) ||
//         !wp_verify_nonce($_POST['mtech_shortcodes_delete_nonce'], 'mtech_shortcodes_delete_shortcode_' . $shortcode_id)) {
//         wp_die('Invalid request', 400);
//     }

//     global $wpdb;
//     $table_shortcodes = $wpdb->prefix . 'mtech_shortcodes_schedules';

//     $result = $wpdb->delete(
//         $table_shortcodes,
//         array('id' => $shortcode_id),
//         array('%d')
//     );

//     if ($result === false) {
//         wp_die('Database error while deleting shortcode.', 500);
//     }

//     $redirect = wp_get_referer() ? wp_get_referer() : admin_url('options-general.php?page=mtech-shortcodes');
//     $redirect = add_query_arg('mtech_deleted', '1', remove_query_arg(array('mtech_saved', 'mtech_deleted'), $redirect));

//     wp_safe_redirect($redirect);
//     exit;
// }
// add_action('admin_post_mtech_shortcodes_delete_shortcode', 'mtech_shortcodes_delete_shortcode_handler');

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

    // $slug = isset($_POST['slug']) ? mtech_shortcodes_sanitize_program_slug($_POST['slug']) : '';
    // if ($slug === '') {
    //     wp_die('A valid program slug is required', 400);
    // }

    // $duplicate_slug = $wpdb->get_var(
    //     $wpdb->prepare("SELECT id FROM $table_programs WHERE slug = %s", $slug)
    // );
    // if ($duplicate_slug) {
    //     wp_die('This slug is already in use by another program.', 400);
    // }

    $data = array(
        'school_id' => $school_id,
        'name'      => $name,
    );
    $formats = array('%d', '%s');

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

    /////////////////////////////
    //  START ADD NEW PROGRAM  //
    /////////////////////////////

    // assemble data for new program row and insert it into the DB
    $data = array(
        'school_id' => $school_id,
        'name'      => $name,
    );
    $formats = array('%d', '%s');

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

    $table_schedules = $wpdb->prefix . 'mtech_shortcodes_schedules';

    $schedule_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, locations, start_date, hours, days
                FROM $table_schedules
                WHERE program_id = %d
                ORDER BY id ASC",
            $program_id
        )
    );

    foreach($schedule_rows as $row) {
        $data = array(
            'program_id' => $new_program_id,
            'locations' => $row->locations,
            'start_date' => $row->start_date,
            'hours' => $row->hours,
            'days' => $row->days,
        );
        $formats = array('%d', '%s', '%s', '%s', '%s');

        $result = $wpdb->insert($table_schedules, $data, $formats);

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
