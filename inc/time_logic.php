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

    $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('Europe/Berlin');
    $now = new DateTimeImmutable('now', $tz);
    $dayKey = $now->format('l'); // Monday/Tuesday/...

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

        $oT = DateTimeImmutable::createFromFormat('H:i', $o, $tz);
        $cT = DateTimeImmutable::createFromFormat('H:i', $c, $tz);
        if (!$oT || !$cT)
            continue;

        $open = $now->setTime((int) $oT->format('H'), (int) $oT->format('i'));
        $close = $now->setTime((int) $cT->format('H'), (int) $cT->format('i'));

        if ($now < $open) {
            $out = '<div class="test">' . $set . ' öffnet in ' . $fmt($open->getTimestamp() - $now->getTimestamp()) . ' um  ' . $o .' Uhr.</div>';
            break;
        }
        if ($now >= $open && $now < $close) {
            $out = '<div class="test">' . $set . ' ist offen und schließt in ' . $fmt($close->getTimestamp() - $now->getTimestamp()) . ' um ' . $c .  ' Uhr.</div>';
            break;
        }
    }

    // Nichts gepasst → heute geschlossen
    if ($out === '')
        $out = '<div class="test">heute geschlossen</div>';

    return $out;


}


add_shortcode('tl', 'time_logic'); 