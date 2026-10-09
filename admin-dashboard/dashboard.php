<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_enqueue_dashboard_script($hook) {
    // Only load on our specific settings page
    if ($hook !== 'settings_page_mtech-shortcodes') {
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
        'mtech-shortcodes-tab-shortcodes',
        plugin_dir_url(__FILE__) . 'tabs/tab-shortcodes/tab-shortcodes.css',
        array(),
        filemtime(__DIR__ . '/tabs/tab-shortcodes/tab-shortcodes.css'),
        // changed from '1.0' to filetime so that old versions don't get cached.
    );

    wp_enqueue_script(
        'mtech-shortcodes-tab-shortcodes',
        plugin_dir_url(__FILE__) . 'tabs/tab-shortcodes/tab-shortcodes.js',
        array(),
        '1.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'mtech_shortcodes_enqueue_dashboard_script');

function display_shortcodes_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Close PHP
    ?>

    <div class="wp-tabs-wrapper">
        <!-- Tab Navigation Links -->
        <nav class="nav-tab-wrapper">
            <a href="#tab-general" class="nav-tab nav-tab-active" data-tab="general">API</a>
            <a href="#tab-advanced" class="nav-tab" data-tab="advanced">Shortcodes</a>
        </nav>

        <!-- Tab Content Sections -->
        <div class="tab-content-container">
            <div id="tab-general" class="tab-content active">
                <?php require __DIR__ . '/tabs/tab-api/tab-api.php'; ?>
            </div>

            <div id="tab-advanced" class="tab-content">
                <?php require __DIR__ . '/tabs/tab-shortcodes/tab-shortcodes.php'; ?>
            </div>
        </div>
    </div>

    <?php
    // Open PHP
}