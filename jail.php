#!/usr/local/bin/php

<?php

include 'inc/misc.php';
include 'inc/interactive/jail.php';
include 'inc/interactive/bridge.php';
include 'init.php';

function interactive() {
    do {
        echo "> ";
        $cmd = read_command();

        if (strlen($cmd) == 0)
            continue;

        switch ($cmd) {
            case "quit":
            case "exit":
                exit(0);
            case "jail":
                jail_command();
                break;
            case "bridge":
                bridge_command();
                break;
            case "help":
                echo "Available commands:\n";
                echo "jail - Administer jails\n";
                echo "bridge - Administer bridges\n";
                echo "\nEnter \"back\" at any > prompt to go back to the previous menu\n";
                break;
            default:
                system($cmd);
                break;
        }
    } while(true);
}

function batch($args) {
    if (count($args) < 3) {
        echo "USAGE: " . $args[0] . "[command] {arguments}\n";
        echo "Available commands:\n";
        echo "start [jail]\n";
        echo "stop [jail]\n";
        echo "upgrade [jail]\n";
        echo "snapshot [jail]\n";
        return;
    }

    switch ($args[1]) {
        case "start":
            $jail = Jail::findByName($args[2]);
            if ($jail !== false)
                $jail->Start();
            break;
        case "stop":
            $jail = Jail::findByName($args[2]);
            if ($jail !== false)
                $jail->Stop();
            break;
        case "upgrade":
            $jail = Jail::findByName($args[2]);
            if ($jail !== false)
                $jail->UpgradeWorld();
            break;
        case "snapshot":
            $jail = Jail::findByName($args[2]);
            if ($jail !== false)
                $jail->Snapshot();
            break;
        default:
            echo "Unkown command: " . $args[1] . "\n";
            break;
    }
}

function main($args) {
    if (count($args) == 1) {
        interactive();
        return;
    }

    batch($args);
}

main($argv);

?>
