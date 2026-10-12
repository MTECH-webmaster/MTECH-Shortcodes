<?php
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_add_custom_editor_role() {
    add_role(
        'shortcodes_editor',
        __( 'Shortcodes Editor', 'textdomain' ),
        array(
            'read'                  => true,
            'edit_mtech_shortcodes' => true,
        )
    );

    // Ensure Administrators also have access
    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('edit_mtech_shortcodes')) {
        $admin_role->add_cap('edit_mtech_shortcodes');
    }
}

function mtech_shortcodes_remove_custom_editor_role() {
    $editors = get_users(array('role' => 'shortcodes_editor'));
    foreach ($editors as $user) {
        $user->set_role('subscriber');
    }
    remove_role( 'shortcodes_editor' );

    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->remove_cap('edit_mtech_shortcodes');
    }
}