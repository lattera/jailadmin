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
                if (count($parsed) == 2)
                    config_jail($parsed[1]);
                else
                    config_jail();
                break;
            case "new":
                new_jail();
                break;
            case "back":
                return;
            case "delete":
                $jail = Jail::findByName($parsed[1]);
                $jail->Remove();
            case "status":
                $jail = Jail::findByName($parsed[1]);
                $jail->View();
                break;
            case "start":
                $jail = Jail::findByName($parsed[1]);
                $jail->Start();
                break;
            case "stop":
                $jail = Jail::findByName($parsed[1]);
                $jail->Stop();
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

function config_jail($name="") {
    if (strlen($name) == 0) {
        echo "jail: ";
        $name = read_stdin();
    }

    $jail = Jail::findByName($name);
    while ($jail === false) {
        echo "Invalid jail.\n";
        echo "jail: ";
        $name = read_stdin();
        $jail = Jail::findByName($name);
    }

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
            case "network":
                config_network($jail);
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

function config_network($jail) {
    $prompt = "jail:" . $jail->getJailName() . ":network> ";

    do {
        echo "$prompt";
        $cmd = read_command();

        if (strlen($cmd) == 0)
            continue;

        $parsed = explode(" ", $cmd);
        switch ($parsed[0]) {
            case "viewall":
                foreach ($jail->associatedEpairs() as $n)
                    $n->View();
                break;
            case "new":
                new_epair($jail);
                break;
            case "delete":
                delete_epair($jail, $parsed[1]);
                break;
            case "back":
                return;
            default:
                $config_epair = false;
                foreach ($jail->associatedEpairs() as $n)
                    if (!strcmp($parsed[0], $n->getEpairDevice()))
                        $config_epair = true;

                if ($config_epair)
                    config_epair($jail, $parsed[0]);
                else
                    system($cmd);
                break;
        }
    } while(true);
}

function new_epair($jail) {
    $jails = Jail::findAll();
    $bridges = Bridge::findAll();

    $valid_bridge = false;
    $valid_device = true;
    $valid_ip = true;

    do {
        echo "Bridge: ";
        $bridge_name = read_stdin();

        echo "Device: ";
        $device = read_stdin();

        echo "IP: ";
        $ip = read_stdin();

        foreach ($jails as $j) {
            foreach ($j->associatedEpairs() as $n)
                if (!strcmp($device, $n->getEpairDevice()))
                    $valid_device = false;
                if (!strcmp($ip, $n->getIp()))
                    $valid_ip = false;
        }

        foreach ($bridges as $bridge) {
            if (!strcmp($bridge_name, $bridge->getBridgeName()))
                $valid_bridge = true;
            if (!strcmp($ip, $bridge->getBridgeIp()))
                $valid_ip = false;
        }

        if ($valid_bridge == false)
            echo "Invalid bridge\n";
        if ($valid_device == false)
            echo "Device already taken\n";
        if ($valid_ip == false)
            echo "IP already taken\n";
    } while ($valid_bridge == false || $valid_device == false || $valid_ip == false);

    $bridge = null;
    foreach ($bridges as $bridge)
        if (!strcmp($bridge_name, $bridge->getBridgeName()))
            break;

    $n = new Epair;
    $n->associateBridge($bridge);
    $n->setIp($ip);
    $n->setEpairDevice($device);
    $n->setJailId($jail->getJailId());

    $n->Persist();

    $epairs = $jail->associatedEpairs();
    array_push($epairs, $n);
    $jail->associateEpairs($epairs);
}

function delete_epair($jail, $name) {
    $newlist = array();
    $epairs = $jail->associatedEpairs();

    $e = null;
    foreach ($epairs as $n) {
        if (!strcmp($name, $n->getEpairDevice()))
            $e = $n;
        else
            array_push($newlist, $n);
    }

    $e->Remove();
    $jail->associateEpairs($newlist);
}

function config_epair($jail, $name) {
    $n = null;

    foreach ($jail->associatedEpairs() as $e)
        if (!strcmp($name, $e->getEpairDevice()))
            $n = $e;

    $prompt = "jail:" . $jail->getJailName() . ":" . $name . "> ";
    do {
        echo "$prompt";
        $cmd = read_command();

        if (strlen($cmd) == 0)
            continue;

        $parsed = explode(" ", $cmd);
        switch ($parsed[0]) {
            case "back":
                return;
            case "view":
                $n->View();
                break;
            case "set":
                if (count($parsed) != 3)
                    break;

                switch ($parsed[1]) {
                    case "ip":
                        $jails = Jail::findAll();
                        $bridges = Bridge::findAll();
                        $available = true;
                        foreach ($jails as $j)
                            foreach ($j->associatedEpairs() as $e)
                                if (strcmp($e->getEpairDevice(), $n->getEpairDevice()))
                                    if (!strcmp($parsed[2], $e->getIp()))
                                        $available = false;

                        foreach ($bridges as $bridge)
                            if (!strcmp($parsed[2], $bridge->getBridgeIp()))
                                $available = false;

                        if ($available == true) {
                            $n->setIp($parsed[2]);
                            echo "IP set to " . $parsed[2] . "\n";
                        }
                        else
                            echo "IP already taken.\n";

                        break;
                    case "bridge":
                        $bridges = Bridge::findAll();
                        foreach ($bridges as $bridge)
                            if (!strcmp($parsed[2], $bridge->getBridgeName()))
                                $n->associateBridge($bridge);
                        break;
                    case "device":
                        $jails = Jail::findAll();
                        $available = true;
                        foreach ($jails as $j)
                            foreach ($j->associatedEpairs() as $e)
                                if (!strcmp($parsed[2], $e->getEpairDevice()))
                                    $available = false;

                        if ($available)
                            $n->setEpairDevice($parsed[2]);
                        break;
                }

                break;
            default:
                system($cmd);
                break;
        }
    } while (true);
}

function new_jail() {
    $bridges = Bridge::findAll();
    $epairs = Epair::findAllForUniqueCheck();
    $bridgenames = "[";
    foreach ($bridges as $bridge)
        $bridgenames .= ((strlen($bridgenames) > 1) ? ", " : "") . "(" . $bridge->getBridgeName() . " => " . $bridge->getBridgeIp() . ")";

    $bridgenames .= "]";

    echo "Name: ";
    $name = read_stdin();

    echo "ZFS Template: ";
    $template = read_stdin();

    echo "New ZFS Dataset: ";
    $dataset = read_stdin();

    echo "Path: ";
    $path = read_stdin();

    echo "Available bridges: $bridgenames\n";

    $ips = array();
    do {
        echo "Bridge/Device/IP (eg. mainbridge/epair0/192.168.0.2) (enter blank line when finished): ";
        $ip = read_stdin();

        $combo = explode("/", $ip);
        if (strlen($ip) == 0 && count($ips) == 0)
            continue;
        elseif (count($combo) != 3)
            continue;

        $valid = false;
        foreach ($bridges as $bridge)
            if (!strcmp($combo[0], $bridge->getBridgeName()))
                $valid = true;

        if ($valid == false) {
            echo "Invalid bridge: " . $combo[0] . "\n";;
            continue;
        }

        $validip = true;
        $validepair = true;
        foreach ($epairs as $epair) {
            if (!strcmp($combo[1], $epair->getEpairDevice()))
                $validepair = false;
            if (!strcmp($combo[2], $epair->getIp()))
                $validip = false;
        }

        if ($validip == true)
            foreach ($bridges as $bridge)
                if (!strcmp($combo[2], $bridge->getIp()))
                    $validip = false;

        if ($validip == false) {
            echo "IP already taken: " . $combo[2] . "\n";
            continue;
        }

        if ($validepair == false) {
            echo "Device already taken: " . $combo[1] . "\n";
            continue;
        }

        array_push($ips, $combo);
    } while(strlen($ip) > 0 || count($ips) == 0);

    echo "Default Route: ";
    $route = read_stdin();

    $service = "";
    $services = array();
    do {
        echo "Service (enter blank line when finished): ";
        $service = read_stdin();
        if (strlen($service) > 0)
            array_push($services, $service);
    } while (strlen($service) > 0);

    $networks = array();
    foreach ($ips as $ip) {
        $bridge_name = $ip[0];
        $epair_device = $ip[1];
        $epair_ip = $ip[2];

        $bridge = Bridge::findByName($bridge_name);
        $epair = new Epair;

        $epair->associateBridge($bridge);
        $epair->setEpairDevice($epair_device);
        $epair->setIp($epair_ip);

        array_push($networks, $epair);
    }

    $j = new Jail;

    $j->associateEpairs($networks);
    $j->setJailName($name);
    $j->setPath($path);
    $j->setDataset($dataset);
    $j->setDefaultRoute($route);

    $j->Persist();

    exec("zfs clone $template $dataset");

    $fp = fopen($path . "/etc/ssh/sshd_config", "a");
    if ($fp !== false) {
        fwrite($fp, "ListenAddress $ip\n");
        fclose($fp);
    }

    $fp = fopen($path . "/etc/rc.conf", "a");
    if ($fp !== false) {
        fwrite($fp, "sshd_enable=\"YES\"\n");
        fclose($fp);
    }

    return $j->Start();

}

?>
