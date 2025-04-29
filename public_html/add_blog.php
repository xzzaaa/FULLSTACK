<?php
include 'header.php'; // Start session first

// Check login status
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include '../db_connect.php'; // Include DB connection

$errorMessage = null;
$successMessage = null;
$title = ''; // Initialize variables to retain form data on error
$content = '';

// --- PHP Logic for handling POST request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? ''); // Use trim and null coalesce
    $content = trim($_POST['content'] ?? '');
    $author = $_SESSION['user_name']; // Get author from session

    // Basic validation
    if (empty($title) || empty($content)) {
        $errorMessage = "Title and content are required.";
    } else {
        // --- Start Transaction ---
        $conn->begin_transaction();

        try {
            // 1. Insert the main blog post (without image reference)
            $queryBlog = "INSERT INTO blogs (title, content, author, created_at) VALUES (?, ?, ?, NOW())";
            $stmtBlog = $conn->prepare($queryBlog);
            if (!$stmtBlog) {
                 throw new Exception("Blog prepare failed: " . $conn->error);
            }
            $stmtBlog->bind_param("sss", $title, $content, $author);

            if (!$stmtBlog->execute()) {
                 throw new Exception("Blog execute failed: " . $stmtBlog->error);
            }

            $newBlogId = $conn->insert_id; // Get the ID of the newly inserted blog post
            $stmtBlog->close();

            // 2. Handle Multiple Image Uploads
            $imageUploadSuccess = true; // Flag to track if all images processed ok
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) { // Check if files were selected

                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                $imageCount = count($_FILES['images']['name']);

                // Prepare statement for image insertion (reuse inside loop)
                $queryImage = "INSERT INTO blog_images (blog_id, image_filename) VALUES (?, ?)";
                $stmtImage = $conn->prepare($queryImage);
                 if (!$stmtImage) {
                     throw new Exception("Image prepare failed: " . $conn->error);
                 }

                for ($i = 0; $i < $imageCount; $i++) {
                    // Check for individual file upload errors
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $imageTmpPath = $_FILES['images']['tmp_name'][$i];
                        // Sanitize and create unique filename
                        $originalName = basename($_FILES['images']['name'][$i]);
                        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        // Create a more unique name to prevent collisions
                        $imageName = uniqid('img_', true) . '.' . $fileExtension;
                        $uploadFilePath = $uploadDir . $imageName;

                        // Validate file type and check if it's a real image
                        $check = getimagesize($imageTmpPath);
                        if ($check !== false && in_array($fileExtension, $allowedExtensions)) {
                            if (move_uploaded_file($imageTmpPath, $uploadFilePath)) {
                                // File moved successfully, insert into DB
                                $stmtImage->bind_param("is", $newBlogId, $imageName);
                                if (!$stmtImage->execute()) {
                                    // If DB insert fails, stop and set flag
                                     throw new Exception("Image DB insert failed: " . $stmtImage->error);
                                }
                            } else {
                                throw new Exception("Failed to move uploaded file: " . $originalName);
                            }
                        } else {
                             throw new Exception("Invalid file type or not an image: " . $originalName);
                        }
                    } elseif ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        // Handle other upload errors (e.g., file size limit)
                        throw new Exception("Error uploading file " . basename($_FILES['images']['name'][$i]) . ": Error Code " . $_FILES['images']['error'][$i]);
                    }
                } // End of for loop

                $stmtImage->close();

            } // End if images isset

            // 3. If everything went well, commit the transaction
            $conn->commit();
            $successMessage = "Blog post added successfully!";
             // Redirect after successful post to prevent resubmission
             header("Location: index.php?status=post_success");
             exit();

        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            $conn->rollback();
            $errorMessage = "Error posting blog: " . $e->getMessage();
            // Log the detailed error for debugging: error_log($e->getMessage());
        }
    }
}
// --- End of POST Handling Logic ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Add New Blog Post</title>
    <style>
        /* Optional: Style for image preview */
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
        /* Basic error/success message styling */
        .form-error { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;}
        .form-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;}
    </style>
</head>
<body>

    <div class="main-content">

        <h1>Add New Blog Post</h1>

        <?php
        // Display error or success messages
        if (!empty($errorMessage)) {
            echo '<p class="form-error">' . htmlspecialchars($errorMessage) . '</p>';
        }
        if (!empty($successMessage)) { // Display success if redirect fails for some reason
             echo '<p class="form-success">' . htmlspecialchars($successMessage) . '</p>';
        }
        ?>

        <!-- Note the name="images[]" and multiple attribute -->
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
                <div id="imagePreview" class="image-preview-container"></div> <!-- Container for preview -->
            </div>

            <button type="submit">Post Blog</button>
        </form>

    </div> <!-- End main-content -->

    <script>
        // Optional: JavaScript for image preview before upload
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            previewContainer.innerHTML = ''; // Clear previous previews
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

<?php include 'footer.php'; ?>

</body>
</html>