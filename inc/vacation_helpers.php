<?php
if (!defined('ABSPATH')) {
    exit;
}

function ot_build_vacation_lookup(): array
{
    $absences = get_all_absences();
    $lookup = [];

    foreach ((array) $absences as $a) {
        $start = $a['start'] ?? '';
        $end = $a['end'] ?? '';
        if ($start === '' || $end === '') {
            continue;
        }

        $dates = vacation_difference($start, $end);
        foreach ((array) $dates as $d) {
            if ($d !== '') {
                $lookup[$d] = true;
            }
        }
    }
    return $lookup;
}

function ot_is_vacation_day(string $ymd, array $lookup): bool
{
    return isset($lookup[$ymd]);
}
