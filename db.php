<?php

$host = "trolley.proxy.rlwy.net";
$port = "46098";
$username = "root";
$password = "PIZLlVufDMBYRVyttzOjPSYVsoDjuYfq";
$database = "railway";

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

?>