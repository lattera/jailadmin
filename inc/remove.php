<?php

function delete_jail($name) {
    global $jail;

    if (is_online($jail[$name]))
        kill_jail($jail[$name]);

    exec("cp config.php config.php.tmp");

    $fp = fopen("config.php.tmp", "r");
    if ($fp === false)
        return false;

    $lines = array();
    while (!feof($fp)) {
        $s = rtrim(fgets($fp));
        if (strstr($s, "jail[\"" . $name) === false)
            array_push($lines, $s);
    }

    fclose($fp);
    $fp = fopen("config.php", "w");
    if ($fp === false)
        return false;

    $prevblank = false;
    foreach ($lines as $line) {
        if ($prevblank == true && strlen($line) == 0)
            continue;

        fwrite($fp, $line . "\n");

        if (strlen($line) == 0)
            $prevblank = true;
    }

    exec("rm config.php.tmp");

    return true;
}

?>
