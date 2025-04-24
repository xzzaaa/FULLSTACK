<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

include '../db_connect.php';

include("header.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hämta formulärdata
    $name = htmlspecialchars($_POST['name']);
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

        // Mottagare
        $mail->setFrom($email, "$name");
        $mail->addAddress('telly.lange@elev.ga.lbs.se'); // Ersätt med mottagarens e-post

        // Innehåll
        $mail->Subject = 'Kontaktmeddelande';
        $mail->Body = "Förnamn: $fname\nEfternamn: $lname\nE-post: $email\n\nMeddelande:\n$message";

        // Skicka e-post
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
    <div class="homepagenavbar">
    <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php">Login</a></li>
            <?php else: ?>
                
            <?php endif; ?>
        <a href="doomscrollblog.php" class="button">Past Blogs</a>
        <a href="index.php" class="button">Home</a>
    </div>
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
</body>

</html>

<?php
include("footer.php");
?>