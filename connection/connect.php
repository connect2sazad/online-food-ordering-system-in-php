<?php

$config = parse_ini_file(__DIR__ . '/db.config');

if ($config === false) {
    die("Unable to load db.config");
}

$db = mysqli_connect(
    $config['host'],
    $config['username'],
    $config['password'],
    $config['dbname']
);

if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}
?>