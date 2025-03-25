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
        <h2><?php echo htmlspecialchars($row['title']); ?></h2>
        <p><strong>By:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
        <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
        <small>Posted on: <?php echo $row['created_at']; ?></small>
        <hr>
    </div>
<?php endwhile; ?>

</body>
</html>