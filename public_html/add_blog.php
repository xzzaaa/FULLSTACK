<?php

include 'header.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include '../db_connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_SESSION['user_name']; 

    $stmt = $conn->prepare("INSERT INTO blogs (title, content, author) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $author);

    if ($stmt->execute()) {
        echo "Blog post added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
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

<form method="POST">
    <input type="text" name="title" placeholder="Blog Title" required>
    <textarea name="content" placeholder="Blog Content" required></textarea>
    <button type="submit">Add Blog</button>
</form>

</body>