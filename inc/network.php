<?php

function bridge_exists($bridgename) {
    global $bridge;

    if (array_key_exists($bridgename, $bridge) == false)
        return false;

    $o = exec("ifconfig " . $bridge[$bridgename]["inet"] . " | grep -v \"does not exist\"");
    return strlen($o) > 0;
}

function create_bridge($bridgename) {
    global $bridge;

    if (bridge_exists($bridgename))
        return true;

    exec("ifconfig " . $bridge[$bridgename]["inet"] . " create");
    exec("ifconfig " . $bridge[$bridgename]["inet"] . " " . $bridge[$bridgename]["ip"]);

    return true;
}

function prep_bridge($bridgename) {
    global $config;

    if (bridge_exists($bridgename) == false) {
        if ($config["on_start"]["createbridge"] == false) {
            echo "WARNING: configured bridge " . $bridgename . " does not exist. Please create the bridge.\n";
            return false;
        }

        create_bridge($bridgename);
    }

    return true;
}

?>
