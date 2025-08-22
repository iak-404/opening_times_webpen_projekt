<?php

/**
 * Plugin Name: Opening Times 
 * Description: Shows opening times
 * Author: Kai-Uwe Kamps
 * Author URI: www.iak404.de
 * Version: 1.0
 * Text Domain: opening-times
 * Domain Path: /languages
 */

if (!defined('ABSPATH'))
    exit;

require_once plugin_dir_path(__FILE__) . 'inc/db.php';
require_once plugin_dir_path(__FILE__) . 'inc/admin-ajax.php';
require_once plugin_dir_path(__FILE__) . 'inc/functions.php';
require_once plugin_dir_path(__FILE__) . 'inc/settings.php';


final class Opening_Times
{

    public static function activate()
    {
        foreach (['administrator', 'editor'] as $role_name) {
            if ($role = get_role($role_name)) {
                $role->add_cap('manage_opening_times');
            }
        }
    }

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_menu', [$this, 'register_settings_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'myplugin_enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 20);
        add_action('admin_enqueue_scripts', function ($hook) {
            if (isset($_GET['page']) && $_GET['page'] === 'opening-times-create') {
                wp_enqueue_style(
                    'create-css',
                    plugin_dir_url(__FILE__) . 'assets/css/create.css',
                    [],
                    '1.0.3',
                    'all'
                );
                wp_dequeue_style('opening-times-admin');
            }

            if ($hook === 'toplevel_page_overview') {
                wp_enqueue_script('overview-js', plugin_dir_url(__FILE__) . 'assets/js/overview.js', ['jquery'], '1.0.0', true);
                wp_localize_script('overview-js', 'MyAjax', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('my_nonce')
                ]);
            }
        });
        add_action('admin_init', function () {
            foreach (['administrator', 'editor'] as $role_name) {
                if ($role = get_role($role_name)) {
                    if (!$role->has_cap('manage_opening_times')) {
                        $role->add_cap('manage_opening_times');
                    }
                }
            }
        });

        add_action('wp_enqueue_scripts', function () {
            // deine gespeicherten Optionen holen
            $settings = get_option('ot_settings', []);
            if (empty($settings['highlight_today']['enabled'])) {
                return; // aus, nix tun
            }

            $styles = $settings['highlight_today']['styles'] ?? [];
            $css = '';

            foreach (['font-weight', 'color', 'background-color'] as $prop) {
                if (!empty($styles[$prop])) {
                    $css .= $prop . ':' . $styles[$prop] . ';';
                }
            }

            if ($css) {
                // hier 'myplugin-style' durch den Handle deines Stylesheets ersetzen
                wp_add_inline_style('myplugin-style', ".day-row.today { $css }");
            }


        });

        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('holidays-js', plugins_url('assets/js/holidays.js', __FILE__), [], '1.0.0', true);

            $o = get_option('ot_settings', []);
            $region = $o['holidays_api']['region'] ?? 'nw';

            wp_localize_script('holidays-js', 'otHolidays', ['region' => $region]);

        });

        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook !== 'opening-times_page_opening-times-calender')
                return;

            wp_enqueue_style(
                'material-symbols',
                'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded',
                [],
                null
            );

            wp_enqueue_style(
                'ot-admin-calendar-css',
                plugins_url('assets/css/admin-calendar.css', __FILE__),
                [],
                filemtime(plugin_dir_path(__FILE__) . 'assets/css/admin-calendar.css')
            );

            wp_enqueue_script(
                'ot-admin-calendar-js',
                plugins_url('assets/js/admin-calendar.js', __FILE__),
                [],             
                '1.0.0',
                true          
            );
        });




    }

    public function register_settings_submenu()
    {
        add_submenu_page(
            'options-general.php',
            __('Opening Times – Einstellungen', 'opening-times'),
            __('Opening Times', 'opening-times'),
            'manage_opening_times',
            'opening-times-settings',
            [$this, 'render_settings_page'],
            3
        );
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('opening-times', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_admin_menu()
    {
        add_menu_page(
            __('Opening Times', 'opening-times'),
            __('Opening Times', 'opening-times'),
            'manage_opening_times',
            'opening-times',
            [$this, 'render_overview_page'],
            'dashicons-clock',
            25
        );

        add_submenu_page(
            'opening-times',
            __('Overview', 'opening-times'),
            __('Overview', 'opening-times'),
            'manage_opening_times',
            'opening-times',
            [$this, 'render_overview_page']
        );

        add_submenu_page(
            'opening-times',
            __('Create New Set', 'opening-times'),
            __('Create New Set', 'opening-times'),
            'manage_opening_times',
            'opening-times-create',
            [$this, 'render_create_page']
        );

        add_submenu_page(
            'opening-times',
            __('Edit', 'opening-times'),
            __('Edit', 'opening-times'),
            'manage_opening_times',
            'opening-times-edit',
            [$this, 'render_edit_page']
        );

        add_submenu_page(
            'opening-times',
            __('Vacation / Absence', 'opening-times'),
            __('Vacation / Absence', 'opening-times'),
            'manage_options',
            'opening-times-holidays',
            [$this, 'render_holidays_page']
        );

        add_submenu_page(
            'opening-times',
            __('Calendar', 'opening-times'),
            __('Calendar', 'opening-times'),
            'manage_options',
            'opening-times-calender',
            [$this, 'render_calender_page']
        );
    }
    public function render_overview_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/times_overview.php';
    }

    public function render_create_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/times_create.php';

    }

    public function render_edit_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/times_edit.php';
    }

    public function render_settings_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/times_settings.php';
    }

    public function render_holidays_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/holidays.php';
    }

    public function render_calender_page()
    {
        include plugin_dir_path(__FILE__) . 'templates/calender.php';
    }

    public function enqueue_admin_styles($hook)
    {
        $allowed = [
            'toplevel_page_opening-times',        
            'opening-times_page_opening-times',  
            'settings_page_opening-times-settings'
        ];
        if (!in_array($hook, $allowed, true)) {
            return;
        }

        $path = plugin_dir_path(__FILE__) . 'assets/css/admin.css';

        wp_enqueue_style(
            'opening-times-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            ['dashicons', 'common'],
            '1.0.1',
            'all'
        );
    }

    public function enqueue_admin_scripts()
    {

        wp_enqueue_script(
            'overview-js',
            plugin_dir_url(__FILE__) . 'assets/js/overview.js',
            [],
            '1.0.1',
            true
        );

        wp_enqueue_script(
            'holidays-js',
            plugin_dir_url(__FILE__) . 'assets/js/holidays.js',
            [],
            '1.0.1',
            true
        );

        wp_enqueue_script(
            'settings-js',
            plugin_dir_url(__FILE__) . 'assets/js/settings.js',
            [],
            '1.0.1',
            true
        );

        wp_localize_script('overview-js', 'OpeningTimesData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('load_opening_times_nonce'),
            'action' => 'load_opening_times',
        ]);

        wp_enqueue_script(
            'create',
            plugin_dir_url(__FILE__) . 'assets/js/create.js',
            [],
            '1.0.1',
            true
        );


    }

    public function enqueue_frontend_scripts()
    {
        wp_enqueue_script(
            'ot-tz-cookie',
            plugin_dir_url(__FILE__) . 'assets/js/tz-cookie.js',
            [],
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'overview-js',
            plugin_dir_url(__FILE__) . 'assets/js/overview.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script('overview-js', 'OpeningTimesData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('load_opening_times_nonce'),
            'action' => 'load_opening_times',
        ]);

        wp_localize_script('overview-js', 'OT_Holidays', [
            'dates' => ot_get_holiday_dates(), 
            'region' => ot_get_region(),
        ]);

    }


    function myplugin_enqueue_styles()
    {
        wp_enqueue_style(
            'myplugin-style',
            plugin_dir_url(__FILE__) . 'assets/css/frontend.css',
            [],
            '1.0.1',
            'all'
        );
    }


}



Opening_Times::get_instance();