#!/usr/local/bin/php

<?php

include 'inc/enum.php';
include 'inc/manage.php';
include 'inc/network.php';
include 'inc/misc.php';
include 'inc/new.php';
include 'inc/remove.php';
include 'config.php';
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
