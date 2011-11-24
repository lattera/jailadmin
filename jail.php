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
            default:
                system($cmd);
                break;
        }
    } while(true);
}

function batch() {

}

function main($args) {
    if (count($args) < 2) {
        echo "USAGE: " . $args[0] . " [i] [b [commands]]\n";
        return;
    }

    switch ($args[1]) {
        case "i":
            interactive();
            break;
        case "b":
            batch();
            break;
        default:
            echo "USAGE: " . $args[0] . " [i] [b [commands]]\n";
            break;
    }
}

main($argv);

?>
