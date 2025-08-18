<?php
if (!defined('ABSPATH')) { exit; }

/* ==============================
   POST handling
   ============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $set_name      = sanitize_text_field($_POST['set_name'] ?? '');
    $opening_times = $_POST['opening_times'] ?? [];
    $selected_set  = $_POST['select_set'] ?? '';
    $check_delete  = !empty($_POST['delete']);

    if ($set_name && !empty($opening_times)) {
        ot_create_table($set_name);

        if ($check_delete) {
            delete_set($set_name);
            echo '<div>Set deleted: ' . esc_html($set_name) . '</div>';
        } else if ($selected_set && $selected_set !== 'create_new') {
            update_opening_times($set_name, $opening_times);
            echo '<div>Opening Times updated for: ' . esc_html($set_name) . '</div>';
        } else {
            ot_save_opening_times($set_name, $opening_times);
            echo '<div>Opening Times saved for: ' . esc_html($set_name) . '</div>';
        }
    }
}

$sets         = get_all_sets();
$selectedSet  = $_POST['select_set'] ?? '';
$openingTimes = [];
$dates = get_all_absences();
$vactaion = vacation_difference($dates[0]['start'] ?? '', $dates[0]['end'] ?? '');


if ($selectedSet !== '') {
    $openingTimes = get_opening_times($selectedSet);
}

$days = [
    'Monday'    => __('Monday', 'opening-times'),
    'Tuesday'   => __('Tuesday', 'opening-times'),
    'Wednesday' => __('Wednesday', 'opening-times'),
    'Thursday'  => __('Thursday', 'opening-times'),
    'Friday'    => __('Friday', 'opening-times'),
    'Saturday'  => __('Saturday', 'opening-times'),
    'Sunday'    => __('Sunday', 'opening-times'),
];
?>

<div class="create-wrapper">
    <div class="select_wrapper">
        <form id="setForm" method="POST" action="">
            <label>Select Set:</label>
            <select name="select_set" id="select_set" onchange="this.form.submit()">
                <option value="">--- Sets ---</option>
                <option value="create_new" <?php echo ($selectedSet === 'create_new') ? 'selected' : ''; ?>>Create New Set</option>
                <?php
                foreach ($sets as $set) {
                    echo '<option value="' . esc_attr($set) . '" ' . selected($set, $selectedSet, false) . '>'
                       . esc_html($set)
                       . '</option>';
                }
                ?>
            </select>
        </form>
    </div>

    <?php
    if ($selectedSet === '') {
        // nichts anzeigen
    } else if ($selectedSet === 'create_new') {
    ?>
        <form class="times_create_form" method="POST" action="">
            <div class="set_name_input">
                <label>Set Name: </label>
                <input class="input_set_name" type="text" name="set_name" placeholder="Please enter a Setname" required>
            </div>

            <div class="wrapper_days">
                <?php
                foreach ($days as $key => $label) {
                    $dayKey       = $key;
                    $closedForDay = 0;
                ?>
                    <div class="days" data-next-index="1">
                        <label><?php echo esc_html($label); ?></label>

                        <input type="hidden" name="opening_times[<?php echo esc_attr($dayKey); ?>][closed]" value="0">
                        <input id="closed_<?php echo esc_attr($dayKey); ?>" type="checkbox"
                               name="opening_times[<?php echo esc_attr($dayKey); ?>][closed]" value="1"
                               <?php checked($closedForDay, 1); ?>>
                        <label for="closed_<?php echo esc_attr($dayKey); ?>" class="closed_checkbox">Closed</label>

                        <!-- erste Zeile mit Index 0 -->
                        <div class="time_row" data-day="<?php echo esc_attr($dayKey); ?>">
                            <input class="time_input" type="time"
                                   name="opening_times[<?php echo esc_attr($dayKey); ?>][times][0][open_time]">
                            <input class="time_input" type="time"
                                   name="opening_times[<?php echo esc_attr($dayKey); ?>][times][0][close_time]">
                        </div>

                        <span class="new_time_row" data-day="<?php echo esc_attr($dayKey); ?>"></span>

                        <div class="button_list">
                            <button type="button" class="add_row" data-day="<?php echo esc_attr($dayKey); ?>">Add Time</button>
                            <button type="button" class="delete_row" data-day="<?php echo esc_attr($dayKey); ?>">Delete Time</button>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>

            <button class="times_submit_button" type="submit">Submit</button>
        </form>
    <?php
    } else {
    ?>
        <form class="times_edit_form" method="POST" action="">
            <input type="hidden" name="select_set" value="<?php echo esc_attr($selectedSet); ?>">

            <div class="set_name_input">
                <label>Set Name: </label>
                <input class="input_set_name" value="<?php echo esc_attr($selectedSet); ?>" type="text" name="set_name" readonly>
                <div class="checkbox_list">
                    <div class="checkbox">
                        <input id="delete" type="checkbox" name="delete">
                        <label for="delete" class="delete_checkbox">Delete</label>
                    </div>
                </div>
            </div>

            <div class="wrapper_days">
                <?php
                foreach ($days as $key => $label) {
                    $dayKey       = $key;
                    $dayData      = $openingTimes[$dayKey] ?? ['closed' => 0, 'times' => []];
                    $closedForDay = (int)!empty($dayData['closed']);
                    $times        = is_array($dayData['times'] ?? null) ? $dayData['times'] : [];
                    if (empty($times)) { $times = [[]]; }
                    $nextIndex    = count($times);
                ?>
                    <div class="days" data-next-index="<?php echo esc_attr($nextIndex); ?>">
                        <label><?php echo esc_html($label); ?></label>

                        <input type="hidden" name="opening_times[<?php echo esc_attr($dayKey); ?>][closed]" value="0">
                        <input id="closed_<?php echo esc_attr($dayKey); ?>" type="checkbox"
                               name="opening_times[<?php echo esc_attr($dayKey); ?>][closed]" value="1"
                               <?php checked($closedForDay, 1); ?>>
                        <label for="closed_<?php echo esc_attr($dayKey); ?>" class="closed_checkbox">Closed</label>

                        <?php
                        $i = 0;
                        foreach ($times as $interval) {
                            $open  = isset($interval['open_time'])  ? esc_attr($interval['open_time'])  : '';
                            $close = isset($interval['close_time']) ? esc_attr($interval['close_time']) : '';
                        ?>
                            <div class="time_row" data-day="<?php echo esc_attr($dayKey); ?>">
                                <input type="time"
                                       name="opening_times[<?php echo esc_attr($dayKey); ?>][times][<?php echo (int)$i; ?>][open_time]"
                                       value="<?php echo $open; ?>">
                                <input type="time"
                                       name="opening_times[<?php echo esc_attr($dayKey); ?>][times][<?php echo (int)$i; ?>][close_time]"
                                       value="<?php echo $close; ?>">
                            </div>
                        <?php
                            $i++;
                        }
                        ?>

                        <span class="new_time_row" data-day="<?php echo esc_attr($dayKey); ?>"></span>

                        <div class="button_list">
                            <button type="button" class="add_row" data-day="<?php echo esc_attr($dayKey); ?>">Add Time</button>
                            <button type="button" class="edit_delete_row" data-day="<?php echo esc_attr($dayKey); ?>">Delete Time</button>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>

            <button class="times_submit_button" type="submit">Submit</button>
        </form>
    <?php
    }
    ?>
</div>
<?php ?>