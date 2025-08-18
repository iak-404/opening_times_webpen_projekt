<?php

require_once __DIR__ . '/vacation_helpers.php';


function opening_times_shortcode($atts)
{
    $atts = shortcode_atts(
        array('set' => '', 'set_name' => ''),
        $atts,
        'ot'
    );

    $set_name = $atts['set_name'] !== '' ? $atts['set_name'] : $atts['set'];
    $set_name = sanitize_text_field($set_name);

    if ($set_name === '') {
        return '<p>Kein Set ausgewählt.</p>';
    }

    $times = get_opening_times($set_name);
    if (empty($times)) {
        return '<p>Keine Öffnungszeiten gefunden für ' . esc_html($set_name) . '.</p>';
    }


    $vacation_lookup = ot_build_vacation_lookup();

    $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
    if (!empty($_COOKIE['tz'])) {
        $tz_candidate = sanitize_text_field(wp_unslash($_COOKIE['tz']));
        if (in_array($tz_candidate, timezone_identifiers_list(), true)) {
            $tz = new DateTimeZone($tz_candidate);
        }
    }
    $today = new DateTime('today', $tz);
    $weekStart = (clone $today)->modify('monday this week');
    $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $weekdayIndex = array_flip($weekdayOrder);

    ob_start(); ?>
    <div class="ot_wrapper">
        <div class="opening-times">
            <?php foreach ($times as $day => $data) {
                $offset = isset($weekdayIndex[$day]) ? (int) $weekdayIndex[$day] : 0;
                $dateObj = (clone $weekStart)->modify('+' . $offset . ' day');
                $todayBackend = $dateObj->format('Y-m-d');
                $isVacation = ot_is_vacation_day($todayBackend, $vacation_lookup);

                $isClosed = !empty($data['closed']);
                $dayTimes = (isset($data['times']) && is_array($data['times'])) ? $data['times'] : [];
                $closed = !empty($data['closed']);
                $intervals = (is_array($data['times'] ?? null)) ? $data['times'] : array(); ?>


                <div class="day-row">
                    <div class="day"><?php echo esc_html($day); ?>:</div>
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
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    return trim($html);
}
add_shortcode('ot', 'opening_times_shortcode');



// Vacation 

function vacation_difference($start, $end)
{
    $start_date = new DateTime($start);
    $end_date = new DateTime($end);

    // Enddatum einen Tag weiter setzen, weil DatePeriod das Enddatum ausschließt
    $end_date->modify('+1 day');

    $interval = new DateInterval('P1D'); // Schrittweite = 1 Tag
    $period = new DatePeriod($start_date, $interval, $end_date);

    $days = [];
    foreach ($period as $date) {
        $days[] = $date->format('Y-m-d');
    }

    return $days;
}