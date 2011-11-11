<?php

function new_jail() {
    global $jail;
    global $bridge;

    echo "Name: ";
    $name = read_stdin();

    echo "ZFS Template: ";
    $template = read_stdin();

    echo "New ZFS Dataset: ";
    $dataset = read_stdin();

    echo "Path: ";
    $path = read_stdin();

    echo "Network Type [EPAIR]: ";
    $nettype = read_stdin();

    echo "Network Interface: ";
    $inet = read_stdin();

    echo "Bridge []: ";
    $bridgename = read_stdin();

    echo "IP: ";
    $ip = read_stdin();

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

    $jtype = NetTypes::EPAIR;
    switch ($nettype) {
        case "EPAIR":
        case "":
            $nettype =  "\$jail[\"$name\"][\"nettype\"] = NetTypes::EPAIR;\n";
            break;
        default:
            echo "ERROR: Unknown Network Type: $nettype\n";
            return false;
    }

    if (strlen($bridgename) > 0) {
        if (bridge_exists($bridgename) == false) {
            echo "ERROR: Bridge $bridgename not configured\n";
            return false;
        }
    }

    if (ip_available($ip) == false) {
        echo "ERROR: IP already taken\n";
        return false;
    }

    if (inet_available($inet) == false) {
        echo "ERROR: Network interface already taken\n";
        return false;
    }

    exec("zfs clone $template $dataset");

    $fp = fopen("config.php", "a");

    fwrite($fp, "\$jail[\"$name\"][\"name\"] = \"$name\";\n");
    fwrite($fp, $nettype);
    fwrite($fp, "\$jail[\"$name\"][\"inet\"] = \"$inet\";\n");
    if (strlen($bridgename) > 0)
        fwrite($fp, "\$jail[\"$name\"][\"bridge\"] = \"$bridgename\";\n");
    fwrite($fp, "\$jail[\"$name\"][\"path\"] = \"$path\";\n");
    fwrite($fp, "\$jail[\"$name\"][\"ip\"] = \"$ip\";\n");
    fwrite($fp, "\$jail[\"$name\"][\"route\"] = \"$route\";\n");

    $service = "";
    if (count($services) > 0)
        foreach ($services as $s)
            $service .= ((strlen($service) > 0) ? ", " : "") . "\"$s\"";

    if (strlen($service) > 0)
        fwrite($fp, "\$jail[\"$name\"][\"services\"] = array($service);\n");

    fwrite($fp, "\n");
    fclose($fp);

    $j = array();
    $j["name"] = $name;
    $j["nettype"] = $jtype;
    $j["inet"] = $inet;
    if (strlen($bridgename) > 0)
        $j["bridge"] = $bridgename;
    $j["path"] = $path;
    $j["ip"] = $ip;
    $j["route"] = $route;
    if (count($services) > 0)
        $j["services"] = $services;

    $fp = fopen($path . "/etc/ssh/sshd_config", "a");
    if ($fp !== false) {
        fwrite($fp, "ListenAddress $ip\n");
        fclose($fp);
    }

    $fp = fopen($path . "/etc/rc.conf", "a+");
    if ($fp !== false) {
        fwrite($fp, "sshd_enable=\"YES\"\n");
        fclose($fp);
    }

    return $j;
}

?>
