<?php

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
