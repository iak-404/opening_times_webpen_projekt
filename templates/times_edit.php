<?php
// Beispiel: Sets laden (z.B. aus DB)
$sets = get_all_sets();

// Ausgewähltes Set aus POST (Formular)
$selectedSet = $_POST['select_set'] ?? '';

// Öffnungszeiten-Array vorbereiten
$openingTimes = [];

if ($selectedSet !== '') {
    // Öffnungszeiten für das ausgewählte Set laden
    $openingTimes = get_opening_times($selectedSet); // sollte ein Array zurückgeben
}
?>

<form id="setForm" method="POST" action="">
    <label>Select Set:</label>
    <select name="select_set" id="select_set" onchange="this.form.submit()">
        <option value="">--- Sets ---</option>
        <?php foreach ($sets as $set): ?>
            <option value="<?php echo htmlspecialchars($set); ?>" <?php selected($set, $selectedSet); ?>>
                <?php echo htmlspecialchars($set); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div id="opening-times-output">
    <?php
    if (is_array($openingTimes) && count($openingTimes) > 0) {
        foreach ($openingTimes as $day => $times) {
            echo '<strong>' . htmlspecialchars($day) . ':</strong><br>';

            foreach ($times as $time) {
                if ($time['open_time'] === '00:00' && $time['close_time'] === '00:00') {
                    echo 'Geschlossen<br>';
                } else {
                    echo htmlspecialchars($time['open_time']) . ' - ' . htmlspecialchars($time['close_time']) . '<br>';
                }
            }
            echo '<br>';
        }
    } elseif ($selectedSet !== '') {
        echo 'Keine Öffnungszeiten gefunden.';
    }
    ?>
</div>
