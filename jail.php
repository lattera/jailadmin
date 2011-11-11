#!/usr/local/bin/php

<?php

include 'inc/enum.php';
include 'inc/manage.php';
include 'inc/network.php';
include 'inc/misc.php';
include 'inc/new.php';
include 'config.php';

function prep_start($jail) {
    global $config;

    if (is_online($jail)) {
        if ($config["on_start"]["killexisting"] == false) {
            echo "WARNING: " . $jail["name"] . " is either still online or not fully killed. Please manually kill jail.\n";
            return false;
        }

        kill_jail($jail);
    }

    return true;
}

function start_all_jails() {
    global $jail;

    foreach ($jail as $j) {
        if (prep_start($j))
            start_jail($j);
    }
}

function main($args=array()) {
    global $jail;

    if (count($args) < 2) {
        start_all_jails();
        return;
    }

    switch ($args[1]) {
        case "start":
            if (array_key_exists($args[2], $jail) == false) {
                echo "ERROR: Jail " . $args[2] . " is not configured.\n";
                return;
            }
            if (prep_start($jail[$args[2]]))
                start_jail($jail[$args[2]]);
            break;
        case "stop":
            if (array_key_exists($args[2], $jail) == false) {
                echo "ERROR: Jail " . $args[2] . " is not configured.\n";
                return;
            }
            kill_jail($jail[$args[2]]);
            break;
        case "list":
            if (count($args) < 3) {
                echo "USAGE: " . $args[0] . " list [bridges|running|jails]\n";
                return;
            }
            switch ($args[2]) {
                case "bridges":
                    list_bridges();
                    break;
                case "running":
                    list_running();
                    break;
                case "jails":
                    list_jails();
                    break;
                default:
                    echo "USAGE: " . $args[0] . " list [bridges|running|jails]\n";
                    break;
            }
            break;
        case "new":
            $j = new_jail();
            if ($j !== false)
                if (prep_start($j))
                    start_jail($j);
            break;
        default:
            echo "USAGE: " . $args[0] . " [start|stop] <jail>\n";
            echo "    No arguments will attempt to start all configured jails.\n";
            break;
    }
}

main($argv);
?>
