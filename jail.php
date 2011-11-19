#!/usr/local/bin/php

<?php

include 'inc/enum.php';
include 'inc/manage.php';
include 'inc/misc.php';
include 'config.php';
include 'classes/bridge.php';
include 'classes/jail.php';
include 'inc/commands.php';

$count = count($argv)-1;

foreach ($commands as $command) {
    if ($count >= $command->getProperty("minArgs")) {
        if ($command->Test($argv)) {
            if ($command->Run($argv) == false) {
                echo "Command failed\n";
            }
        }
    }
}

?>
