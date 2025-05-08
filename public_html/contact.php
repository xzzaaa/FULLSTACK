<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

include '../db_connect.php';

include("header.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'pro.turbo-smtp.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'lbsuppgift@gmail.com';
        $mail->Password = 'WebbGrej0+';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('lbsuppgift@gmail.com', $name);
        $mail->addReplyTo($email, $name);
        $mail->addAddress('telly.lange@elev.ga.lbs.se');

        $mail->Subject = 'Kontaktmeddelande';
        $mail->Body = "Namn: $name\nE-post: $email\n\nMeddelande:\n$message";

        $mail->send();
        echo "<script>showToast('Your message has been sent!');</script>";
    } catch (Exception $e) {
        echo "<script>showToast('Something went wrong. Try again later. Error message: {$mail->ErrorInfo}');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.scss">
    <title>Document</title>
</head>

<body>

    <div id="contactHeader">
        <h1>Contact Us:</h1>
    </div>
    <div class="contactForm">
        <form method="post" action="">
            <div class="contactContent">
                <label for="name">Full name:</label><br>
                <input type="text" id="name" name="name" required><br>
                <label for="email">Enter your email:</label><br>
                <input type="email" id="email" name="email" required><br>
                <label for="message">Message:</label><br>
                <textarea id="message" name="message" rows="5" required></textarea><br>
                <div id="submitButton">
                    <input type="submit" value="Submit">
                </div>
            </div>
        </form>
    </div>

    <div id="toast"></div>

    <script>
        function showToast(message) {
            const toast = document.getElementById("toast");
            toast.innerText = message;
            toast.style.display = "block";
            toast.style.opacity = 1;

            setTimeout(() => {
                toast.style.opacity = 0;
                setTimeout(() => {
                    toast.style.display = "none";
                }, 500);
            }, 3000);
        }
    </script>

</body>

</html>

<?php
include("footer.php");
?>