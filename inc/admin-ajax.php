<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_load_opening_times', 'load_opening_times_callback');
add_action('wp_ajax_nopriv_load_opening_times', 'load_opening_times_callback');

require_once __DIR__ . '/vacation_helpers.php';



function load_opening_times_callback()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'load_opening_times_nonce')) {
        echo 'Ungültiger Nonce';
        wp_die();
    }

    if (empty($_POST['set_name'])) {
        echo 'Kein Set ausgewählt';
        wp_die();
    }

    $set_name = sanitize_text_field($_POST['set_name']);
    $times = get_opening_times($set_name);

    if (empty($times) || !is_array($times)) {
        echo 'Keine Öffnungszeiten gefunden für ' . esc_html($set_name) . '.';
        wp_die();
    }

    $set_name = sanitize_text_field($_POST['set_name']);
    $times = get_opening_times($set_name);

    // NEU: Urlaubstage einmalig vorbereiten
    $vacation_lookup = ot_build_vacation_lookup();

    $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');

    if (!empty($_GET['tz'])) {
        $cand = sanitize_text_field(wp_unslash($_GET['tz']));
        if (in_array($cand, timezone_identifiers_list(), true)) {
            $tz = new DateTimeZone($cand);
        }
    } elseif (!empty($_COOKIE['tz'])) {
        $cand = sanitize_text_field(wp_unslash($_COOKIE['tz']));
        if (in_array($cand, timezone_identifiers_list(), true)) {
            $tz = new DateTimeZone($cand);
        }
    }

    $today = new DateTime('today', $tz);
    $weekStart = (clone $today)->modify('monday this week');
    $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $weekdayIndex = array_flip($weekdayOrder);



    ?>

    <h2>Opening Times:</h2>
    <div class="ot_wrapper">
        <div id="opening-times" class="opening-times">
            <?php
            echo "<!-- tz-used: " . esc_html($tz->getName()) . " -->";
            foreach ($times as $day => $data) {
                $offset = isset($weekdayIndex[$day]) ? (int) $weekdayIndex[$day] : 0;
                $dateObj = (clone $weekStart)->modify('+' . $offset . ' day');
                $todayBackend = $dateObj->format('Y-m-d');
                $todayFrontend = $dateObj->format('d.m.y');
                $isVacation = ot_is_vacation_day($todayBackend, $vacation_lookup);

                $isClosed = !empty($data['closed']);
                $dayTimes = (isset($data['times']) && is_array($data['times'])) ? $data['times'] : [];
                ?>
                <div class="daydate">
                    <div class="day"><?php echo esc_html($day); ?>:</div>
                    <div class="date"><?php echo esc_html($todayFrontend); ?></div>
                </div>

                <div class="times">
                    <?php
                    if ($isClosed) {
                        echo '<div class="time closed">Closed</div>';
                    } else if ($isVacation) {
                        echo '<div class="time closed">Vacation</div>';
                    } else {
                        if (empty($dayTimes)) {
                            echo '<div class="time">–</div>';
                        } else {
                            foreach ($dayTimes as $interval) {
                                $open = isset($interval['open_time']) ? trim($interval['open_time']) : '';
                                $close = isset($interval['close_time']) ? trim($interval['close_time']) : '';
                                if ($open === '' && $close === '') {
                                    continue;
                                }
                                echo '<div class="time">' . esc_html($open) . ' - ' . esc_html($close) . ' Uhr</div>';
                                echo "<!-- tz-used: " . esc_html($tz->getName()) . " -->";
                            }
                        }
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <?php
    wp_die(); // wichtig für Ajax
}
