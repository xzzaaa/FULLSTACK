<?php
include("header.php");
?>

<?php session_start(); ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Document</title>
</head>
<body>
    <div class="homepagenavbar">
    <a href="index.php" class="button">Home</a>
    <a href="doomscrollblog.php" class="button">Past blogs</a>
    <a href="contact.php" class="button">Contact us</a>
    <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php">Login</a></li>
            <?php else: ?>
               <a href="logout.php">Logout</a></li>
            <?php endif; ?>
    </div>

    <div class="post-container">
        <h2 class="post-title">Current Post:</h2>
        <div class="blog-post">
            <h3 class="post-heading">My First Blog Post</h3>
            <p class="post-meta">Posted on February 27, 2025</p>
            <p class="post-content">
                This is a short preview of the blog post. You can add more details here and format it as needed...
            </p>
            <a href="#" class="read-more">Read More</a>
        </div>
    </div>


</body>
</html>