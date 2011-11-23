<?php
function read_stdin() {
    $fp = fopen("php://stdin", "r");
    $s = fgets($fp);
    return trim($s);
}

function read_command() {
    $cmd = read_stdin();

    $parsed = strstr($cmd, "#", true);
    if ($parsed !== false)
        return $parsed;

    return $cmd;
}
?>
