<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function () {
    register_setting(
        'ot_settings_group',         // Einstellungs-Gruppe (options.php)
        'ot_settings',               // Optionsname (Array)
        [
            'type' => 'array',
            'sanitize_callback' => 'ot_sanitize_settings',
            'default' => [
                'time_12h' => 0,
            ],
            // optional:
            // 'show_in_rest'   => false,
            // 'description'    => 'Opening Times settings',
        ]
    );

    add_settings_section(
        'ot_display',
        __('Anzeige', 'opening-times'),
        '__return_false',
        'opening-times-settings'
    );

    add_settings_field(
        'time_12h',
        __('12-Stunden-Format (AM/PM)', 'opening-times'),
        function () {
            $o = get_option('ot_settings', []);
            ?>
        <label>
            <input type="checkbox" name="ot_settings[time_12h]" value="1" <?php checked(!empty($o['time_12h'])); ?>>
            <?php esc_html_e('Zeiten als 1:30 PM statt 13:30 anzeigen', 'opening-times'); ?>
        </label>
        <?php
        },
        'opening-times-settings',
        'ot_display'
    );
});


// 2) Feld-Callback (rendert die Checkbox)
function ot_field_time_12h()
{
    $o = get_option('ot_settings', []);
    ?>
    <label>
        <input type="checkbox" name="ot_settings[time_12h]" value="1" <?php checked(!empty($o['time_12h'])); ?>>
        <?php esc_html_e('Zeiten als 1:30 PM statt 13:30 anzeigen', 'opening-times'); ?>
    </label>
    <?php
}

// 3) Sanitize-Callback
function ot_sanitize_settings($in)
{
    $out = get_option('ot_settings', []);
    $out['time_12h'] = empty($in['time_12h']) ? 0 : 1;
    return $out;
}
