<?php
include("header.php"); 
include("../db_connect.php");

$query = "SELECT id, title, content, author, created_at FROM blogs ORDER BY created_at DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latest_post = null;
if ($result) {
    $latest_post = mysqli_fetch_assoc($result);
} else {
    error_log("Failed to fetch latest post: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Welcome to Your Blog</title>
</head>
<body>

    <div class="main-content">
        <div class="post-container">
            <h2 class="post-title">Current Latest Post:</h2>

            <?php if ($latest_post): ?>
                <div class="blog-post">
                    <h3 class="post-heading"><?php echo htmlspecialchars($latest_post['title']); ?></h3>
                    <p class="post-meta">Posted by <?php echo htmlspecialchars($latest_post['author']); ?> on <?php echo date("F j, Y", strtotime($latest_post['created_at'])); ?></p>
                    <p class="post-content">
                        <?php echo nl2br(htmlspecialchars(substr($latest_post['content'], 0, 150))); ?>...
                    </p>

                    <div class="post-actions">
                        <a href="doomscrollblog.php?id=<?php echo $latest_post['id']; ?>" class="read-more">Read More</a>
                        <span>about the latest post, or check out</span>
                        <a href="doomscrollblog.php" class="action-link">Past Blogs</a>
                    </div>

                </div>
            <?php else: ?>
                <p>No blog posts available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php
    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_close($conn);
    }
    include("footer.php");
    ?>
</body>
</html>