<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../signin/signin.html");
    exit();
}

$user_id = $_SESSION['user_id'];

?>