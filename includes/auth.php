<?php
session_start();

function checkUser(){

    if(!isset($_SESSION['user'])){
        header("Location: login.php");
        exit();
    }

}

function checkAdmin(){

    if(!isset($_SESSION['admin'])){
        header("Location: admin_login.php");
        exit();
    }

}
?>