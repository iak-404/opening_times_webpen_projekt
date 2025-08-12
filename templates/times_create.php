<?php

function ot_render_form()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $set_name = sanitize_text_field($_POST['set_name'] ?? '');
        $opening_times = $_POST['opening_times'] ?? [];

        if ($set_name && !empty($opening_times)) {
            ot_create_table($set_name);
            ot_save_opening_times($set_name, $opening_times);

            echo '<div>Öffnungszeiten gespeichert für Set: ' . esc_html($set_name) . '</div>';
        }
    }

    $days = [
        'Monday' => __('Monday', 'opening-times'),
        'Tuesday' => __('Tuesday', 'opening-times'),
        'Wednesday' => __('Wednesday', 'opening-times'),
        'Thursday' => __('Thursday', 'opening-times'),
        'Friday' => __('Friday', 'opening-times'),
        'Saturday' => __('Saturday', 'opening-times'),
        'Sunday' => __('Sunday', 'opening-times'),
    ];

    echo '<form class="times_create_form" method="POST" action="">';
    echo '<label>Set Name</label>';
    echo '<input class="input_set_name" type="text" name="set_name">';
    echo '<div class="wrapper_days">';
    foreach ($days as $key => $label) {
        echo "<div class='days'>";
        echo "<label>{$label}: </label>";
        echo "<input type='time' name='opening_times[{$key}][0][open_time]'>";
        echo "<input type='time' name='opening_times[{$key}][0][close_time]'>";
        echo "<span class='new_row' data-day='{$key}'></span>";
        echo "<button type='button' class='add_row' data-day='{$key}'>+</button>";
        echo "</div>";
    }
    echo '</div>';
    echo '<button class="times_create_submit_button" type="submit">Submit</button>';
    echo '</form>';

}



ot_render_form();