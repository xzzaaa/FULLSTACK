<?php
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include '../db_connect.php';

$errorMessage = null;
$successMessage = null;
$title = '';
$content = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = $_SESSION['user_name'];

    if (empty($title) || empty($content)) {
        $errorMessage = "Title and content are required.";
    } else {
        $conn->begin_transaction();

        try {
            $queryBlog = "INSERT INTO blogs (title, content, author, created_at) VALUES (?, ?, ?, NOW())";
            $stmtBlog = $conn->prepare($queryBlog);
            if (!$stmtBlog) {
                 throw new Exception("Blog prepare failed: " . $conn->error);
            }
            $stmtBlog->bind_param("sss", $title, $content, $author);

            if (!$stmtBlog->execute()) {
                 throw new Exception("Blog execute failed: " . $stmtBlog->error);
            }

            $newBlogId = $conn->insert_id;
            $stmtBlog->close();

            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {

                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                $imageCount = count($_FILES['images']['name']);

                $queryImage = "INSERT INTO blog_images (blog_id, image_filename) VALUES (?, ?)";
                $stmtImage = $conn->prepare($queryImage);
                 if (!$stmtImage) {
                     throw new Exception("Image prepare failed: " . $conn->error);
                 }

                for ($i = 0; $i < $imageCount; $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $imageTmpPath = $_FILES['images']['tmp_name'][$i];
                        $originalName = basename($_FILES['images']['name'][$i]);
                        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $imageName = uniqid('img_', true) . '.' . $fileExtension;
                        $uploadFilePath = $uploadDir . $imageName;

                        $check = getimagesize($imageTmpPath);
                        if ($check !== false && in_array($fileExtension, $allowedExtensions)) {
                            if (move_uploaded_file($imageTmpPath, $uploadFilePath)) {
                                $stmtImage->bind_param("is", $newBlogId, $imageName);
                                if (!$stmtImage->execute()) {
                                     throw new Exception("Image DB insert failed: " . $stmtImage->error);
                                }
                            } else {
                                throw new Exception("Failed to move uploaded file: " . $originalName);
                            }
                        } else {
                             throw new Exception("Invalid file type or not an image: " . $originalName);
                        }
                    } elseif ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception("Error uploading file " . basename($_FILES['images']['name'][$i]) . ": Error Code " . $_FILES['images']['error'][$i]);
                    }
                }

                $stmtImage->close();

            }

            $conn->commit();
            header("Location: index.php?status=post_success");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = "Error posting blog: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Add New Blog Post</title>
    <style>
        .image-preview-container {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .image-preview-item {
            max-width: 100px;
            max-height: 100px;
            border: 1px solid #ccc;
            padding: 2px;
            border-radius: 4px;
        }
        .image-preview-item img {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }
        .form-error { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;}
        .form-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;}
    </style>
</head>
<body>

    <div class="main-content">

        <h1>Add New Blog Post</h1>

        <?php
        if (!empty($errorMessage)) {
            echo '<p class="form-error">' . htmlspecialchars($errorMessage) . '</p>';
        }
        if (isset($_GET['status']) && $_GET['status'] === 'post_success' && empty($errorMessage) ) { // Only show success if no error from current submission attempt
             echo '<p class="form-success">Blog post added successfully! You will be redirected shortly.</p>';
             // Optionally add a meta refresh or JS redirect here if header() didn't work due to output
        }
        ?>

        <form class="add-blog-form" method="POST" action="add_blog.php" enctype="multipart/form-data">

            <div>
                <label for="title">Title:</label><br>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>">
            </div>

            <div>
                <label for="content">Content:</label><br>
                <textarea id="content" name="content" required rows="15"><?php echo htmlspecialchars($content); ?></textarea>
            </div>

            <div>
                <label for="images">Images (Optional, Select Multiple):</label><br>
                <input type="file" id="images" name="images[]" multiple accept="image/jpeg, image/png, image/gif, image/webp">
                <div id="imagePreview" class="image-preview-container"></div>
            </div>

            <button type="submit">Post Blog</button>
        </form>

    </div>

    <script>
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.classList.add('image-preview-item');
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            previewItem.appendChild(img);
                            previewContainer.appendChild(previewItem);
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>

<?php
include 'footer.php';
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>

</body>
</html>