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
                'highlight_today' => [
                    'enabled' => 0,
                    'styles' => [
                        'font-weight' => 'bold',
                        'color' => '#008000',
                        'background-color' => '#e6f6e6',
                    ],
                ],
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
        'ot_field_time_12h',         // <-- nicht die anonyme Funktion!
        'opening-times-settings',
        'ot_display'
    );

    add_settings_field(
        'highlight_today',
        __('Heute hervorheben', 'opening-times'),
        'ot_field_highlight_today',     // <-- nicht die anonyme Funktion!
        'opening-times-settings',
        'ot_display'
    );

    add_settings_field(
        'show_closed',
        __('Show Closed Days', 'opening-times'),
        'ot_field_show_closed',     // <-- nicht die anonyme Funktion!
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
    $defaults = [
        'highlight_today' => [
            'enabled' => 0,
            'styles' => [
                'font-weight' => 'bold',
                'color' => '#008000',
                'background-color' => 'rgba(230, 246, 230, 1)',
            ],
        ],
    ];
    $o = wp_parse_args(get_option('ot_settings', []), $defaults);

    // Backcompat, falls alt als Skalar gespeichert
    if (isset($o['highlight_today']) && !is_array($o['highlight_today'])) {
        $o['highlight_today'] = [
            'enabled' => (int) $o['highlight_today'],
            'styles' => $defaults['highlight_today']['styles'],
        ];
    }

    $enabled = !empty($o['highlight_today']['enabled']);
    $styles = wp_parse_args($o['highlight_today']['styles'] ?? [], $defaults['highlight_today']['styles']);
    ?>
    <label style="display:block; margin-bottom:.5rem;">
        <!-- Hidden erzwingt 0, falls Checkbox nicht gesendet wird -->
        <input type="hidden" name="ot_settings[highlight_today][enabled]" value="0">
        <input type="checkbox" name="ot_settings[highlight_today][enabled]" value="1" <?php checked($enabled); ?>>
        <?php esc_html_e('Heute in der Liste hervorheben', 'opening-times'); ?>
    </label>

    <div style="padding-left:1.6rem; display:grid; gap:.5rem; max-width:420px;">
        <label class="today-highlights-label">
            <?php esc_html_e('Schriftstärke', 'opening-times'); ?><br>
            <select class="select-font-weight" name="ot_settings[highlight_today][styles][font-weight]">
                <?php foreach (['normal', 'bold', '500', '600', '700'] as $fw): ?>
                    <option value="<?php echo esc_attr($fw); ?>" <?php selected($styles['font-weight'], $fw); ?>>
                        <?php echo esc_html($fw); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php
        $stored = $styles['background-color'] ?? '';
        $hex_for_picker = '#e6f6e6'; // Fallback
        $alpha_pct = 100;            // 0–100
    
        if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|0?\.\d+|1(?:\.0)?)\s*\)$/i', $stored, $m)) {
            // clamp
            $r = max(0, min(255, (int) $m[1]));
            $g = max(0, min(255, (int) $m[2]));
            $b = max(0, min(255, (int) $m[3]));
            $a = max(0, min(1, (float) $m[4]));
            $hex_for_picker = sprintf('#%02X%02X%02X', $r, $g, $b);
            $alpha_pct = (int) round($a * 100);
        } elseif (preg_match('/^#([0-9a-f]{6})$/i', $stored)) {
            $hex_for_picker = $stored;
            $alpha_pct = 100;
        }
        ?>
        <label class="today-highlights-label">
            <?php esc_html_e('Textfarbe', 'opening-times'); ?><br>
            <input type="color" name="ot_settings[highlight_today][styles][color]"
                value="<?php echo esc_attr($styles['color']); ?>" class="regular-text">
        </label>

        <label class="today-highlights-label">
            <?php esc_html_e('Hintergrundfarbe', 'opening-times'); ?><br>
            <input id="color" type="color" value="<?php echo esc_attr($hex_for_picker); ?>" class="regular-text">
        </label>

        <label class="today-highlights-label">
            <?php esc_html_e('Transparenz für Hintergrundfarbe', 'opening-times'); ?>
        </label>
        <input type="range" class="alpha-range" id="alpha" min="0" max="100" value="<?php echo esc_attr($alpha_pct); ?>">

        <!-- Das ist das EINZIGE Feld mit name=…, hier landet die kombinierte RGBA -->
        <input type="text" id="bg-with-alpha" class="rgba-preview"
            name="ot_settings[highlight_today][styles][background-color]"
            value="<?php echo esc_attr($stored ?: 'rgba(230,246,230,1)'); ?>" readonly>

    </div>
    <hr>
    <?php
}

