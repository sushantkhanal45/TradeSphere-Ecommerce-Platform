<?php
include "includes/mail_helper.php";

$result = sendOtpEmail("skhanal0045@gmail.com", "Test User", "123456");

if ($result) {
    echo "Test email sent successfully.";
}
?>