<?php
include("header.php");
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
        <a href="Login.php" class="button">Login</a>
        <a href="doomscrollblog.php" class="button">Past Blogs</a>
        <a href="index.php" class="button">Home</a>
    </div>
    <div id="contactHeader">
        <h1>Contact Us:</h1>
    </div>
    <div class="contactForm">
        <form method="post" action="mailto:telly.lange@elev.ga.lbs.se">
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