function ot_field_show_closed()
{
    $o = get_option('ot_settings', []);
    ?>
    <label>
        <input type="checkbox" name="ot_settings[show_closed]" value="1" <?php checked(!empty($o['show_closed'])); ?>>
        <?php esc_html_e('Hide closed days', 'opening-times'); ?>
    </label>
    <?php
}

// 3) Sanitize-Callback
function ot_sanitize_settings($in)
{
    $defaults = [
        'time_12h' => 0,
        'highlight_today' => [
            'enabled' => 0,
            'styles' => [
                'font-weight' => 'bold',
                'color' => '#008000',
                'background-color' => 'rgba(230, 246, 230, 1)',
            ],
        ],
        'show_closed' => 0,
    ];

    $in = is_array($in) ? $in : [];
    $out = [];

    $out['time_12h'] = !empty($in['time_12h']) ? 1 : 0;
    $out['show_closed'] = !empty($in['show_closed']) ? 1 : 0;

    // enabled kommt jetzt IMMER als "0" oder "1" an (wegen hidden input)
    $enabled_in = isset($in['highlight_today']['enabled']) ? (int) $in['highlight_today']['enabled'] : 0;
    $out['highlight_today'] = [
        'enabled' => $enabled_in ? 1 : 0,
        'styles' => [],
    ];

    $allowed_fw = ['normal', 'bold', '500', '600', '700'];
    $fw = $in['highlight_today']['styles']['font-weight'] ?? $defaults['highlight_today']['styles']['font-weight'];
    $out['highlight_today']['styles']['font-weight'] =
        in_array($fw, $allowed_fw, true) ? $fw : $defaults['highlight_today']['styles']['font-weight'];

    $text = isset($in['highlight_today']['styles']['color']) ? sanitize_hex_color($in['highlight_today']['styles']['color']) : '';
    $bg = isset($in['highlight_today']['styles']['background-color']) ? sanitize_hex_color($in['highlight_today']['styles']['background-color']) : '';
    $out['highlight_today']['styles']['color'] = $text ?: $defaults['highlight_today']['styles']['color'];
    $out['highlight_today']['styles']['background-color'] = $bg ?: $defaults['highlight_today']['styles']['background-color'];
    $bg_in = $in['highlight_today']['styles']['background-color'] ?? '';
    $bg_out = '';

    // RGBA?
    if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|0?\.\d+|1(?:\.0)?)\s*\)$/i', $bg_in, $m)) {
        $r = max(0, min(255, (int) $m[1]));
        $g = max(0, min(255, (int) $m[2]));
        $b = max(0, min(255, (int) $m[3]));
        $a = max(0, min(1, (float) $m[4]));
        $bg_out = sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim(rtrim(number_format($a, 3, '.', ''), '0'), '.')); // normalisiert
    } else {
        // Hex ok?
        $hex = sanitize_hex_color($bg_in);
        if ($hex) {
            $bg_out = $hex;
        }
    }

    // Fallback
    if (!$bg_out) {
        $bg_out = $defaults['highlight_today']['styles']['background-color']; // z. B. '#e6f6e6' oder eine rgba
    }

    $out['highlight_today']['styles']['background-color'] = $bg_out;
    return array_replace_recursive($defaults, $out);
}