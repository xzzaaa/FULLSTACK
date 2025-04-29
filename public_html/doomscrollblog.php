<?php

include("header.php");
include("../db_connect.php");


// Header and Footer are included within the HTML below

// --- Existing function for fetching comments (remains unchanged) ---
function fetch_comments($conn, $blog_id, $parent_id = NULL, $depth = 0) {
    $query = "SELECT comments.id, comments.comment, users.username, comments.created_at,
                     COALESCE(SUM(CASE WHEN comment_votes.vote = 1 THEN 1 ELSE 0 END), 0) as upvotes,
                     COALESCE(SUM(CASE WHEN comment_votes.vote = -1 THEN 1 ELSE 0 END), 0) as downvotes
              FROM comments
              JOIN users ON comments.user_id = users.id
              LEFT JOIN comment_votes ON comments.id = comment_votes.comment_id
              WHERE comments.blog_id = ? AND comments.parent_comment_id " .
              ($parent_id === NULL ? "IS NULL" : "= ?") .
              " GROUP BY comments.id
              ORDER BY comments.created_at DESC"; // Or ASC if preferred

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
         error_log("Prepare failed (fetch_comments): " . $conn->error); // Log error
         return; // Exit function on prepare error
    }

    if ($parent_id === NULL) {
        $stmt->bind_param("i", $blog_id);
    } else {
        $stmt->bind_param("ii", $blog_id, $parent_id);
    }

    if (!$stmt->execute()) {
        error_log("Execute failed (fetch_comments): " . $stmt->error); // Log error
        $stmt->close();
        return; // Exit function on execute error
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $container_class = ($depth > 0) ? 'nested-comments' : '';
        echo '<div class="'.$container_class.'" id="comment-thread-'.($parent_id ?? 'root').'" '.($depth > 0 ? 'style="margin-left: '.($depth * 20).'px;"' : '').'>';

        while ($comment = $result->fetch_assoc()) {
            $comment_id = $comment['id'];
            echo '<div class="comment-box" id="comment-'.$comment_id.'">';
            echo '<div class="comment-header">';
            echo '<span class="comment-username">'.htmlspecialchars($comment['username']).'</span>';
            echo '<span class="comment-time">'.date("F j, Y, g:i a", strtotime($comment['created_at'])).'</span>';
            echo '</div>';
            echo '<p class="comment-content">'.nl2br(htmlspecialchars($comment['comment'])).'</p>';
            echo '<div class="comment-actions">';
                echo '<div class="vote-section">';
                echo '<button class="vote-btn" data-comment-id="'.$comment_id.'" data-vote-type="1" aria-label="Upvote">▲</button>';
                echo '<span id="upvotes-'.$comment_id.'">'.($comment['upvotes'] ?? 0).'</span>';
                echo '<button class="vote-btn" data-comment-id="'.$comment_id.'" data-vote-type="-1" aria-label="Downvote">▼</button>';
                echo '<span id="downvotes-'.$comment_id.'">'.($comment['downvotes'] ?? 0).'</span>';
                echo '</div>';
                if (isset($_SESSION['user_id'])) {
                   echo '<button class="reply-btn" onclick="setReply('.$comment_id.')">Reply</button>';
                }
            echo '</div>';
            // Using nested-comments class also for the replies container for potential style reuse
            echo '<div class="nested-comments replies" id="replies-'.$comment_id.'" style="display: none;">';
                fetch_comments($conn, $blog_id, $comment_id, $depth + 1); // Recursive call
            echo '</div>';
            echo '</div>'; // end comment-box
        }
        echo '</div>'; // end container
    }
    $stmt->close(); // Close statement when done
}
// --- End of fetch_comments function ---


