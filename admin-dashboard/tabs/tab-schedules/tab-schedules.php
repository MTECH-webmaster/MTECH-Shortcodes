<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$table_schools    = $wpdb->prefix . 'mtech_shortcodes_schools';
$table_programs   = $wpdb->prefix . 'mtech_shortcodes_programs';
$table_shortcodes = $wpdb->prefix . 'mtech_shortcodes_items';
$schools = $wpdb->get_results("SELECT id, name FROM $table_schools ORDER BY name ASC");
?>
<h2>Program Schedules</h2>
<?php if (isset($_GET['mtech_saved']) && $_GET['mtech_saved'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>Shortcode values saved.</p></div>
<?php endif; ?>
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
            "SELECT id, name, slug, shortcodes_program_id FROM $table_programs WHERE school_id = %d ORDER BY name ASC",
            $school->id
        )
    );
?>
    <li>
        <span class="mtech-caret" data-type="school"><?php echo esc_html($school->name); ?></span>
        <ul class="mtech-nested">
            <?php foreach ($programs as $program) :
                $shortcode_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, type, field, search, search_query, effective_dates_range
                         FROM $table_shortcodes
                         WHERE program_id = %d
                         ORDER BY type ASC",
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
                                <p>
                                    <label>
                                        Program Slug<span class="mtech-optional">(letters, numbers, hyphens only)</span><br>
                                        <input class="mtech-shortcode-input-margin-top" type="text" name="slug" value="" required>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        Coursedog Program ID <span class="mtech-optional">(optional)</span><br>
                                        <input class="mtech-shortcode-input-margin-top" type="text" name="shortcodes_program_id" value="">
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        Search <span class="mtech-optional">(optional)</span><br>
                                        <input class="mtech-shortcode-input-margin-top" type="checkbox" name="search" value="1">
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        Search Query <span class="mtech-optional">(use spaces, not %20)</span><br>
                                        <input class="mtech-shortcode-input-margin-top" type="text" name="search_query" value="">
                                    </label>
                                </p>

                                <p>
                                    <label>
                                        Effective Dates Range<br>
                                        <input class="mtech-shortcode-input-margin-top" type="text" name="effective_dates_range" value="">
                                    </label>
                                </p>

                                <button type="submit" class="button-link mtech-remove-program-link">Duplicate</button>
                            </form>
                        </dialog>
                    </div>
                    


