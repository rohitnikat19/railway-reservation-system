<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "railway_reservation";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// echo "Connected Successfully";

?>