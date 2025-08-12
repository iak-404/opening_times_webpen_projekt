<?php

$url = 'https://ferien-api.de/api/v1/holidays/NW/2025';

$respnse = file_get_contents($url);

if ($respnse === false) {
    echo 'Error, no Data.';
    exit;
}

$data = json_decode($respnse, true);

if ($data === null) {
    echo 'JSON Error';
    exit;
}

foreach ($data as $holiday) {
    echo 'Name: ' . htmlspecialchars($holiday['stateCode']) . '<br>';
    echo 'Start: ' . htmlspecialchars($holiday['start']) . '<br>';
    echo 'Ende: ' . htmlspecialchars($holiday['end']) . '<br><br>';
}
?>

<?php
if (isset($_POST['ot_save_absence'])) {
    // Nonce prüfen
    if (!isset($_POST['ot_absence_nonce']) || 
        !wp_verify_nonce($_POST['ot_absence_nonce'], 'save_absence_action')) {
        
        echo '<div class="notice notice-error"><p>Nonce-Fehler.</p></div>';
    } else {
        // Felder bereinigen
        $title = sanitize_text_field($_POST['title']);
        $start = sanitize_text_field($_POST['start_date']);
        $end   = sanitize_text_field($_POST['end_date']);

        // Speichern in der Datenbank
        save_absence_period($start, $end, $title);

        echo '<div class="notice notice-success"><p>Urlaub gespeichert.</p></div>';
    }
}
?>

<div class="wrap">
    <h1>Urlaub hinzufügen</h1>

    <form method="post" action="">
        <?php wp_nonce_field('save_absence_action', 'ot_absence_nonce'); ?>

        <label for="title">Titel: </label>
        <input type="text" id="title" name="title" required>

        <br><br>

        <label for="start_date">Startdatum:</label>
        <input type="date" id="start_date" name="start_date" required>

        <br><br>

        <label for="end_date">Enddatum:</label>
        <input type="date" id="end_date" name="end_date" required>

        <br><br>

        <input type="submit" name="ot_save_absence" value="Speichern" class="button button-primary">
    </form>
</div>

<?php
