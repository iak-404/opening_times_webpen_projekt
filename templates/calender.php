<?php

?>
<div class="wrap ot-cal-page">
  <h1><?php esc_html_e('Calendar', 'opening-times'); ?></h1>

  <div class="calendar-wrapper">
    <div class="calendar-container">
      <header class="calendar-header">
        <p class="calendar-current-date"></p>
        <div class="calendar-navigation">
          <span id="calendar-prev" class="material-symbols-rounded">chevron_left</span>
          <span id="calendar-next" class="material-symbols-rounded">chevron_right</span>
        </div>
      </header>

      <div class="calendar-body">
        <ul class="calendar-weekdays">
          <li>Sun</li><li>Mon</li><li>Tue</li><li>Wed</li><li>Thu</li><li>Fri</li><li>Sat</li>
        </ul>
        <ul class="calendar-dates"></ul>
      </div>
    </div>
  </div>
</div>
