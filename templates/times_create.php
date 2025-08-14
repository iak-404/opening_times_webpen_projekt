<?php



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $set_name = sanitize_text_field($_POST['set_name'] ?? '');
    $opening_times = $_POST['opening_times'] ?? [];
    $selected_set = $_POST['select_set'] ?? '';
    $check_delete = $_POST['delete'] ?? '';

    if ($set_name && !empty($opening_times)) {
        ot_create_table($set_name);
        if ($check_delete) {
            delete_set($set_name);
        } else if ($selected_set && $selected_set !== 'create_new') {
            update_opening_times($set_name, $opening_times);
            echo '<div>Opening Times updated for: ' . esc_html($set_name) . '</div>';
        } else {
            ot_save_opening_times($set_name, $opening_times);
            echo '<div>Opening Times saved for: ' . esc_html($set_name) . '</div>';
        }
    }
}



$sets = get_all_sets();

$selectedSet = $_POST['select_set'] ?? '';

$openingTimes = [];

if ($selectedSet !== '') {
    $openingTimes = get_opening_times($selectedSet);
} else {

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
?>
<div class="create-wrapper">
    <div class="select_wrapper">
        <form id="setForm" method="POST" action="">
            <label>Select Set:</label>
            <select name="select_set" id="select_set" onchange="this.form.submit()">
                <option value="">--- Sets ---</option>
                <option value="create_new">Create New Set</option>
                <?php foreach ($sets as $set): ?>
                    <option value="<?php echo htmlspecialchars($set); ?>" <?php selected($set, $selectedSet); ?>>
                        <?php echo htmlspecialchars($set); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php
    if ($selectedSet === '') { ?>

    <?php } else if ($selectedSet === 'create_new') { ?>
            <form class="times_create_form" method="POST" action="">
                <div class="set_name_input">
                    <label>Set Name: </label>
                    <input class="input_set_name" type="text" name="set_name" placeholder="Please enter a Setname" required>
                </div>
                <div class="wrapper_days">
                    <?php
                    foreach ($days as $key => $label) {
                        ?>
                        <div class='days'>
                            <label><?php echo $label ?> </label>
                            <div class="checkbox_list">
                                <input id="closed_<?php echo $day . '_' . $idx ?>" type="checkbox"
                                    name="opening_times[<?php echo $day ?>][<?php echo $idx ?>][closed]" value="1" <?php echo (!empty($interval['closed']) ? 'checked' : '') ?>>
                                <label for="closed_<?php echo $day . '_' . $idx ?>" class="closed_checkbox">Closed</label>
                            </div>
                            <div class="time_row" data-day="<?php echo $key ?>">
                                <input class="time_input" type='time' name='opening_times[<?php echo $key ?>][0][open_time]'>
                                <input class="time_input" type='time' name='opening_times[<?php echo $key ?>][0][close_time]'>
                            </div>
                            <span class='new_time_row' data-day='<?php echo $key ?>'></span>
                            <div class="button_list">
                                <button type='button' class='add_row' data-day='<?php echo $key ?>'>Add Time</button>
                                <button type='button' class='delete_row' data-day='<?php echo $key ?>'>Delete Time</button>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
                <button class="times_submit_button" type="submit">Submit</button>
            </form>

    <?php } else { ?>
            <form class="times_edit_form" method="POST" action="">
                <input type="hidden" name="select_set" value="<?php echo esc_attr($selectedSet); ?>">
                <div class="set_name_input">
                    <label>Set Name: </label>
                    <input class="input_set_name" value="<?php echo $selectedSet ?>" type="text" name="set_name" readonly>
                    <div class="checkbox_list">
                        <div class="checkbox"><input id="delete" type="checkbox" name="delete"><label for="delete"
                                class="delete_checkbox">Delete</label></div>
                    </div>
                </div>
                <div class="wrapper_days">
                <?php foreach ($openingTimes as $day => $intervals) { ?>
                        <div class='days'>
                            <label><?php echo $day ?></label>
                            <div class="checkbox_list">
                                <div class="checkbox"><input id="closed" type="checkbox"
                                        name="opening_times[<?php echo $key ?>]['closed']"><label for="closed"
                                        class="closed_checkbox">Closed</label></div>
                            </div>
                        <?php $idx = 0;
                        foreach ($intervals as $interval) { ?>
                                <div class="time_row" data-day='<?php echo $day ?>'>
                                    <input type='time' value="<?php echo htmlspecialchars($interval['open_time'] ?? ''); ?>"
                                        name='opening_times[<?php echo $day ?>][<?php echo $idx ?>][open_time]'>
                                    <input type='time' value="<?php echo htmlspecialchars($interval['close_time'] ?? ''); ?>"
                                        name='opening_times[<?php echo $day ?>][<?php echo $idx ?>][close_time]'>
                                </div>
                            <?php $idx++;
                        } ?>
                            <span class='new_time_row' data-day='<?php echo $day ?>'></span>
                            <div class="button_list">
                                <button type='button' class='add_row' data-day='<?php echo $day ?>'>Add Time</button>
                                <button type='button' class='edit_delete_row' data-day='<?php echo $day ?>'>Delete Time</button>
                            </div>
                        </div>
                <?php }
                ?>
                </div>
                <button class="times_submit_button" type="submit">Submit</button>
            </form>
    <?php } ?>
</div>
<?php





