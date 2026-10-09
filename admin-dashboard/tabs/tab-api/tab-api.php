<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$saved_username = get_option('mtech_shortcodes_username', '');
$has_password   = get_option('mtech_shortcodes_encrypted_password', '') !== '';
?>
<h2>API</h2>

<?php if (isset($_GET['mtech_api_saved']) && $_GET['mtech_api_saved'] === '1') : ?>
    <div class="notice notice-success is-dismissible"><p>API credentials saved.</p></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mtech-api-credentials-form">
    <input type="hidden" name="action" value="mtech_shortcodes_save_api_credentials">
    <?php wp_nonce_field('mtech_shortcodes_save_api_credentials', 'mtech_shortcodes_api_nonce'); ?>

    <p>
        <label>
            Username<br>
            <input type="text" name="username" value="<?php echo esc_attr($saved_username); ?>" autocomplete="off">
        </label>
    </p>
    <p>
        <label>
            Password<br>
            <input type="password" name="password" value="" placeholder="<?php echo $has_password ? esc_attr('•••••••• (saved — leave blank to keep current password)') : ''; ?>" autocomplete="new-password">
        </label>
    </p>
    <p>
        <button type="submit" class="button button-primary">Save Credentials</button>
    </p>
</form>