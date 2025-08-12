<?php
$sets = get_all_sets();
?>

<form id="setForm" method="POST" action=""> 
    <label>Select Set:</label>
    <select name="selected_set" id="selected_set">
        <option value="">--- Sets ---</option>
        <?php foreach ($sets as $set): ?>
            <option value="<?php echo esc_attr($set); ?>">
                <?php echo esc_html($set); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>


<div id="opening_times_result"></div>
<?php


?>
