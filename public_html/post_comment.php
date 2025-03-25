<?php
session_start();
include("../db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $blog_id = intval($_POST['blog_id']);
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment']);

    if (!empty($comment)) {
        $query = "INSERT INTO comments (blog_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $blog_id, $user_id, $comment);
        $stmt->execute();
    }
}

// Redirect back to the blog post
header("Location: doomscrollblog.php?id=" . $blog_id);
exit();
?>
