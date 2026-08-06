<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "paulines_kitchen";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}