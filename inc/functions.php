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

    $vacation_lookup = ot_build_vacation_lookup();

    $tz_store = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('Europe/Berlin');

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

    $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'week';

    if ($mode === 'today') {
        echo ot_render_today_status($set_name, $tz_store, $tz_view, $vacation_lookup);
        wp_die();
    }

    $today = new DateTimeImmutable('today', $tz_store);
    $weekStart = (clone $today)->modify('monday this week');
    $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $weekdayIndex = array_flip($weekdayOrder);

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
                $isToday = $dateObj->format('Y-m-d') === $today->format('Y-m-d');

                $opts = get_option('ot_settings', []);
                $useTdy = !empty($opts['highlight_today']['enabled']);
                $isClosed = !empty($data['closed']);
                $dayTimes = (isset($data['times']) && is_array($data['times'])) ? $data['times'] : [];

                $cls = $useTdy && $isToday ? 'day-row today' : 'day-row';
                $hide = !empty($opts['show_closed']) && $isClosed ? ' style="display:none;"' : '';

                ?>
                <div class="<?php echo $cls; ?>"<?php echo $hide; ?>>
                    <div class="<?php echo ('daydate') ?>">
                        <div class="day">
                            <?php echo esc_html($day); ?>:
                        </div>
                        <div class="date"><?php echo esc_html($todayFrontend); ?></div>
                    </div>
                    <div class="<?php echo ('times') ?>">
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

                                    if ($close_store <= $open_store) {
                                        $close_store = $close_store->modify('+1 day');
                                    }

                                    $opts = get_option('ot_settings', []);
                                    $use12h = !empty($opts['time_12h']);
                                    $fmt = $use12h ? 'g:i A' : 'H:i';
                                    $suffix = $use12h ? '' : ' Uhr';

                                    $open_view = $open_store->setTimezone($tz_view)->format($fmt);
                                    $close_view = $close_store->setTimezone($tz_view)->format($fmt);

                                    echo '<div class="time">' . esc_html($open_view . ' - ' . $close_view . $suffix) . '</div>';
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
    wp_die();
}



add_shortcode('opening_times', function ($atts) {
    $atts = shortcode_atts([
        'set' => '',
        'use_client_tz' => '1',
        'tz' => '',
    ], $atts, 'opening_times');

    if ($atts['set'] === '') {
        return '<div class="opening-times-error">Fehlendes Shortcode-Attribut: set="...".</div>';
    }

    $uid = 'ot_' . wp_generate_uuid4();
    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>" class="opening-times-auto" data-set="<?php echo esc_attr($atts['set']); ?>"
        data-use-client-tz="<?php echo esc_attr($atts['use_client_tz']); ?>" data-tz="<?php echo esc_attr($atts['tz']); ?>">
    </div>
    <?php
    return ob_get_clean();
});

if (!function_exists('ot_fmt_duration')) {
    function ot_fmt_duration(int $seconds): string
    {
        $mins = intdiv($seconds, 60);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        $opts = get_option('ot_settings', []);
        $use12h = !empty($opts['time_12h']);
        $hSuffix = $use12h ? ' h ' : ' Std ';
        $mSuffix = $use12h ? ' m ' : ' Min ';

        return ($h ? $h . $hSuffix : '') . $m . $mSuffix;
    }
}

function ot_render_today_status(string $set, DateTimeZone $tz_store, DateTimeZone $tz_view, $vacation_lookup = null): string
{
    $ot = get_opening_times($set);
    if (empty($ot) || !is_array($ot)) {
        return '<div class="test">Keine Daten für ' . esc_html($set) . '</div>';
    }

    $now_view = new DateTimeImmutable('now', $tz_view);
    $now_store = $now_view->setTimezone($tz_store);
    $dayKey = $now_store->format('l');
    $today = $ot[$dayKey] ?? null;

    $isVacation = false;
    if ($vacation_lookup && function_exists('ot_is_vacation_day')) {
        $isVacation = ot_is_vacation_day($now_store->format('Y-m-d'), $vacation_lookup);
    }
    if ($isVacation || !$today || !empty($today['closed'])) {
        return '<div class="test">heute geschlossen</div>';
    }

    $slots = is_array($today['times'] ?? null) ? $today['times'] : [];
    $fmt = function (int $seconds): string {
        $mins = intdiv($seconds, 60);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return ($h ? $h . ' Std ' : '') . $m . ' Min';
    };

    foreach ($slots as $slot) {
        $o = trim($slot['open_time'] ?? '');
        $c = trim($slot['close_time'] ?? '');
        if ($o === '' || $c === '')
            continue;

        $d = $now_store->format('Y-m-d');
        $open_store = DateTimeImmutable::createFromFormat('Y-m-d H:i', "$d $o", $tz_store);
        $close_store = DateTimeImmutable::createFromFormat('Y-m-d H:i', "$d $c", $tz_store);
        if (!$open_store || !$close_store)
            continue;
        if ($close_store <= $open_store)
            $close_store = $close_store->modify('+1 day');

        $opts = get_option('ot_settings', []);
        $use12h = !empty($opts['time_12h']);
        $fmt = $use12h ? 'g:i A' : 'H:i';
        $suffix = $use12h ? '' : ' Uhr';

        // 1) Erst DateTime-Objekte bauen (in View-TZ)
        $open_dt = $open_store->setTimezone($tz_view);
        $close_dt = $close_store->setTimezone($tz_view);

        // 2) Dann String-Repräsentation für die Ausgabe
        $open_str = $open_dt->format($fmt);
        $close_str = $close_dt->format($fmt);

        // 3) Vergleiche IMMER mit DateTime-Objekten, nicht mit Strings
        if ($now_view < $open_dt) {
            $diff = $open_dt->getTimestamp() - $now_view->getTimestamp();
            return '<div class="test">' . esc_html($set) . ' öffnet in '
                . ot_fmt_duration($diff) . ' um ' . $open_str . $suffix . '.</div>';
        }
        if ($now_view >= $open_dt && $now_view < $close_dt) {
            $diff = $close_dt->getTimestamp() - $now_view->getTimestamp();
            return '<div class="test">' . esc_html($set) . ' ist offen und schließt in '
                . ot_fmt_duration($diff) . ' um ' . $close_str . $suffix . '.</div>';
        }
    }
    return '<div class="test">heute geschlossen</div>';
}

add_shortcode('tl', function ($atts) {
    $atts = shortcode_atts(['set' => '', 'tz' => ''], $atts, 'tl');
    if ($atts['set'] === '')
        return 'Kein Set angegeben. Nutzung: [tl set="webpen"]';

    $uid = 'tl_' . wp_generate_uuid4();
    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>" class="ot-today-auto" data-set="<?php echo esc_attr($atts['set']); ?>"
        data-tz="<?php echo esc_attr($atts['tz']); ?>">
        <div class="loading">Lade Öffnungszeiten …</div>
    </div>
    <?php
    return ob_get_clean();
});


function vacation_difference($start, $end)
{
    $start_date = new DateTime($start);
    $end_date = new DateTime($end);

    $end_date->modify('+1 day');

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start_date, $interval, $end_date);

    $days = [];
    foreach ($period as $date) {
        $days[] = $date->format('Y-m-d');
    }

    return $days;
}