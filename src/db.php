<?php
date_default_timezone_set('Asia/Bangkok');

$servername = "db";           
$username   = "tomeiei";      
$password   = "HuyHui098";   
$database   = "regis_training";         

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+07:00'");
