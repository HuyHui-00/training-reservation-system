<?php
session_start();

if (isset($_SESSION['admin'])) {
    header("Location: a_training_program.php");
    exit;
}

header("Location: f_training_program.php");
exit;
