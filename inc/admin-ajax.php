<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_load_opening_times', 'load_opening_times_callback');
add_action('wp_ajax_nopriv_load_opening_times', 'load_opening_times_callback');



function load_opening_times_callback()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'load_opening_times_nonce')) {
        wp_send_json_error('Ungültiger Nonce');
        wp_die();
    }

    if (!isset($_POST['set_name'])) {
        wp_send_json_error('Kein Set ausgewählt');
        wp_die();
    }

    $set_name = sanitize_text_field($_POST['set_name']);
    $times = get_opening_times($set_name);

    $today = new DateTime();

    $weekStart = clone $today;
    $weekStart->modify('monday this week');

    if (empty($times)) {
        echo '<p>Keine Öffnungszeiten gefunden für ' . esc_html($set_name) . '.</p>';
        wp_die();
    } 

    echo '<h2>Opening Times:</h2>';
    echo '<div class="ot_wrapper">';
    echo '<div id="opening-times" class="opening-times">';

    foreach ($times as $day => $intervals) {
        $date = clone $weekStart;
        $date->modify($day);
        echo '<div class="daydate">';
        echo '<div class="day">' . htmlspecialchars($day) . ':</div> ';
        echo '<div class="date">' . $date->format('d.m.y') . '</div>';
        echo '</div>';
        

        echo '<div class="times">';
        $closed = true;
        foreach ($intervals as $interval) {
            if ($interval['open_time'] === '00:00' && $interval['close_time'] === '00:00') {
                continue;
            }
            $closed = false;
            echo '<div class="time">' . htmlspecialchars($interval['open_time']) . ' - ' . htmlspecialchars($interval['close_time']) . ' Uhr</div>';
        }
        if ($closed) {
            echo '<div class="times closed">Geschlossen</div>';
        }
        echo '</div>';

    }

    echo '</div>';
    echo '</div>';


    wp_die();
}