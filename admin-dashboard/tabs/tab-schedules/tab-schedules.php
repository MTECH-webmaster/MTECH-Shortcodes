<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$table_schools    = $wpdb->prefix . 'mtech_shortcodes_schools';
$table_programs   = $wpdb->prefix . 'mtech_shortcodes_programs';
$table_schedules = $wpdb->prefix . 'mtech_shortcodes_schedules';
$schools = $wpdb->get_results("SELECT id, name FROM $table_schools ORDER BY name ASC");
?>
<h2>Program Schedules</h2>
<!-- Saved notice -->
<?php if (isset($_GET['mtech_saved']) && $_GET['mtech_saved'] === '1') :
    $added   = isset($_GET['mtech_added'])   ? absint($_GET['mtech_added'])   : 0;
    $updated = isset($_GET['mtech_updated']) ? absint($_GET['mtech_updated']) : 0;
    $removed = isset($_GET['mtech_removed']) ? absint($_GET['mtech_removed']) : 0;

    $parts = array();
    if ($added)   { $parts[] = $added . ' added'; }
    if ($updated) { $parts[] = $updated . ' updated'; }
    if ($removed) { $parts[] = $removed . ' deleted'; }
    $message = $parts ? 'Schedule saved: ' . implode(', ', $parts) . '.' : 'Schedule saved. No changes.';
?>
    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
