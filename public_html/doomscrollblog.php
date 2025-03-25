<?php
include '../db_connect.php';

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