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
        closed tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id),
        KEY day (day)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Save Opening Times 
// Save Opening Times (pro Tag closed, pro Zeitreihe Einträge)
function ot_save_opening_times($set_name, $opening_times)
{
    global $wpdb;

    $set_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($set_name));
    $table_name = 'ot_' . $set_name_sanitized;

    foreach ((array) $opening_times as $day => $data) {
        $day_sanitized = sanitize_text_field($day);
        $closed = !empty($data['closed']) ? 1 : 0;
        $times = is_array($data['times'] ?? null) ? $data['times'] : [];

        if ($closed) {
            // Tag ist geschlossen: eine Marker-Zeile, Zeiten NULL
            $wpdb->insert(
                $table_name,
                [
                    'day' => $day_sanitized,
                    'open_time' => null,
                    'close_time' => null,
                    'closed' => 1,
                ],
                ['%s', 'NULL', 'NULL', '%d']
            );
            continue;
        }

        // Tag ist offen: nur valide Zeitpaare speichern
        foreach ($times as $t) {
            $open = isset($t['open_time']) ? trim($t['open_time']) : '';
            $close = isset($t['close_time']) ? trim($t['close_time']) : '';
            if ($open === '' && $close === '') {
                continue;
            }

            // Normalisiere "HH:MM" → "HH:MM:00"
            $open = $open ? substr($open, 0, 5) . ':00' : null;
            $close = $close ? substr($close, 0, 5) . ':00' : null;

            $wpdb->insert(
                $table_name,
                [
                    'day' => $day_sanitized,
                    'open_time' => $open,
                    'close_time' => $close,
                    'closed' => 0,
                ],
                ['%s', '%s', '%s', '%d']
            );
        }
    }
}



// Update Opening Times

// Update Opening Times (einfach löschen & neu einfügen)
function update_opening_times($set_name, $opening_times)
{
    global $wpdb;

    $set_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($set_name));
    $table_name = 'ot_' . $set_name_sanitized;

    // Vorherige Einträge entfernen
    $wpdb->query("DELETE FROM {$table_name}");

    // Dann wie beim Save neu einfügen
    ot_save_opening_times($set_name, $opening_times);
}


function delete_set($set_name)
{
    global $wpdb;

    $set_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($set_name));
    $table_name = 'ot_' . $set_name_sanitized;

    // Tabelle löschen
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

    // Option entfernen, falls vorhanden
    delete_option('ot_absence');
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

// Get the saved opening Times (aggregiert zu ['closed'=>0|1, 'times'=>[]])
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

    // closed mitladen!
    $results = $wpdb->get_results(
        "SELECT day,
                closed,
                DATE_FORMAT(open_time,  '%H:%i') AS open_time,
                DATE_FORMAT(close_time, '%H:%i') AS close_time
         FROM {$table_name}
         ORDER BY id ASC",
        ARRAY_A
    );

    $opening_times = [];
    foreach ((array) $results as $row) {
        $day = $row['day'];

        if (!isset($opening_times[$day])) {
            $opening_times[$day] = ['closed' => 0, 'times' => []];
        }

        // Wenn eine Zeile "closed=1" existiert → Tag ist geschlossen, Zeiten ignorieren
        if (!empty($row['closed'])) {
            $opening_times[$day]['closed'] = 1;
            // Wir setzen times auf leeres Array – Marker genügt
            $opening_times[$day]['times'] = [];
            continue;
        }

        // Nur Zeitreihe anhängen, wenn Tag nicht als geschlossen markiert ist
        if (!$opening_times[$day]['closed']) {
            // Leere Werte filtern
            $open = $row['open_time'] ?: '';
            $close = $row['close_time'] ?: '';
            if ($open !== '' || $close !== '') {
                $opening_times[$day]['times'][] = [
                    'open_time' => $open,
                    'close_time' => $close,
                ];
            }
        }
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


