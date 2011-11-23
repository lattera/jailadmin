<?php

include 'config.php';
include 'db.php';

$db = new fDatabase($dbdriver, $dbdatabase, $dbuser, $dbpassword);
fORMDatabase::attach($db);

?>