<!-- START MOST RECENT UPDATES -->
                    <ul class="mtech-nested">
                        <li class="mtech-bulk-edit-section">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-bulk-form">
                                <input type="hidden" name="action" value="mtech_shortcodes_save_shortcodes_bulk">
                                <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                                <?php wp_nonce_field('mtech_shortcodes_save_shortcodes_bulk_' . $program->id, 'mtech_shortcodes_bulk_nonce'); ?>

                                <?php foreach ($shortcode_rows as $shortcode) : ?>
                                    <div class="mtech-shortcode-row">
                                        <input type="hidden" name="shortcodes[<?php echo esc_attr($shortcode->id); ?>][id]" value="<?php echo esc_attr($shortcode->id); ?>">
                                        <p>
                                            <label>
                                                Type<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="shortcodes[<?php echo esc_attr($shortcode->id); ?>][type]" value="<?php echo esc_attr($shortcode->type); ?>">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                Field<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="shortcodes[<?php echo esc_attr($shortcode->id); ?>][field]" value="<?php echo esc_attr($shortcode->field); ?>">
                                            </label>
                                        </p>
                                        <p class="mtech-checkbox-field">
                                            <label>
                                                <input class="mtech-shortcode-input-margin-top" type="checkbox" name="shortcodes[<?php echo esc_attr($shortcode->id); ?>][search]" value="1" <?php checked((bool) $shortcode->search, true); ?>>
                                                Search
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                Search Query <span class="mtech-optional">(use spaces, not %20)</span><br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="shortcodes[<?php echo esc_attr($shortcode->id); ?>][search_query]" value="<?php echo esc_attr($shortcode->search_query); ?>">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                Effective Dates Range<br>
                                                <input class="mtech-shortcode-input-margin-top" type="text" name="shortcodes[<?php echo esc_attr($shortcode->id); ?>][effective_dates_range]" value="<?php echo esc_attr($shortcode->effective_dates_range); ?>">
                                            </label>
                                        </p>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (!empty($shortcode_rows)) : ?>
                                    <p class="mtech-bulk-save-row">
                                        <button type="submit" class="button button-primary">Save All Shortcodes</button>
                                    </p>
                                <?php endif; ?>
                            </form>

                            <!-- Per-row delete / delete-transient actions, outside the bulk form -->
                            <div class="mtech-shortcode-row-actions-wrapper">
                                <?php foreach ($shortcode_rows as $shortcode) : ?>
                                    <div class="mtech-shortcode-row-actions">
                                        <span class="mtech-row-actions-label"><?php echo esc_html($shortcode->type); ?></span>

                                        <div class="mtech-shortcode-row-actions-forms">
                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-form-delete">
                                                <input type="hidden" name="action" value="mtech_shortcodes_delete_shortcode_transient">
                                                <input type="hidden" name="program_slug" value="<?php echo esc_attr($program->slug); ?>">
                                                <input type="hidden" name="shortcode_type" value="<?php echo esc_attr($shortcode->type); ?>">
                                                <?php wp_nonce_field('mtech_shortcodes_delete_shortcode_transient_' . $program->slug . $shortcode->type, 'mtech_shortcodes_delete_nonce'); ?>
                                                <button type="submit" class="button button-secondary mtech-shortcode-input-margin-top">Delete Transient</button>
                                            </form>

                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-form-delete" onsubmit="return confirm('Delete this shortcode? This cannot be undone.');">
                                                <input type="hidden" name="action" value="mtech_shortcodes_delete_shortcode">
                                                <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                                                <input type="hidden" name="shortcode_id" value="<?php echo esc_attr($shortcode->id); ?>">
                                                <?php wp_nonce_field('mtech_shortcodes_delete_shortcode_' . $shortcode->id, 'mtech_shortcodes_delete_nonce'); ?>
                                                <button type="submit" class="button button-secondary mtech-shortcode-input-margin-top">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>


                        <!-- Add new shortcode for this program -->
                            <li class="li-new-shortcode-section">
                                <p><strong>Add new shortcode</strong></p>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-shortcode-form mtech-shortcode-form-new">
                                    <input type="hidden" name="action" value="mtech_shortcodes_save_shortcode">
                                    <input type="hidden" name="program_id" value="<?php echo esc_attr($program->id); ?>">
                                    <input type="hidden" name="shortcode_id" value="">
                                    <?php wp_nonce_field('mtech_shortcodes_save_shortcode_' . $program->id, 'mtech_shortcodes_nonce'); ?>
                                    <p>
                                        <label>
                                            Type<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="type" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            Field<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="field" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            <input class="mtech-shortcode-input-margin-top" type="checkbox" name="search" value="1">
                                            Search
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            Search Query <span class="mtech-optional">(use spaces, not %20)</span><br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="search_query" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            Effective Dates Range<br>
                                            <input class="mtech-shortcode-input-margin-top" type="text" name="effective_dates_range" value="">
                                        </label>
                                    </p>
                                    <p>
                                        <button type="submit" class="button button-primary">Add Shortcode</button>
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
                        <label>
                            Program Slug<span class="mtech-optional">(letters, numbers, hyphens only)</span><br>
                            <input class="mtech-shortcode-input-margin-top" type="text" name="slug" value="" required>
                        </label>
                    </p>
                    <p>
                        <label>
                            Coursedog Program ID <span class="mtech-optional">(optional)</span><br>
                            <input class="mtech-shortcode-input-margin-top" type="text" name="shortcodes_program_id" value="">
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