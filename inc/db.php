<?php
if (!defined('ABSPATH')) {
    exit;
}

// Create Table for Opening Times
function ot_create_table($set_name)
{
    global $wpdb;

    if (empty($set_name)) {
        error_log('ot_create_table: $set_name ist leer!');
        return;
    }

    $set_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($set_name));
    $table_name = 'ot_' . $set_name_sanitized;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        day varchar(20) NOT NULL,
        open_time time DEFAULT NULL,
        close_time time DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY day (day)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Save Opening Times 
function ot_save_opening_times($set_name, $opening_times)
{
    global $wpdb;

    $set_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($set_name));
    $table_name = 'ot_' . $set_name_sanitized;

    foreach ($opening_times as $day => $rows) {
        foreach ($rows as $times) {
            $open = $times['open_time'] ?? null;
            $close = $times['close_time'] ?? null;

            if ($open) {
                $open = substr($open, 0, 5) . ':00';
            }
            if ($close) {
                $close = substr($close, 0, 5) . ':00';
            }

            $wpdb->insert(
                $table_name,
                [
                    'day' => $day,
                    'open_time' => $open,
                    'close_time' => $close,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                ]
            );
        }
    }
}


// Get all saved Sets
function get_all_sets()
{
    global $wpdb;

    $prefix = 'ot_'; // ohne wp_ oder $wpdb->prefix

    $tables = $wpdb->get_col("SHOW TABLES LIKE '{$prefix}%'");

    $sets = [];
    foreach ($tables as $table) {
        // Entfernt nur 'ot_' (kein wp_ davor)
        $set_name = str_replace($prefix, '', $table);
        $sets[] = $set_name;
    }

    return $sets;
}

// Get the saved opening Times from the chosen Set

function get_opening_times($set_name)
{
    global $wpdb;

    $set_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($set_name));
    $table_name = 'ot_' . $set_name_sanitized;

    // Prüfen, ob Tabelle existiert
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($table_name)));
    if (!$table_exists) {
        return [];
    }

    $results = $wpdb->get_results(
        "SELECT day, DATE_FORMAT(open_time, '%H:%i') AS open_time, DATE_FORMAT(close_time, '%H:%i') AS close_time FROM $table_name ORDER BY id ASC",
        ARRAY_A
    );
    
    $opening_times = [];
    foreach ($results as $row) {
        $day = $row['day'];
        if (!isset($opening_times[$day])) {
            $opening_times[$day] = [];
        }
        $opening_times[$day][] = [
            'open_time' => $row['open_time'],
            'close_time' => $row['close_time'],
        ];
    }
    return $opening_times;
}

function get_all_absences()
{
    return get_option('ot_absence', []);
}

function save_absence_period($start, $end, $title)
{
    $absence = get_option('ot_absence', []);

    $absence[] = [
        'title' => $title,
        'start' => $start,
        'end' => $end,
    ];

    update_option('ot_absence', $absence);
}


