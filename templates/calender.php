<?php
// Daten aus der Datenbank holen
$absences = get_all_absences();

// Events für JavaScript vorbereiten
$calendar_events = array_map(function ($absence) {
    return [
        'title' => $absence['title'],
        'start' => $absence['start'],
        'end' => date('Y-m-d', strtotime($absence['end'] . ' +1 day'))
    ];
}, $absences);

wp_localize_script('ot-calender-init', 'otCalendarEvents', $calendar_events);
?>

<div class="wrap">
    <h1><?php _e('Kalender', 'opening-times'); ?></h1>
    
    <div style="margin-bottom: 20px; padding: 10px; background: #f0f0f0;">

        <strong>Für JavaScript aufbereitete Events:</strong>
        <pre><?php print_r($calendar_events); ?></pre>
        <div id="testCalendar"></div>
    </div>
    
    <div id="ot_calendar" class="admin_calendar"></div>
</div>
