<?php

function start_jail($jail) {
    if ($jail["nettype"] == NetTypes::EPAIR)
        exec("ifconfig " . $jail["inet"] . " create");

    if (array_key_exists("bridge", $jail))
        exec("ifconfig " . $jail["bridge"] . " addm " . $jail["inet"] . "a");

    if ($jail["nettype"] == NetTypes::EPAIR)
        exec("ifconfig " . $jail["inet"] . "a up");

    exec("mount -t devfs devfs " . $jail["path"] . "/dev");
    exec("jail -c vnet name=" . $jail["name"] . " host.hostname=" . $jail["name"] . " path=" . $jail["path"] . " persist");

    if ($jail["nettype"] == NetTypes::EPAIR) {
        exec("ifconfig " . $jail["inet"] . "b vnet " . $jail["name"]);
        exec("jexec " . $jail["name"] . " ifconfig " . $jail["inet"] . "b " . $jail["ip"]);
    }

    exec("jexec " . $jail["name"] . " route add default " . $jail["route"]);

    if (array_key_exists("services", $jail))
        foreach($jail["services"] as $service)
            exec("jexec " . $jail["name"] . " " . $service . " start");
}

?>
