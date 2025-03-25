<?php
include("header.php");
include("../db_connect.php");

$query = "SELECT title, content, author, created_at FROM blogs ORDER BY created_at DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latest_post = mysqli_fetch_assoc($result);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Document</title>
</head>
<body>


    <div class="post-container">
    <h2 class="post-title">Current Post:</h2>

    <?php if ($latest_post): ?>
        <div class="blog-post">
            <h3 class="post-heading"><?php echo htmlspecialchars($latest_post['title']); ?></h3>
            <p class="post-meta">Posted by <?php echo htmlspecialchars($latest_post['author']); ?> on <?php echo date("F j, Y", strtotime($latest_post['created_at'])); ?></p>
            <p class="post-content">
                <?php echo nl2br(htmlspecialchars(substr($latest_post['content'], 0, 150))); ?>...
            </p>
            <a href="doomscrollblog.php" class="read-more">Read More</a>
        </div>
    <?php else: ?>
        <p>No blog posts available.</p>
    <?php endif; ?>
</div>

</body>
</html>