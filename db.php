<?php

$conn = mysqli_connect(
    "sql200.infinityfree.com",
    "if0_41878061",
    "XOXN2ee1WvxPc5h",
    "if0_41878061_db_railway"
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>