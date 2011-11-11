<?php
function read_stdin() {
    $fp = fopen("php://stdin", "r");
    $s = fgets($fp);
    return trim($s);
}
?>
