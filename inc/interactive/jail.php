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
            case "viewall":
                foreach (Jail::findAll() as $jail)
                    $jail->View();
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
                if (count($parsed) != 3)
                    break;

                switch ($parsed[1]) {
                    case "name":
                        $jail->setName($parsed[2]);
                        break;
                    case "path":
                        $jail-setPath($parsed[2]);
                        break;
                    case "dataset":
                        $jail->setDataset($parsed[2]);
                        break;
                }

                break;
            case "view":
                $jail->View();
                break;
            case "help":
                echo "Available commands: back, commit, set, view, network\n";
                echo "set - set jail parameters\n";
                echo "      name\n";
                echo "      path\n";
                echo "      dataset\n";
                break;
            default:
                system($cmd);
                break;
        }
    } while (true);
}

?>
