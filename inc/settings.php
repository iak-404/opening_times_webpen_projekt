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
                'highlight_today' => 0,
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

    add_settings_field(
        'highlight_today',
        __('Heute hervorheben', 'opening-times'),
        function () {
            $o = get_option('ot_settings', []);
            ?>
        <label>
            <input type="checkbox" name="ot_settings[highlight_today]" value="1" <?php checked(!empty($o['highlight_today'])); ?>>
            <?php esc_html_e('Heute in der Liste hervorheben', 'opening-times'); ?>
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

function ot_field_highlight_today()
{
    $o = get_option('ot_settings', []);
    ?>
    <label>
        <input type="checkbox" name="ot_settings[highlight_today]" value="1" <?php checked(!empty($o['hightlight_todayy'])); ?>>
        <?php esc_html_e('Heute in der Liste hervorheben', 'opening-times'); ?>
    </label>
    <?php
}

// 3) Sanitize-Callback
function ot_sanitize_settings($in)
{
    $defaults = [
        'time_12h' => 0,
        'highlight_today' => 0,
    ];

    $in = is_array($in) ? $in : [];

    $out = [];

    $out['time_12h'] = !empty($in['time_12h']) ? 1 : 0;
    $out['highlight_today'] = !empty($in['highlight_today']) ? 1 : 0;

    $out = array_replace_recursive($defaults, $out);

    return $out;

}




