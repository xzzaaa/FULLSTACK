<?php
session_start();
include("db_connect.php");

if (!isset($_GET['id'])) {
    die("No blog post selected.");
}

$blog_id = intval($_GET['id']);

$query = "SELECT title, content, author, created_at FROM blogs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

$comment_query = "SELECT comments.comment, users.username, comments.created_at 
                  FROM comments 
                  JOIN users ON comments.user_id = users.id 
                  WHERE comments.blog_id = ? 
                  ORDER BY comments.created_at DESC";
$stmt = $conn->prepare($comment_query);
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$comments_result = $stmt->get_result();


$query = "SELECT * FROM blogs ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<?php
include("header.php");
include("../db_connect.php");

function fetch_comments($conn, $blog_id, $parent_id = NULL, $depth = 0) {
    $query = "SELECT comments.id, comments.comment, users.username, comments.created_at
              FROM comments 
              JOIN users ON comments.user_id = users.id 
              WHERE comments.blog_id = ? AND comments.parent_comment_id " . 
              ($parent_id === NULL ? "IS NULL" : "= ?") . 
              " ORDER BY comments.created_at ASC";

    $stmt = $conn->prepare($query);
    if ($parent_id === NULL) {
        $stmt->bind_param("i", $blog_id);
    } else {
        $stmt->bind_param("ii", $blog_id, $parent_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<div class="nested-comments" id="comment-thread-'.$parent_id.'" style="margin-left: '.($depth * 20).'px">';

        while ($comment = $result->fetch_assoc()) {
            $comment_id = $comment['id'];
            echo '<div class="comment-box">';
            echo '<div class="comment-header">';
            echo '<span class="comment-username">'.htmlspecialchars($comment['username']).'</span>';
            echo '<span class="comment-time">'.date("F j, Y, g:i a", strtotime($comment['created_at'])).'</span>';
            
          
            $reply_query = "SELECT COUNT(*) as reply_count FROM comments WHERE parent_comment_id = ?";
            $reply_stmt = $conn->prepare($reply_query);
            $reply_stmt->bind_param("i", $comment_id);
            $reply_stmt->execute();
            $reply_result = $reply_stmt->get_result();
            $reply_count = $reply_result->fetch_assoc()['reply_count'];
            
            if ($reply_count > 0) {
                echo '<button class="toggle-replies-btn" onclick="toggleReplies('.$comment_id.')">▶</button>';
            }

            echo '</div>'; 
            echo '<p class="comment-content">'.nl2br(htmlspecialchars($comment['comment'])).'</p>';
            echo '<button class="reply-btn" onclick="setReply('.$comment_id.')">Reply</button>';

            
            echo '<div class="replies" id="replies-'.$comment_id.'" style="display: none;">';
            fetch_comments($conn, $blog_id, $comment_id, $depth + 1);
            echo '</div>';

            echo '</div>'; 
        }

        echo '</div>'; 
    }
}

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
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="stylesheet.css">
        <title><?php echo htmlspecialchars($blog['title']); ?></title>
        <script>
            function setReply(commentId) {
                document.getElementById("parent_comment_id").value = commentId;
                document.querySelector(".comment-textarea").focus();
            }

            function toggleReplies(commentId) {
                var repliesDiv = document.getElementById("replies-" + commentId);
                var button = document.querySelector('button[onclick="toggleReplies('+commentId+')"]');
                
                if (repliesDiv.style.display === "none") {
                    repliesDiv.style.display = "block";
                    button.innerHTML = "▼"; 
                } else {
                    repliesDiv.style.display = "none";
                    button.innerHTML = "▶"; 
                }
            }
        </script>
    </head>
    <body>

    <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
    <p><strong>By:</strong> <?php echo htmlspecialchars($blog['author']); ?> | <small><?php echo date("F j, Y", strtotime($blog['created_at'])); ?></small></p>
    <p><?php echo nl2br(htmlspecialchars($blog['content'])); ?></p>

    <hr>

    <h3>Comments</h3>
    <div class="comments-section">
        <?php fetch_comments($conn, $blog_id); ?>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <hr>
        <h3>Add a Comment</h3>
        <form method="POST" action="post_comment.php" class="comment-form">
            <input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>">
            <input type="hidden" name="parent_comment_id" id="parent_comment_id" value="NULL">
            <textarea name="comment" required placeholder="Write your comment..." class="comment-textarea"></textarea><br>
            <button type="submit" class="comment-btn">Post Comment</button>
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
    <title>Document</title>
</head>
<body>

<div class="homepagenavbar">
    <a href="index.php" class="button">Home</a>
</div>

    <h1>Latest Blog Posts</h1>

    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="blog-post">
            <h2><a href="doomscrollblog.php?id=<?php echo $row['id']; ?>" class="blog-title">
                <?php echo htmlspecialchars($row['title']); ?></a></h2>
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