// --- Determine View (Single Post or List) ---
if (isset($_GET['id'])) {
    // ===========================
    // == SINGLE BLOG POST VIEW ==
    // ===========================
    $blog_id = intval($_GET['id']);
    $blog = null;
    $blog_images = []; // Array to hold image filenames

    // --- Fetch Main Blog Post Data ---
    $queryBlog = "SELECT title, content, author, created_at FROM blogs WHERE id = ?";
    $stmtBlog = $conn->prepare($queryBlog);
    if ($stmtBlog) {
        $stmtBlog->bind_param("i", $blog_id);
        $stmtBlog->execute();
        $resultBlog = $stmtBlog->get_result();
        $blog = $resultBlog->fetch_assoc();
        $stmtBlog->close();
    } else {
         error_log("Prepare failed (fetch blog data): " . $conn->error); // Log error
    }

    // --- Check if blog post exists ---
    if (!$blog) {
        // You might want to redirect to a 404 page or show a more user-friendly message
        http_response_code(404); // Set appropriate HTTP status
        die("Blog post not found.");
    }

    // --- Fetch Associated Images ---
    $queryImages = "SELECT image_filename FROM blog_images WHERE blog_id = ? ORDER BY id ASC"; // Or order by a specific column if added
    $stmtImages = $conn->prepare($queryImages);
    if ($stmtImages) {
        $stmtImages->bind_param("i", $blog_id);
        $stmtImages->execute();
        $resultImages = $stmtImages->get_result();
        while ($imgRow = $resultImages->fetch_assoc()) {
            $blog_images[] = $imgRow['image_filename']; // Add filename to array
        }
        $stmtImages->close();
    } else {
        error_log("Prepare failed (fetch images): " . $conn->error); // Log error
    }

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title><?php echo htmlspecialchars($blog['title']); ?> - Your Blog Name</title>
    <!-- JavaScript functions for comment interaction -->
    <script>
        function setReply(commentId) {
            // Clear previous status and set hidden input
            cancelReply();
            document.getElementById("parent_comment_id").value = commentId;

            const commentForm = document.querySelector(".comment-form");
            const targetComment = document.getElementById("comment-" + commentId);
            if (commentForm && targetComment) {
                 // Create and insert the 'Replying to...' status message
                 const statusP = document.createElement('p');
                 statusP.className = 'reply-status';
                 // Use textContent for security when displaying username
                 const username = targetComment.querySelector('.comment-username') ? targetComment.querySelector('.comment-username').textContent : 'user';
                 statusP.innerHTML = `Replying to <strong>${username}</strong> <button type="button" class="cancel-reply-btn" onclick="cancelReply()">Cancel</button>`;
                 commentForm.insertAdjacentElement('afterbegin', statusP);
            }
            // Focus the textarea
            const textarea = document.querySelector(".comment-textarea");
            if (textarea) textarea.focus();
        }

        function cancelReply() {
            // Reset hidden input
            const parentInput = document.getElementById("parent_comment_id");
            if (parentInput) parentInput.value = 'NULL';

            // Remove status message
            const commentForm = document.querySelector(".comment-form");
            const existingStatus = commentForm ? commentForm.querySelector('.reply-status') : null;
            if (existingStatus) existingStatus.remove();
        }

        // Note: toggleReplies functionality might require adding a specific button per comment
        function toggleReplies(commentId) {
            var repliesDiv = document.getElementById("replies-" + commentId);
            var button = event.target; // Assumes the button itself was clicked

            if (repliesDiv && button) {
                if (repliesDiv.style.display === "none") {
                    repliesDiv.style.display = "block";
                    button.innerHTML = "▼ Hide Replies";
                } else {
                    repliesDiv.style.display = "none";
                    button.innerHTML = "▶ Show Replies";
                }
            }
        }
    </script>
     <!-- Defer loading vote.js -->
     <script src="vote.js" defer></script>
     <!-- Optional: Basic CSS for the image gallery grid -->
     <style>
        .image-gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 15px 0; }
        .blog-post-image { border: 1px solid #eee; padding: 3px; } /* Example border */
     </style>
</head>
<body>


    <div class="main-content">

        <article class="blog-post-full">
            <!-- Blog Post Title -->
            <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
            <!-- Meta Information -->
            <p class="post-meta">
                <strong>By:</strong> <?php echo htmlspecialchars($blog['author']); ?> |
                <small><?php echo date("F j, Y", strtotime($blog['created_at'])); ?></small>
            </p>

            <!-- Display Multiple Images -->
            <?php if (!empty($blog_images)): ?>
                <div class="image-gallery">
                    <?php foreach ($blog_images as $image_filename): ?>
                        <img class="blog-post-image" src="uploads/<?php echo htmlspecialchars($image_filename); ?>" alt="<?php echo htmlspecialchars($blog['title']); // Basic alt text ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Blog Post Content -->
            <div class="post-content-full">
                 <p><?php echo nl2br(htmlspecialchars($blog['content'])); ?></p>
            </div>
        </article>

        <hr>

        <!-- Comments Section -->
        <section class="comments-section">
            <h3>Comments</h3>
            <?php fetch_comments($conn, $blog_id); // Use the existing function ?>
        </section>

        <!-- Comment Form (Logged-in users only) -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <hr>
            <section class="comment-form-section">
                <h3>Add a Comment</h3>
                <form method="POST" action="post_comment.php" class="comment-form">
                    <input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>">
                    <input type="hidden" name="parent_comment_id" id="parent_comment_id" value="NULL">
                    <!-- JS reply status will be inserted here -->
                    <label for="comment-textarea-input" class="sr-only">Comment:</label>
                    <textarea name="comment" id="comment-textarea-input" required placeholder="Write your comment..." class="comment-textarea" rows="5"></textarea><br>
                    <button type="submit" class="comment-btn">Post Comment</button>
                </form>
            </section>
        <?php else: // Prompt for non-logged-in users ?>
             <hr>
            <p class="login-prompt"><a href="login.php">Log in</a> or <a href="register.php">Register</a> to post a comment.</p>
        <?php endif; ?>

    </div> <!-- End main-content -->

    <?php include("footer.php"); // Include footer ?>

</body>
</html>

<?php
} else {
    // ============================
    // == LIST ALL BLOG POSTS VIEW ==
    // ============================
    // Fetch main blog post data (excluding images column now)
    $queryBlogs = "SELECT id, title, author, content, created_at FROM blogs ORDER BY created_at DESC";
    $resultBlogs = $conn->query($queryBlogs); // Use query for simple select

    // Prepare statement for fetching the first image (reused in loop)
    $queryFirstImage = "SELECT image_filename FROM blog_images WHERE blog_id = ? ORDER BY id ASC LIMIT 1";
    $stmtFirstImage = $conn->prepare($queryFirstImage);

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Blog Posts - Your Blog Name</title>
</head>
<body>

    <?php include("header.php"); // Include navigation ?>

    <div class="main-content">

        <h1>Latest Blog Posts</h1>

        <?php if ($resultBlogs && $resultBlogs->num_rows > 0): ?>
            <?php while ($row = $resultBlogs->fetch_assoc()):
                $first_image = null; // Reset first image for each post

                // Fetch the first image for the current post
                if ($stmtFirstImage) {
                    $stmtFirstImage->bind_param("i", $row['id']);
                    $stmtFirstImage->execute();
                    $resultFirstImage = $stmtFirstImage->get_result();
                    if ($imgRow = $resultFirstImage->fetch_assoc()) {
                        $first_image = $imgRow['image_filename'];
                    }
                    // $resultFirstImage->close(); // Optional: close result set explicitly if needed
                } else {
                     error_log("Prepare failed (fetch first image): " . $conn->error); // Log error
                }
            ?>
                <article class="blog-post">
                    <!-- Post Title -->
                    <h2>
                        <a href="doomscrollblog.php?id=<?php echo $row['id']; ?>" class="blog-title">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                    </h2>
                    <!-- Post Meta -->
                    <p class="post-meta">
                        <strong>By:</strong> <?php echo htmlspecialchars($row['author']); ?> |
                        <small><?php echo date("F j, Y", strtotime($row['created_at'])); ?></small>
                    </p>

                    <!-- Display First Image (if exists) -->
                    <?php if (!empty($first_image)): ?>
                        <a href="doomscrollblog.php?id=<?php echo $row['id']; ?>">
                           <img class="blog-post-image" src="uploads/<?php echo htmlspecialchars($first_image); ?>" alt="<?php echo htmlspecialchars($row['title']); // Basic alt text ?>">
                        </a>
                    <?php endif; ?>

                    <!-- Post Excerpt -->
                    <div class="post-excerpt">
                        <p><?php echo nl2br(htmlspecialchars(substr($row['content'], 0, 250))); ?>...</p>
                    </div>

                    <!-- Read More Link -->
                    <a href="doomscrollblog.php?id=<?php echo $row['id']; ?>" class="read-more-link">Read More »</a>

                </article> <!-- End single blog post item -->
            <?php endwhile;

            // Close the prepared statement for first image after the loop
            if ($stmtFirstImage) {
                $stmtFirstImage->close();
            }
            ?>
        <?php else: // No posts found ?>
            <p>No blog posts have been published yet.</p>
        <?php endif; ?>

    </div> <!-- End main-content -->

     <?php include("footer.php"); // Include footer ?>

</body>
</html>
<?php
} // End of main if/else block determining view type

// Close the database connection once at the end
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>