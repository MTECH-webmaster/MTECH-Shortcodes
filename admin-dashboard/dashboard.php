<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_enqueue_dashboard_script($hook) {
    // Only load on the top level mtech shortcodes plugin page
    if ($hook !== 'toplevel_page_mtech-shortcodes') {
        return;
    }

    wp_enqueue_style(
        'mtech-shortcodes-dashboard',
        plugin_dir_url(__FILE__) . 'dashboard.css',
        array(), // dependencies
        '1.0'
    );

    wp_enqueue_script(
        'mtech-shortcodes-dashboard',
        plugin_dir_url(__FILE__) . 'dashboard.js',
        array(), // dependencies, e.g. array('jquery') if needed
        '1.0',
        true // load in footer
    );

    // Shortcodes tab styles
    wp_enqueue_style(
        'mtech-shortcodes-tab-schedules',
        plugin_dir_url(__FILE__) . 'tabs/tab-schedules/tab-schedules.css',
        array(),
        filemtime(__DIR__ . '/tabs/tab-schedules/tab-schedules.css'),
        // changed from '1.0' to filetime so that old versions don't get cached.
    );

    wp_enqueue_script(
        'mtech-shortcodes-tab-schedules',
        plugin_dir_url(__FILE__) . 'tabs/tab-schedules/tab-schedules.js',
        array(),
        '1.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'mtech_shortcodes_enqueue_dashboard_script');

function display_shortcodes_admin_dashboard() {
    if (!current_user_can('edit_mtech_shortcodes')) {
        return;
    }

    // Close PHP
    ?>

    <div class="wp-tabs-wrapper">
        <!-- Tab Navigation Links -->
        <nav class="nav-tab-wrapper">
            <a href="#tab-general" class="nav-tab nav-tab-active" data-tab="general">Instructions</a>
            <a href="#tab-advanced" class="nav-tab" data-tab="advanced">Schedules</a>
        </nav>

        <!-- Tab Content Sections -->
        <div class="tab-content-container">
            <div id="tab-general" class="tab-content active">
                <?php require __DIR__ . '/tabs/tab-instructions/tab-instructions.php'; ?>
            </div>

            <div id="tab-advanced" class="tab-content">
                <?php require __DIR__ . '/tabs/tab-schedules/tab-schedules.php'; ?>
            </div>
        </div>
    </div>

    <?php
    // Open PHP
}