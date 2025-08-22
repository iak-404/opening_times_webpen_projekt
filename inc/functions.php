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

                $isHoliday = ot_holidays_enabled() && ot_is_holiday($todayBackend);




                ?>
                <div class="<?php echo $cls; ?>" <?php echo $hide; ?>>
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
                        } else if ($isHoliday) {
                            echo '<div class="time closed">Feiertag</div>';
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

    $isHoliday = ot_holidays_enabled() && ot_is_holiday($now_store->format('Y-m-d'));
    if ($isVacation || $isHoliday || !$today || !empty($today['closed'])) {
        $reason = $isHoliday ? ' (Feiertag)' : ($isVacation ? ' (Vacation)' : '');
        return '<div class="test">heute geschlossen' . $reason . '</div>';
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


// Test Feiertage 
if (!defined('ABSPATH'))
    exit;

/**
 * Lies Plugin-Einstellungen
 */
if (!function_exists('ot_get_settings')) {
    function ot_get_settings(): array
    {
        return wp_parse_args(get_option('ot_settings', []), [
            'holidays_api' => ['enabled' => 0, 'region' => 'nw'],
        ]);
    }
}

/**
 * Ist Feiertags-Feature aktiviert?
 */
if (!function_exists('ot_holidays_enabled')) {
    function ot_holidays_enabled(): bool
    {
        $o = ot_get_settings();
        return !empty($o['holidays_api']['enabled']);
    }
}

/**
 * Gewählte Region (Bundesland-Kürzel)
 */
if (!function_exists('ot_get_region')) {
    function ot_get_region(): string
    {
        $o = ot_get_settings();
        $r = strtolower($o['holidays_api']['region'] ?? 'nw');
        return preg_match('/^[a-z]{2}$/', $r) ? $r : 'nw';
    }
}

/**
 * Feiertage remote laden (nur Rohdaten -> Liste ISO-Daten)
 */
if (!function_exists('ot_fetch_holidays_remote')) {
    function ot_fetch_holidays_remote(string $region): array
    {
        $url = 'https://get.api-feiertage.de/?states=' . rawurlencode(strtolower($region));
        $res = wp_remote_get($url, ['timeout' => 8]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200)
            return [];
        $data = json_decode(wp_remote_retrieve_body($res), true);
        $list = (isset($data['feiertage']) && is_array($data['feiertage'])) ? $data['feiertage'] : [];
        $dates = [];
        foreach ($list as $h) {
            if (!empty($h['date']))
                $dates[$h['date']] = true; // Set
        }
        return array_keys($dates); // ['2025-01-01', ...]
    }
}

/**
 * Gecachte Feiertage (pro Region+Jahr)
 */
if (!function_exists('ot_get_holiday_dates')) {
    function ot_get_holiday_dates(?string $region = null): array
    {
        $region = strtolower($region ?: ot_get_region());
        $year = (int) date('Y'); // wenn du WP-Zeitzone willst: (int) wp_date('Y')
        $key = "ot_holidays_{$region}_{$year}";
        $cached = get_transient($key);
        if ($cached !== false)
            return (array) $cached;

        $dates = ot_fetch_holidays_remote($region);
        set_transient($key, $dates, DAY_IN_SECONDS); // 24h Cache
        return $dates;
    }
}

/**
 * Prüfer: Ist Datum (Y-m-d) ein Feiertag?
 */
if (!function_exists('ot_is_holiday')) {
    function ot_is_holiday($date, ?string $region = null): bool
    {
        if ($date instanceof DateTimeInterface) {
            $d = $date->format('Y-m-d');
        } elseif (is_string($date)) {
            $d = substr($date, 0, 10);
        } else {
            $d = date('Y-m-d', strtotime((string) $date));
        }
        return in_array($d, ot_get_holiday_dates($region), true);
    }
}

/**
 * Cache leeren, wenn sich die Region in den Einstellungen ändert
 */
add_action('update_option_ot_settings', function ($old, $new) {
    $oldRegion = strtolower($old['holidays_api']['region'] ?? '');
    $newRegion = strtolower($new['holidays_api']['region'] ?? '');
    $year = (int) date('Y');
    foreach (array_filter([$oldRegion, $newRegion]) as $r) {
        delete_transient("ot_holidays_{$r}_{$year}");
    }
}, 10, 2);
