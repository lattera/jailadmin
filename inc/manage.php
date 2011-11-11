<?php

function kill_jail($jail) {
    exec("jail -r " . $jail["name"]);
    exec("umount " . $jail["path"] . "/dev");
    if ($jail["nettype"] == NetTypes::EPAIR)
        exec("ifconfig " . $jail["inet"] . "a destroy");
}

function start_jail($jail) {
    global $config;
    global $bridge;

    # Step 1: Initial networking, set up in the host
    if ($jail["nettype"] == NetTypes::EPAIR)
        exec("ifconfig " . $jail["inet"] . " create");

    if (array_key_exists("bridge", $jail)) {
        if (prep_bridge($jail["bridge"]) == false) {
            if ($jail["nettype"] == NetTypes::EPAIR)
                exec("ifconfig " . $jail["inet"] . "a destroy");

            return;
        }

        if ($jail["nettype"] == NetTypes::EPAIR)
            exec("ifconfig " . $bridge[$jail["bridge"]]["inet"] . " addm " . $jail["inet"] . "a");
    }

    if ($jail["nettype"] == NetTypes::EPAIR)
        exec("ifconfig " . $jail["inet"] . "a up");

    # Step 2: Start jail
    exec("mount -t devfs devfs " . $jail["path"] . "/dev");
    exec("jail -c vnet name=" . $jail["name"] . " host.hostname=" . $jail["name"] . " path=" . $jail["path"] . " persist");

    # Step 3: Set up networking in the jail
    if ($jail["nettype"] == NetTypes::EPAIR) {
        exec("ifconfig " . $jail["inet"] . "b vnet " . $jail["name"]);
        exec("jexec " . $jail["name"] . " ifconfig " . $jail["inet"] . "b " . $jail["ip"]);
    }

    exec("jexec " . $jail["name"] . " route add default " . $jail["route"]);

    # Step 4: Start services
    if (array_key_exists("services", $jail))
        foreach($jail["services"] as $service)
            exec("jexec " . $jail["name"] . " " . $service . " start");
}

function is_online($jail) {
    $o = exec("mount | grep " . $jail["path"] . "/dev");
    return strlen($o) > 0;
}

function list_running() {
    global $jail;

    foreach ($jail as $j) {
        if (is_online($j))
            echo $j["name"] . " is online\n";
    }
}

function list_jails() {
    global $jail;

    foreach ($jail as $j) {
        foreach ($j as $key => $value) {
            switch (gettype($value)) {
                case "array":
                    $values = "";
                    foreach ($value as $v)
                        $values .= ((strlen($values) > 0) ? ", " : "") . $v;
                    echo "[" . $j["name"] . "] $key => ( $values )\n";
                    break;
                default:
                    echo "[" . $j["name"] . "] $key => $value\n";
                    break;
            }
        }
    }
}

?>
