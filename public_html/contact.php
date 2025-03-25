<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

include '../db_connect.php';

include("header.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = htmlspecialchars($_POST['fname']);
    $lname = htmlspecialchars($_POST['lname']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->Host = 'MS_xKGp2r@trial-k68zxl2j5r94j905.mlsender.net'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'lbsuppgift@gmail.com'; 
        $mail->Password = 'WebbGrej0+'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port = 587; 

      
        $mail->setFrom($email, "$fname $lname");
        $mail->addAddress('telly.lange@elev.ga.lbs.se'); 

       
        $mail->Subject = 'Kontaktmeddelande';
        $mail->Body = "Förnamn: $fname\nEfternamn: $lname\nE-post: $email\n\nMeddelande:\n$message";

      
        $mail->send();
        echo "<p>Meddelandet har skickats!</p>";
    } catch (Exception $e) {
        echo "<p>Något gick fel. Försök igen senare. Felmeddelande: {$mail->ErrorInfo}</p>";
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
                <label for="fname">First name:</label><br>
                <input type="text" id="fname" name="fname" required><br>
                <label for="lname">Last name:</label><br>
                <input type="text" id="lname" name="lname" required><br>
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
</body>

</html>

<?php
include("footer.php");
?>