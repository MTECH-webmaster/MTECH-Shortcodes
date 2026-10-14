<?php
if (!defined('ABSPATH')) {
    exit;
}

function mtech_shortcodes_format_schedule($schedule_rows) {
    ob_start();
    ?>
    <table class="mtech-schedules-table">
        <thead>
            <tr>
                <th>Locations</th>
                <th>Start Date</th>
                <th>Hours</th>
                <th>Days</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedule_rows as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row->locations); ?></td>
                    <td><?php echo esc_html($row->start_date); ?></td>
                    <td><?php echo esc_html($row->hours); ?></td>
                    <td><?php echo esc_html($row->days); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}