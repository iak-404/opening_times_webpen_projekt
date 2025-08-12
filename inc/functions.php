<?php

function set_shortcode($atts)
{

    $atts = shortcode_atts(
        array(
            'set' => ''
        ),
        $atts,
        'opnening_times'
    );

    $set_name = sanitize_text_field($atts['set']);

    if (empty($set_name)) {
        return '<p>Kein Set angegeben</p>';
    }

    $opening_time_html = get_opening_times_for_sc($set_name);

    if (!$opening_time_html) {
        return '<p>Keine Öffnungszeit gefunden</p>';
    }

    return $opening_time_html;

}

function opening_times_shortcode($atts)
{
    $set_name = isset($atts['set_name']) ? sanitize_text_field($atts['set_name']) : '';
    if (!$set_name) {
        return '<p>Kein Set ausgewählt.</p>';
    }

    $times = get_opening_times($set_name);
    if (empty($times)) {
        return '<p>Keine Öffnungszeiten gefunden für ' . esc_html($set_name) . '.</p>';
    }

    ob_start();
    ?>

    <div class="ot_wrapper">
        <h2>Opening Times:</h2>
        <div class="opening-times">
            <?php foreach ($times as $day => $intervals): ?>
                <div class="day-row">
                    <div class="day"><?php echo esc_html($day); ?>:</div>
                    <div class="times">
                        <?php
                        $closed = true;
                        foreach ($intervals as $interval) {
                            if ($interval['open_time'] === '00:00' && $interval['close_time'] === '00:00') {
                                continue;
                            }
                            $closed = false;
                            echo '<div class="time">' . esc_html($interval['open_time']) . ' - ' . esc_html($interval['close_time']) . ' Uhr</div>';
                        }
                        if ($closed) {
                            echo '<div class="times closed">Geschlossen</div>';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ot', 'opening_times_shortcode');

// function date()
// {
//     // $list = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

//     $today = new DateTime();

//     $weekStart = clone $today;
//     $weekStart->modify('monday this week');


//     foreach ($list as $day) {
//         $date = clone $weekStart;

//         $date->modify($day);

//         echo '<br>' . ucfirst($day) . ' ' . $date->format('d.m.y');
//     }
// }