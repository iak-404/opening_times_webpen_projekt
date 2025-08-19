<?php

function time_logic($atts)
{
    $atts = shortcode_atts(
        array(
            'set' => ''
        ),
        $atts,
        'tl'
    );

    $set = sanitize_text_field($atts['set']);
    if ($set === '')
        return 'Kein set_name angegeben. Nutzung: [tl set_name="webpen"]';

    $ot = get_opening_times($set);

    // Store-TZ (DB gilt hier)
    $tz_store = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('Europe/Berlin');

    // Viewer-TZ (Browser via ?tz= oder Cookie), sonst wie Store
    $tz_view = $tz_store;
    $tz_candidate = null;
    if (!empty($_GET['tz'])) {
        $tz_candidate = sanitize_text_field(wp_unslash($_GET['tz']));
    } elseif (!empty($_COOKIE['tz'])) {
        $tz_candidate = sanitize_text_field(wp_unslash($_COOKIE['tz']));
    }
    if ($tz_candidate && in_array($tz_candidate, timezone_identifiers_list(), true)) {
        $tz_view = new DateTimeZone($tz_candidate);
    }

    // Jetzt in Viewer-TZ, “heute” (für Tageszeile) in Store-TZ
    $now_view = new DateTimeImmutable('now', $tz_view);
    $now_store = $now_view->setTimezone($tz_store);
    $dayKey = $now_store->format('l'); // Monday/Tuesday/...


    $today = $ot[$dayKey] ?? null;

    $out = '';

    if (!$today || !empty($today['closed'])) {
        return '<div class="test">heute geschlossen</div>';
    }

    $slots = is_array($today['times'] ?? null) ? $today['times'] : [];

    // X Std Y Min
    $fmt = function (int $seconds): string {
        $mins = intdiv($seconds, 60);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return ($h ? $h . ' Std ' : '') . $m . ' Min';
    };

    foreach ($slots as $s) {
        if (!is_array($s))
            continue;
        $o = $s['open_time'] ?? '';
        $c = $s['close_time'] ?? '';
        if ($o === '' || $c === '')
            continue;

        // Zeiten der DB gelten für HEUTE in Store-TZ
        $store_date = $now_store->format('Y-m-d');
        $open_store = DateTimeImmutable::createFromFormat('Y-m-d H:i', $store_date . ' ' . $o, $tz_store);
        $close_store = DateTimeImmutable::createFromFormat('Y-m-d H:i', $store_date . ' ' . $c, $tz_store);
        if (!$open_store || !$close_store)
            continue;

        // Über-Mitternacht (z.B. 22:00–02:00)
        if ($close_store <= $open_store) {
            $close_store = $close_store->modify('+1 day');
        }

        // In Viewer-TZ umrechnen
        $open_view = $open_store->setTimezone($tz_view);
        $close_view = $close_store->setTimezone($tz_view);

        // Vergleiche & Ausgabe in Viewer-TZ
        if ($now_view < $open_view) {
            $out = '<div class="test">' . $set . ' öffnet in ' . $fmt($open_view->getTimestamp() - $now_view->getTimestamp()) . ' um ' . $open_view->format('H:i') . ' Uhr.</div>';
            break;
        }
        if ($now_view >= $open_view && $now_view < $close_view) {
            $out = '<div class="test">' . $set . ' ist offen und schließt in ' . $fmt($close_view->getTimestamp() - $now_view->getTimestamp()) . ' um ' . $close_view->format('H:i') . ' Uhr.</div>';
            break;
        }


    }

    // Nichts gepasst → heute geschlossen
    if ($out === '')
        $out = '<div class="test">heute geschlossen</div>';

    return $out;


}


add_shortcode('tl', 'time_logic');