#!/usr/local/bin/php

<?php

include 'inc/misc.php';
include 'init.php';

$jails = Jail::findAll();
foreach ($jails as $jail)
    $jail->Start();

?>
