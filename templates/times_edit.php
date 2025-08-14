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

<div id="opening-times-output"></div>


    <h2>Opening Times: </h2>
    <div class="ot_wrapper">
        <div id="opening-times" class="opening-times">
            <?php
            if (is_array($openingTimes) && count($openingTimes) > 0) {
                foreach ($openingTimes as $day => $times) { ?>

                    <div class="daydate">
                        <div class="day"><?php echo $day ?></div>
                    </div>
                    <div class="times">
                        <?php foreach ($times as $time) { ?>
                            <div class="time"><input type="time" value="<?php echo $time['open_time'] ?>" name=""></div>
                            <div class="time"><input type="time" value="<?php echo $time['close_time'] ?>" name=""></div>
                        <?php } ?>
                    </div>
                    
                <!-- </div>
            </div>
        </div> -->

    <?php }
            } elseif ($selectedSet !== '') {
                echo 'Keine Öffnungszeiten gefunden.';
            }
                ?>