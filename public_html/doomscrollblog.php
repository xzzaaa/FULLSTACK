<?php
include("header.php");
include("../db_connect.php");


if (isset($_GET['id'])) {
    $blog_id = intval($_GET['id']);

    $query = "SELECT title, content, author, created_at FROM blogs WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $blog_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $blog = $result->fetch_assoc();

    if (!$blog) {
        die("Blog post not found.");
    }


    $comment_query = "SELECT comments.comment, users.username, comments.created_at 
                      FROM comments 
                      JOIN users ON comments.user_id = users.id 
                      WHERE comments.blog_id = ? 
                      ORDER BY comments.created_at DESC";
    $stmt = $conn->prepare($comment_query);
    $stmt->bind_param("i", $blog_id);
    $stmt->execute();
    $comments_result = $stmt->get_result();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="stylesheet.css">
        <title><?php echo htmlspecialchars($blog['title']); ?></title>
    </head>
    <body>

    <div class="homepagenavbar">
        <a href="doomscrollblog.php" class="button">Back to Blogs</a>
    </div>

    <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
    <p><strong>By:</strong> <?php echo htmlspecialchars($blog['author']); ?> | <small><?php echo date("F j, Y", strtotime($blog['created_at'])); ?></small></p>
    <p><?php echo nl2br(htmlspecialchars($blog['content'])); ?></p>

    <hr>

    <h3>Comments</h3>
    <?php if ($comments_result->num_rows > 0): ?>
        <?php while ($comment = $comments_result->fetch_assoc()): ?>
            <div class="comment-box">
                <p><strong><?php echo htmlspecialchars($comment['username']); ?></strong> - <?php echo date("F j, Y, g:i a", strtotime($comment['created_at'])); ?></p>
                <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No comments yet. Be the first to comment!</p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <hr>
        <h3>Add a Comment</h3>
        <form method="POST" action="post_comment.php">
            <input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>">
            <textarea name="comment" required placeholder="Write your comment..."></textarea><br>
            <button type="submit">Post Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> to post a comment.</p>
    <?php endif; ?>

    </body>
    </html>

    <?php
} else {
    
    $query = "SELECT id, title, author, content, created_at FROM blogs ORDER BY created_at DESC";
    $result = $conn->query($query);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="stylesheet.css">
        <title>Blog List</title>
    </head>
    <body>

    <div class="homepagenavbar">
        <a href="index.php" class="button">Home</a>
    </div>

    <h1>Latest Blog Posts</h1>

    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="blog-post">
            <h2><a href="doomscrollblog.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h2>
            <p><strong>By:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
            <p><?php echo nl2br(htmlspecialchars(substr($row['content'], 0, 200))); ?>...</p>
            <small>Posted on: <?php echo $row['created_at']; ?></small>
            <hr>
        </div>
    <?php endwhile; ?>

    </body>
    </html>

    <?php
}
?>
