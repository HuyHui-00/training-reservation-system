<?php
$host = 'db';       
$user = 'tomeiei';         
$password = 'HuyHui098';
$database = 'regis_training';      

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
?>