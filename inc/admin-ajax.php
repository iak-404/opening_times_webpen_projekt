<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_load_opening_times', 'load_opening_times_callback');
add_action('wp_ajax_nopriv_load_opening_times', 'load_opening_times_callback');




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

    $today = new DateTime('today');
    $weekStart = (clone $today)->modify('monday this week');
    $weekdayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $weekdayIndex = array_flip($weekdayOrder);
    ?>

    <h2>Opening Times:</h2>
    <div class="ot_wrapper">
        <div id="opening-times" class="opening-times">
            <?php
            foreach ($times as $day => $data) {
                $offset = isset($weekdayIndex[$day]) ? (int)$weekdayIndex[$day] : 0;
                $dateObj = (clone $weekStart)->modify('+' . $offset . ' day');

                $isClosed = !empty($data['closed']);
                $dayTimes = (isset($data['times']) && is_array($data['times'])) ? $data['times'] : [];
                ?>
                <div class="daydate">
                    <div class="day"><?php echo esc_html($day); ?>:</div>
                    <div class="date"><?php echo esc_html($dateObj->format('d.m.y')); ?></div>
                </div>

                <div class="times">
                    <?php
                    if ($isClosed) {
                        echo '<div class="time closed">Closed</div>';
                    } else {
                        if (empty($dayTimes)) {
                            echo '<div class="time">–</div>';
                        } else {
                            foreach ($dayTimes as $interval) {
                                $open  = isset($interval['open_time'])  ? trim($interval['open_time'])  : '';
                                $close = isset($interval['close_time']) ? trim($interval['close_time']) : '';
                                if ($open === '' && $close === '') {
                                    continue;
                                }
                                echo '<div class="time">' . esc_html($open) . ' - ' . esc_html($close) . ' Uhr</div>';
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
