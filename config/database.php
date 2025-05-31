<?php
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'eczhvuq1_dek');
define('DB_USER', 'admin');
define('DB_PASS', 'admin');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?> 