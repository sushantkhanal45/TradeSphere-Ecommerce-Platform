<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/mail_config.php";

function sendMailMessage($toEmail, $toName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(MAIL_USERNAME, "TradeSphere");

        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();

    } catch (Exception $e) {

        file_put_contents(
            __DIR__ . "/mail_errors.txt",
            date("Y-m-d H:i:s") .
            " | " .
            $mail->ErrorInfo .
            PHP_EOL,
            FILE_APPEND
        );

        return false;
    }
}

function sendOtpEmail($toEmail, $toName, $otp)
{
    $subject = "TradeSphere OTP Verification";

    $body = "
        <h2>TradeSphere OTP Verification</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>Your OTP code is:</p>

        <h1 style='letter-spacing:4px;color:#2563eb;'>
            " . htmlspecialchars($otp) . "
        </h1>

        <p>
            This OTP is valid for a limited time.
            Please do not share it with anyone.
        </p>

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}

function sendResetEmail($toEmail, $toName, $otp)
{
    $subject = "TradeSphere Password Reset OTP";

    $body = "
        <h2>Password Reset Request</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>
            You requested to reset your TradeSphere password.
        </p>

        <p>Your reset OTP is:</p>

        <h1 style='letter-spacing:4px;color:#dc2626;'>
            " . htmlspecialchars($otp) . "
        </h1>

        <p>
            If you did not request this,
            please ignore this email.
        </p>

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}

function sendProductRejectedEmail(
    $toEmail,
    $toName,
    $productName,
    $reason
)
{
    $subject = "TradeSphere Product Listing Rejected";

    $body = "
        <h2>Product Listing Rejected</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>
            Your product listing
            <strong>" . htmlspecialchars($productName) . "</strong>
            was rejected by the administrator.
        </p>

        <p>
            <strong>Reason:</strong><br>
            " . nl2br(htmlspecialchars($reason)) . "
        </p>

        <p>
            Please review the product information,
            correct any issues, and submit it again.
        </p>

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}

function sendProductApprovedEmail(
    $toEmail,
    $toName,
    $productName
)
{
    $subject = "TradeSphere Product Listing Approved";

    $body = "
        <h2>Product Listing Approved</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>
            Your product listing
            <strong>" . htmlspecialchars($productName) . "</strong>
            has been approved by the administrator.
        </p>

        <p>
            Your product is now visible
            in the marketplace.
        </p>

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}

function sendProductRemovedEmail(
    $toEmail,
    $toName,
    $productName,
    $reason = ""
)
{
    $subject = "TradeSphere Product Removed by Admin";

    $reasonText = "";

    if (trim($reason) !== "") {
        $reasonText =
            "<p><strong>Reason:</strong><br>" .
            nl2br(htmlspecialchars($reason)) .
            "</p>";
    }

    $body = "
        <h2>Product Removed</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>
            Your product listing
            <strong>" . htmlspecialchars($productName) . "</strong>
            was removed by the administrator.
        </p>

        $reasonText

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}

function sendSellerApprovedEmail(
    $toEmail,
    $toName
)
{
    $subject = "TradeSphere Seller Verification Approved";

    $body = "
        <h2>Seller Verification Approved</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>
            Your seller verification request
            has been approved by the administrator.
        </p>

        <p>
            You can now list products
            on TradeSphere.
        </p>

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}

function sendSellerRejectedEmail(
    $toEmail,
    $toName,
    $reason = ""
)
{
    $subject = "TradeSphere Seller Verification Rejected";

    $reasonText = "";

    if (trim($reason) !== "") {
        $reasonText =
            "<p><strong>Reason:</strong><br>" .
            nl2br(htmlspecialchars($reason)) .
            "</p>";
    }

    $body = "
        <h2>Seller Verification Rejected</h2>

        <p>Hello " . htmlspecialchars($toName) . ",</p>

        <p>
            Your seller verification request
            was rejected by the administrator.
        </p>

        $reasonText

        <p>
            You may request verification again
            after updating your information.
        </p>

        <p>
            Regards,<br>
            TradeSphere Team
        </p>
    ";

    return sendMailMessage(
        $toEmail,
        $toName,
        $subject,
        $body
    );
}
function sendUserRemovedEmail($toEmail, $toName, $reason = "") {
    $subject = "TradeSphere Account Removed";

    $reasonText = trim($reason) !== ""
        ? "<p><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($reason)) . "</p>"
        : "";

    $body = "
        <h2>Account Removed</h2>
        <p>Hello " . htmlspecialchars($toName) . ",</p>
        <p>Your TradeSphere account has been removed by the administrator.</p>
        $reasonText
        <p>If you believe this was a mistake, please contact the TradeSphere administrator.</p>
        <p>Regards,<br>TradeSphere Team</p>
    ";

    return sendMailMessage($toEmail, $toName, $subject, $body);
}
?>