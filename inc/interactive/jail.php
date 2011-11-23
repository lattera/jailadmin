<?php

function jail_command() {
    $prompt = "jail> ";

    do {
        echo $prompt;
        $cmd = read_command();

        if (strlen($cmd) == 0)
            continue;

        $parsed = explode(" ", $cmd);

        switch ($parsed[0]) {
            case "config":
                config_jail();
                break;
            case "new":
                new_jail();
                break;
            case "back":
                return;
            case "status":
                break;
            default:
                system($cmd);
                break;
        }
    } while (true);
}

function config_jail() {
    echo "jail: ";
    $name = read_stdin();

    $jail = Jail::findByName($name);
    while ($jail->count() == 0) {
        echo "Invalid jail.\n";
        echo "jail: ";
        $name = read_stdin();
        $jail = Jail::findByName($name);
    }

    $jail = $jail->getRecord(0);

    do {
        echo "jail:$name> ";
        $cmd = read_command();

        $parsed = explode(" ", $cmd);
        switch ($parsed[0]) {
            case "back":
                return;
            case "commit":
                $jail->store();
                break;
            case "set":
                break;
            case "view":
                break;
            case "help":
                break;
        }
    } while (true);
}

?>
