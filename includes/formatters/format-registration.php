<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns the full registration table HTML, sanitized for safe output.
 * Falls back to an empty string if no table is present in the source data
 * (e.g. some programs describe registration in plain prose instead of a table).
 */
function mtech_shortcodes_format_registration_table($raw_value) {
    if (empty($raw_value)) {
        return '';
    }

    return wp_kses_post($raw_value);
}

/**
 * Parses the registration table HTML and returns a short, human-readable
 * string describing the next open (or open+close) registration date range.
 *
 * Column order is assumed to be: [0] location, [1] open date, [2] close date.
 * This assumption holds across all known Coursedog table shapes as of this
 * writing, but isn't validated against the actual header text — if Coursedog's
 * editor output ever changes column order, this will silently misread dates.
 */
function mtech_shortcodes_format_registration_range($raw_value) {
    if (empty($raw_value)) {
        return esc_html('-');
    }

    // Not every program describes registration as a table — some use plain
    // prose paragraphs instead. There's no reliable way to extract a date
    // range from free text, so fail gracefully rather than attempting it.
    if (stripos($raw_value, '<table') === false) {
        mtech_shortcodes_log('Registration range formatter: no <table> found in source value, cannot extract a date range.');
        return esc_html('-');
    }

    $today_timestamp = strtotime(current_time('Y-m-d'));

    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);

    // Force UTF-8 interpretation to avoid mangled special characters —
    // DOMDocument assumes ISO-8859-1 for fragments with no declared charset.
    $loaded = $dom->loadHTML(
        '<?xml encoding="UTF-8">' . $raw_value,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    if (!$loaded) {
        mtech_shortcodes_log('Registration range formatter: DOMDocument failed to parse the source HTML.');
        return esc_html('-');
    }

    // Some tables have an explicit <tbody>, others place rows directly
    // under <table>. Fall back gracefully either way rather than assuming.
    $tbody = $dom->getElementsByTagName('tbody')->item(0);
    $rows = $tbody
        ? $tbody->getElementsByTagName('tr')
        : $dom->getElementsByTagName('tr');

    if ($rows->length === 0) {
        mtech_shortcodes_log('Registration range formatter: no table rows found.');
        return esc_html('-');
    }

    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');

        if ($cells->length < 3) {
            // Likely a header row (uses <th>, not <td>) — skip it.
            continue;
        }

        $open_cell  = $cells->item(1);
        $close_cell = $cells->item(2);
        $open_text  = $open_cell ? trim((string) $open_cell->textContent) : '';
        $close_text = $close_cell ? trim((string) $close_cell->textContent) : '';

        if ($open_text === '' && $close_text === '') {
            continue;
        }

        if ($open_text !== '' && stripos($open_text, 'now') !== false) {
            return esc_html('NOW');
        }

        $open_ts  = $open_text !== '' ? strtotime($open_text) : false;
        $close_ts = $close_text !== '' ? strtotime($close_text) : false;

        if ($open_ts === false) {
            continue;
        }

        // No close date given, and the open date has already passed — skip.
        if ($close_ts === false && $open_ts < $today_timestamp) {
            continue;
        }

        // No close date given, open date is valid (today or future) — just show it.
        if ($close_ts === false) {
            return esc_html(date('M. j', $open_ts));
        }

        // Close date has already passed — this window isn't relevant, skip.
        if ($close_ts < $today_timestamp) {
            continue;
        }

        // Valid future open+close range — return it.
        $open_english  = date('M. j', $open_ts);
        $close_english = date('M. j', $close_ts);
        return esc_html($open_english . ' - ' . $close_english);
    }

    // No valid future date range found in any row.
    return esc_html('-');
}