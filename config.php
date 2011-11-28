<?php

function __autoload($class_name) {
    $flourish_root = 'flourish/classes/';
    $classes_root = 'classes/';

    $file = $flourish_root . $class_name . '.php';

    if (file_exists($file)) {
        include $file;
        return;
    }

    $file = $classes_root . $class_name . '.php';
    if (file_exists($file)) {
        include $file;
        return;
    }
}
?>
