<?php
session_start();
include("../db_connect.php");

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $blog_id = $_POST['blog_id'];
    $comment = trim($_POST['comment']);
    $parent_comment_id = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== "NULL" ? intval($_POST['parent_comment_id']) : NULL;

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (blog_id, user_id, comment, parent_comment_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $blog_id, $user_id, $comment, $parent_comment_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: doomscrollblog.php?id=".$blog_id);
exit();
?>
