<?php
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

    // Urlaubstage vorbereiten
    $vacation_lookup = ot_build_vacation_lookup();

    // Store-TZ (Server/WordPress)
    $tz_store = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('Europe/Berlin');

    // View-TZ (vom Client übergeben, Cookie, GET oder POST)
    $tz_view = $tz_store;
    $tz_candidate = null;

    if (!empty($_POST['tz'])) {
        $tz_candidate = sanitize_text_field(wp_unslash($_POST['tz']));
    } elseif (!empty($_GET['tz'])) {
        $tz_candidate = sanitize_text_field(wp_unslash($_GET['tz']));
    } elseif (!empty($_COOKIE['tz'])) {
        $tz_candidate = sanitize_text_field(wp_unslash($_COOKIE['tz']));
    }

    if ($tz_candidate && in_array($tz_candidate, timezone_identifiers_list(), true)) {
        $tz_view = new DateTimeZone($tz_candidate);
    }

    $today = new DateTimeImmutable('today', $tz_store);
    $weekStart = (clone $today)->modify('monday this week');
    $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $weekdayIndex = array_flip($weekdayOrder);

    // HTML-Ausgabe
    ?>
    <h2>Opening Times:</h2>
    <div class="ot_wrapper">
        <div id="opening-times" class="opening-times">
            <?php
            echo "<!-- tz-used: " . esc_html($tz_store->getName()) . " -->";
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

                                $open_store = DateTimeImmutable::createFromFormat('Y-m-d H:i', $todayBackend . ' ' . $open, $tz_store);
                                $close_store = DateTimeImmutable::createFromFormat('Y-m-d H:i', $todayBackend . ' ' . $close, $tz_store);
                                if (!$open_store || !$close_store) {
                                    continue;
                                }

                                // Über Mitternacht
                                if ($close_store <= $open_store) {
                                    $close_store = $close_store->modify('+1 day');
                                }

                                $open_view = $open_store->setTimezone($tz_view)->format('H:i');
                                $close_view = $close_store->setTimezone($tz_view)->format('H:i');

                                echo '<div class="time">' . esc_html($open_view . ' - ' . $close_view . ' Uhr') . '</div>';
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
    wp_die();
}


// functions.php oder Plugin:

add_shortcode('opening_times', function ($atts) {
    $atts = shortcode_atts([
        'set'           => '',
        'use_client_tz' => '1',
        'tz'            => '',
    ], $atts, 'opening_times');

    if ($atts['set'] === '') {
        return '<div class="opening-times-error">Fehlendes Shortcode-Attribut: set="...".</div>';
    }

    $uid = 'ot_' . wp_generate_uuid4();
    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>" class="opening-times-auto"
         data-set="<?php echo esc_attr($atts['set']); ?>"
         data-use-client-tz="<?php echo esc_attr($atts['use_client_tz']); ?>"
         data-tz="<?php echo esc_attr($atts['tz']); ?>"></div>
    <?php
    return ob_get_clean();
});




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