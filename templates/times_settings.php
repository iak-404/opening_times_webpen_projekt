<?php

echo '<br><br>';

$list = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

$today = new DateTime();

$weekStart = clone $today;
$weekStart->modify('monday this week');


    foreach ($list as $day) {
        $date = clone $weekStart;

        $date->modify($day);

        echo '<br>' . ucfirst($day) . ' ' . $date->format('d.m.y');
    }