<?php endif; ?>
<!-- End saved notice -->
<?php if (isset($_GET['mtech_deleted']) && $_GET['mtech_deleted'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>Shortcode deleted.</p></div>
<?php endif; ?>
<?php if (isset($_GET['mtech_program_added']) && $_GET['mtech_program_added'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>Program added.</p></div>
<?php endif; ?>
<?php if (isset($_GET['mtech_program_removed']) && $_GET['mtech_program_removed'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>Program removed.</p></div>
<?php endif; ?>
<?php if (isset($_GET['mtech_program_updated']) && $_GET['mtech_program_updated'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>Program updated.</p></div>
<?php endif; ?>
<?php if (isset($_GET['mtech_shortcode_transient_deleted']) && $_GET['mtech_shortcode_transient_deleted'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>Shortcode transient deleted.</p></div>
<?php endif; ?>
<?php if (isset($_GET['mtech_shortcode_transient_deleted']) && $_GET['mtech_shortcode_transient_deleted'] === '0') : ?>
    <div class="notice notice-warning is-dismissible"><p>Shortcode transient not found.</p></div>
<?php endif; ?>
<ul id="mtech-shortcodes-tree">
<?php foreach ($schools as $school) :
    $programs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name FROM $table_programs WHERE school_id = %d ORDER BY name ASC",
            $school->id
        )
    );
?>
    <li>
        <span class="mtech-caret" data-type="school"><?php echo esc_html($school->name); ?></span>
        <ul class="mtech-nested">
            <?php foreach ($programs as $program) :
                $schedule_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, locations, start_date, hours, days
                         FROM $table_schedules
                         WHERE program_id = %d
                         ORDER BY start_date ASC",
                        $program->id
                    )
                );
            ?>
                <li>
                    <div class="mtech-program-row">
                        <span class="mtech-caret" data-type="program"><?php echo esc_html($program->name); ?></span>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-program-form-delete" onsubmit="return confirm('Remove this program? All of its shortcodes will also be deleted. This cannot be undone.');">
                            <input type="hidden" name="action" value="mtech_shortcodes_remove_program">
                            <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                            <?php wp_nonce_field('mtech_shortcodes_remove_program_' . $program->id, 'mtech_shortcodes_remove_program_nonce'); ?>
                            <button type="submit" class="button-link mtech-remove-program-link">Remove Program</button>
                        </form>

                        <!-- Duplicate Program Button -->
                        <button type="button" 
                                    class="button-link mtech-remove-program-link" 
                                    onclick="document.getElementById('mtech-modal-<?php echo esc_attr($program->id); ?>').showModal();">
                                Duplicate
                        </button>

                        <!-- Duplicate Program Modal -->
                        <dialog id="mtech-modal-<?php echo esc_attr($program->id); ?>" class="mtech-custom-modal">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-program-form-delete">
                                <input type="hidden" name="action" value="mtech_shortcodes_duplicate_program">
                                <input type="hidden" name="school_id" value="<?php echo esc_attr($school->id); ?>">
                                <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                                <?php wp_nonce_field('mtech_shortcodes_duplicate_program_' . $program->id, 'mtech_shortcodes_duplicate_program_nonce'); ?>

                                <p>
                                    <label>
                                        Program Name<br>
                                        <input class="mtech-shortcode-input-margin-top" type="text" name="name" value="" required>
                                    </label>
                                </p>

                                <button type="submit" class="button-link mtech-remove-program-link">Duplicate</button>
                            </form>
                        </dialog>
                    </div>
                    


                    <!-- Bulk schedule item edit -->
                    <ul class="mtech-nested">
                        <li class="mtech-bulk-edit-section">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-bulk-form">
                                <input type="hidden" name="action" value="mtech_shortcodes_save_schedules_bulk">
                                <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                                <?php wp_nonce_field('mtech_shortcodes_save_schedules_bulk_' . $program->id, 'mtech_schedules_bulk_nonce'); ?>

                                <?php foreach ($schedule_rows as $schedule_item) : ?>
                                    <div class="mtech-shortcode-row">
                                        <input type="hidden" name="schedules[<?php echo esc_attr($schedule_item->id); ?>][id]" value="<?php echo esc_attr($schedule_item->id); ?>">
                                        <p>
                                            <label>
                                                Locations<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="schedules[<?php echo esc_attr($schedule_item->id); ?>][locations]" value="<?php echo esc_attr($schedule_item->locations); ?>">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                Start date<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="schedules[<?php echo esc_attr($schedule_item->id); ?>][start_date]" value="<?php echo esc_attr($schedule_item->start_date); ?>">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                Hours<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="schedules[<?php echo esc_attr($schedule_item->id); ?>][hours]" value="<?php echo esc_attr($schedule_item->hours); ?>">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                Days<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="schedules[<?php echo esc_attr($schedule_item->id); ?>][days]" value="<?php echo esc_attr($schedule_item->days); ?>">
                                            </label>
                                        </p>
                                        <p>
                                            <label class="mtech-row-delete">
                                                <input type="checkbox" name="delete_schedules[]" value="<?php echo esc_attr($schedule_item->id); ?>">
                                                Delete this row
                                            </label>
                                        </p>
                                    </div>
                                <?php endforeach; ?>

                                <div class="mtech-new-rows" data-next-index="0"></div>

                                <?php if (!empty($schedule_rows)) : ?>
                                    <p class="mtech-bulk-save-row">
                                        <button type="button" class="button mtech-add-row">Add schedule row</button>
                                        <button type="submit" class="button button-primary">Save <?php echo esc_html($program->name); ?> schedule</button>
                                    </p>
                                <?php endif; ?>
                            </form>

                            <!-- Per-row delete / delete-transient actions, outside the bulk form -->
                            <div class="mtech-shortcode-row-actions-wrapper">
                                <?php foreach ($schedule_rows as $schedule_item) : ?>
                                    <div class="mtech-shortcode-row-actions">
                                        <span class="mtech-row-actions-label"><?php echo esc_html($schedule_item->type); ?></span>

                                        <div class="mtech-shortcode-row-actions-forms">
                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-form-delete">
                                                <input type="hidden" name="action" value="mtech_shortcodes_delete_shortcode_transient">
                                                <input type="hidden" name="program_slug" value="<?php echo esc_attr($program->slug); ?>">
                                                <input type="hidden" name="shortcode_type" value="<?php echo esc_attr($schedule_item->type); ?>">
                                                <?php wp_nonce_field('mtech_shortcodes_delete_shortcode_transient_' . $program->slug . $schedule_item->type, 'mtech_shortcodes_delete_nonce'); ?>
                                                <button type="submit" class="button button-secondary mtech-shortcode-input-margin-top">Delete Transient</button>
                                            </form>

                                            <!-- Schedule row delete button was removed from here -->

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>


                        <!-- Add new shortcode for this program -->
                            <li class="li-new-shortcode-section">
                                <p><strong>Add new schedule item</strong></p>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-form mtech-shortcode-form-new">
                                    <input type="hidden" name="action" value="mtech_shortcodes_save_schedule">
                                    <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                                    <input type="hidden" name="shortcode_id" value="">
                                    <?php wp_nonce_field('mtech_shortcodes_save_schedule_' . $program->id, 'mtech_schedule_nonce'); ?>
                                    <p>
                                        <label>
                                            Locations<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="locations" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            Start date<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="start_date" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            Hours<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="hours" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            Days<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="days" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <button type="submit" class="button button-primary">Add Schedule item</button>
                                    </p>
                                </form>
                            </li>


                        </li>
                    </ul>
        <!-- END MOST RECENT UPDATES -->

                </li>
            <?php endforeach; ?>
            <!-- Add new program for this school -->
            <li class="li-new-program-section">
                <p><strong>Add new program</strong></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-program-form-new">
                    <input type="hidden" name="action" value="mtech_shortcodes_add_program">
                    <input type="hidden" name="school_id" value="<?php echo esc_attr($school->id); ?>">
                    <?php wp_nonce_field('mtech_shortcodes_add_program_' . $school->id, 'mtech_shortcodes_add_program_nonce'); ?>
                    
                    <p>
                        <label>
                            Program Name<br>
                            <input class="mtech-shortcode-input-margin-top" type="text" name="name" value="" required>
                        </label>
                    </p>

                    <p>
                        <button type="submit" class="button button-primary">Add Program</button>
                    </p>
                </form>
            </li>
        </ul>
    </li>
<?php endforeach; ?>
</ul>

<template id="mtech-new-row-template">
    <div class="mtech-shortcode-row mtech-shortcode-row-new">
        <p>
            <label>
                Locations<br>
                <input class="mtech-shortcode-input-margin-top" type="text" name="new_schedules[__INDEX__][locations]" value="">
            </label>
        </p>
        <p>
            <label>
                Start date<br>
                <input class="mtech-shortcode-input-margin-top" type="text" name="new_schedules[__INDEX__][start_date]" value="">
            </label>
        </p>
        <p>
            <label>
                Hours<br>
                <input class="mtech-shortcode-input-margin-top" type="text" name="new_schedules[__INDEX__][hours]" value="">
            </label>
        </p>
        <p>
            <label>
                Days<br>
                <input class="mtech-shortcode-input-margin-top" type="text" name="new_schedules[__INDEX__][days]" value="">
            </label>
        </p>
        <p>
            <button type="button" class="button-link mtech-remove-new-row">Remove this row</button>
        </p>
    </div>
</template>