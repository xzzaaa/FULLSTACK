<?php
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author'];

    $stmt = $conn->prepare("INSERT INTO blogs (title, content, author) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $author);

    if ($stmt->execute()) {
        echo "Blog post added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<form method="POST">
    <input type="text" name="title" placeholder="Blog Title" required>
    <textarea name="content" placeholder="Blog Content" required></textarea>
    <input type="text" name="author" placeholder="Author Name" required>
    <button type="submit">Add Blog</button>
</form>
