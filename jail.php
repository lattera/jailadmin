#!/usr/local/bin/php

<?php

include 'inc/enum.php';
include 'inc/start.php';
include 'inc/kill.php';
include 'inc/misc.php';
include 'config.php';

foreach ($jail as $j) {
    if (is_online($j)) {
        if ($config["on_start"]["killexisting"] == false) {
            echo "WARNING: " . $j["name"] . " is either still online or not fully killed. Please manually kill jail.\n";
            continue;
        }

        kill_jail($j);
    }

    start_jail($j);
}

?>
