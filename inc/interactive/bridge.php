<?php

function bridge_command() {
    do {
        echo "bridge> ";
        $cmd = read_command();

        $parsed = explode(" ", $cmd);

        switch ($parsed[0]) {
            case "new":
                new_bridge();
                break;
            case "delete":
                delete_bridge($parsed[1]);
                break;
            case "status":
                $bridge = Bridge::findByName($parsed[1]);
                $bridge->View();
                break;
            case "viewall":
                $bridges = Bridge::findAll();
                foreach ($bridges as $bridge)
                    $bridge->View();
                break;
            case "help":
                echo "Available commands:\n";
                echo "new - create new bridge\n";
                echo "delete [bridge name] - delete a bridge\n";
                echo "status [bridge name]- view the status of a bridge\n";
                echo "viewall - view all bridges\n";
            case "back":
                return;
            default:
                system($cmd);
                break;
        }
    } while (true);
}

function new_bridge() {
    $bridges = Bridge::findAll();

    do {
        echo "Name: ";
        $name = read_stdin();

        echo "Device: ";
        $device = read_stdin();

        echo "IP: ";
        $ip = read_stdin();

        $valid = true;
        foreach ($bridges as $bridge) {
            if (!strcmp($name, $bridge->getBridgeName())) {
                echo "Name already taken\n";
                $valid = false;
            }
            if (!strcmp($device, $bridge->getBridgeDevice())) {
                echo "Device already taken\n";
                $valid = false;
            }
        }

        if (Epair::IPAvailable($ip) == false) {
            echo "IP already taken\n";
            $valid = false;
        }

        if ($valid == false)
            continue;

        $bridge = new Bridge;
        $bridge->setBridgeName($name);
        $bridge->setBridgeDevice($device);
        $bridge->setBridgeIp($ip);

        $bridge->Persist();
        break;
    } while (true);
}

function delete_bridge($name) {
    $jails = Jail::findAll();
    $bridge = Bridge::findByName($name);

    if (count($bridge->relatedJails()) > 0) {
        echo "Cannot delete bridge. Jail(s) are still assigned to bridge.\n";
        return;
    }

    $bridge->Remove();
}

?>
