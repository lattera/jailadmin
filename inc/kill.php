<?php

function kill_jail($jail) {
    exec("jail -r " . $jail["name"]);
    exec("umount " . $jail["path"] . "/dev");
    if ($jail["nettype"] == NetTypes::EPAIR)
        exec("ifconfig " . $jail["inet"] . "a destroy");
}

?>
