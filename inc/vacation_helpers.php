<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Baut ein Set-ähnliches Lookup der Urlaubstage (Format Y-m-d).
 * Erwartet, dass get_all_absences() Einträge wie ['start'=>'Y-m-d','end'=>'Y-m-d'] liefert.
 */
function ot_build_vacation_lookup(): array {
    $absences = get_all_absences();
    $lookup = [];

    foreach ((array)$absences as $a) {
        $start = $a['start'] ?? '';
        $end   = $a['end']   ?? '';
        if ($start === '' || $end === '') { continue; }

        $dates = vacation_difference($start, $end); // -> array Y-m-d
        foreach ((array)$dates as $d) {
            if ($d !== '') { $lookup[$d] = true; }
        }
    }
    return $lookup; // ['2025-08-20' => true, ...]
}

/** Schnelle Abfrage, ob Datum Urlaub ist. */
function ot_is_vacation_day(string $ymd, array $lookup): bool {
    return isset($lookup[$ymd]);
}
