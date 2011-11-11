<?php

function is_online($jail) {
    $o = exec("mount | grep " . $jail["path"] . "/dev");
    return strlen($o) > 0;
}

?>
