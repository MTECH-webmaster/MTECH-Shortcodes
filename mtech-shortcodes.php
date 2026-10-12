<?php
/*
 * Plugin Name: MTECH Shortcodes
 * Description: Shortcodes for use on mtec.edu
 * Version: 1.03
 * Author: MTECH
 * Author URI: https://mtec.edu
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/admin-dashboard/dashboard.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/form-handlers.php';
require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/logging.php';
require_once __DIR__ . '/includes/formatters/formatters.php';
require_once __DIR__ . '/includes/transients.php';
require_once __DIR__ . '/includes/role.php';

class MTECH_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Initialize the plugin
        add_action('plugins_loaded', array($this, 'init'));
    }

    public static function activate_plugin() {
        mtech_shortcodes_create_tables(); // from database.php
        mtech_shortcodes_add_custom_editor_role(); // from role.php

        // if (!wp_next_scheduled('mtech_shortcodes_cron_generate_api_token')) {
        //     wp_schedule_event(time(), 'twicedaily', 'mtech_shortcodes_cron_generate_api_token');
        // }
    }

    public static function deactivate_plugin() {
        mtech_shortcodes_remove_custom_editor_role(); // from role.php

        // wp_clear_scheduled_hook('mtech_shortcodes_cron_generate_api_token');
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // add_action('mtech_shortcodes_cron_generate_api_token', 'mtech_shortcodes_run_scheduled_token_generation');
    }

    public function add_admin_menu() {
        add_menu_page(
            'MTECH Shortcodes',
            'MTECH Shortcodes',
            'edit_mtech_shortcodes',
            'mtech-shortcodes',
            'display_shortcodes_admin_dashboard', // from dashboard.php
            'dashicons-shortcode',
            30
        );
    }

}

///////////// End of class /////////////

register_activation_hook(__FILE__, array('MTECH_Shortcodes', 'activate_plugin'));
register_deactivation_hook(__FILE__, array('MTECH_Shortcodes', 'deactivate_plugin'));

// Initialize the plugin
function mtech_shortcodes_init() {
    return MTECH_Shortcodes::get_instance();
}

// Start the plugin
$mtech_shortcodes = mtech_shortcodes_init();