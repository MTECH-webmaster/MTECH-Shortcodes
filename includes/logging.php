<?php
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_log($message, $level = 'error') {
    if (!in_array($level, array('error', 'warning', 'info'), true)) {
        $level = 'error';
    }

    $log_dir = WP_CONTENT_DIR . '/mtech-shortcodes-logs';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        // Blocks direct web access to the whole folder
        file_put_contents($log_dir . '/.htaccess', "Deny from all\n");
        file_put_contents($log_dir . '/index.php', "<?php // Silence is golden\n");
    }

    $log_file = $log_dir . '/mtech-shortcodes.log';
    $timestamp = current_time('mysql');
    $line = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);

    error_log($line, 3, $log_file);
}

function mtech_shortcodes_error($code, $message, $log_message = null) {
    mtech_shortcodes_log($log_message ?: $message, 'error');
    return new \WP_Error($code, $message);
}

// // Noting here some potential solutions for future error handling:
// $error = mtech_shortcodes_error('shortcod_error_1', 'Test error message description');

// // Option #1 (shortcode example)
// if (1 == 1) {
//     $error = mtech_shortcodes_error('shortcod_error_1', 'Test error message description');
//     return '-';
// }

// // Option #2
// $token = mtech_shortcodes_get_api_token_from_db();
// if (is_wp_error($token)) {
//     mtech_shortcodes_log($token->get_error_message());
//     return ''; // fail silently — no need to inspect $token further here
// }

// // Option #3
// $token = mtech_shortcodes_get_api_token_from_db();
// if (is_wp_error($token)) {
//     if ($token->get_error_code() === 'get_api_token_from_db__error_1') {
//         // specific handling for "no token found" vs. other decrypt failures
//     }
//     return '';
// }


// NOTE - The WP_Error constructor (when using new \WP_Error) takes three optional parameters: error code, error message and error data.
// Example
// return new \WP_Error('error code', 'error message', 'error data');