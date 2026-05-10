<?php

$host = getenv("trolley.proxy.rlwy.net");
$port = getenv("46098");
$username = getenv("root");
$password = getenv("PIZLlVufDMBYRVyttzOjPSYVsoDjuYfq");
$database = getenv("railway");

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

?>