<?php
if (!defined('ABSPATH'))
    exit;

// Zugriffscheck (nur Admin/Redakteur mit deiner Cap)
if (!current_user_can('manage_opening_times')) {
    wp_die(__('Keine Berechtigung.', 'opening-times'), 403);
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Opening Times – Einstellungen', 'opening-times'); ?></h1>

    <form method="post" action="options.php">
        <?php
        // Nonce/Group
        settings_fields('ot_settings_group');
        // Alle Felder rendern (Sektionen/Felder aus admin_init)
        do_settings_sections('opening-times-settings');
        submit_button();
        ?>
    </form>
</div>