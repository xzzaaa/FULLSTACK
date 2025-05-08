<?php
session_start();
include("../db_connect.php"); 

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
              ORDER BY comments.created_at DESC";

    $stmt = $conn->prepare($query);
    if ($stmt === false) { error_log("Prepare failed (fetch_comments): " . $conn->error); return; }
    if ($parent_id === NULL) { $stmt->bind_param("i", $blog_id); } else { $stmt->bind_param("ii", $blog_id, $parent_id); }
    if (!$stmt->execute()) { error_log("Execute failed (fetch_comments): " . $stmt->error); $stmt->close(); return; }
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $container_class = ($depth > 0) ? 'nested-comments' : '';
        echo '<div class="'.$container_class.'" id="comment-thread-'.($parent_id ?? 'root').'" '.($depth > 0 ? 'style="margin-left: '.($depth * 20).'px;"' : '').'>';
        while ($comment = $result->fetch_assoc()) {
            $comment_id = $comment['id'];
            echo '<div class="comment-box" id="comment-'.$comment_id.'">';
            echo '<div class="comment-header"><span class="comment-username">'.htmlspecialchars($comment['username']).'</span><span class="comment-time">'.date("F j, Y, g:i a", strtotime($comment['created_at'])).'</span></div>';
            echo '<p class="comment-content">'.nl2br(htmlspecialchars($comment['comment'])).'</p>';
            echo '<div class="comment-actions"><div class="vote-section"><button class="vote-btn" data-comment-id="'.$comment_id.'" data-vote-type="1" aria-label="Upvote">▲</button><span id="upvotes-'.$comment_id.'">'.($comment['upvotes'] ?? 0).'</span><button class="vote-btn" data-comment-id="'.$comment_id.'" data-vote-type="-1" aria-label="Downvote">▼</button><span id="downvotes-'.$comment_id.'">'.($comment['downvotes'] ?? 0).'</span></div>';
            if (isset($_SESSION['user_id'])) { echo '<button class="reply-btn" onclick="setReply('.$comment_id.')">Reply</button>'; }
            if ($depth < 5) { echo '<button class="toggle-replies-btn" onclick="toggleReplies('.$comment_id.')">▶ Show Replies</button>'; }
            echo '</div>';
            echo '<div class="nested-comments replies" id="replies-'.$comment_id.'" style="display: none;">';
            fetch_comments($conn, $blog_id, $comment_id, $depth + 1);
            echo '</div></div>';
        }
        echo '</div>';
    }
    $stmt->close();
}

function render_blog_post_item_for_list($conn, $post_data) {
    $first_image = null;
    $queryFirstImage = "SELECT image_filename FROM blog_images WHERE blog_id = ? ORDER BY id ASC LIMIT 1";
    $stmtFirstImage = $conn->prepare($queryFirstImage);
    if ($stmtFirstImage) {
        $stmtFirstImage->bind_param("i", $post_data['id']);
        $stmtFirstImage->execute();
        $resultFirstImage = $stmtFirstImage->get_result();
        if ($imgRow = $resultFirstImage->fetch_assoc()) {
            $first_image = $imgRow['image_filename'];
        }
        $stmtFirstImage->close();
    }
    ob_start();
    ?>
    <article class="blog-post">
        <h2><a href="doomscrollblog.php?id=<?php echo $post_data['id']; ?>" class="blog-title"><?php echo htmlspecialchars($post_data['title']); ?></a></h2>
        <p class="post-meta"><strong>By:</strong> <?php echo htmlspecialchars($post_data['author']); ?> | <small><?php echo date("F j, Y", strtotime($post_data['created_at'])); ?></small></p>
        <?php if (!empty($first_image)): ?>
            <a href="doomscrollblog.php?id=<?php echo $post_data['id']; ?>"><img class="blog-post-image" src="uploads/<?php echo htmlspecialchars($first_image); ?>" alt="<?php echo htmlspecialchars($post_data['title']); ?>"></a>
        <?php endif; ?>
        <div class="post-excerpt"><p><?php echo nl2br(htmlspecialchars(substr($post_data['content'], 0, 250))); ?>...</p></div>
        <a href="doomscrollblog.php?id=<?php echo $post_data['id']; ?>" class="read-more-link">Read More »</a>
    </article>
    <?php
    return ob_get_clean();
}

if (isset($_GET['id'])) {
    $blog_id = intval($_GET['id']);
    $blog = null;
    $blog_images = [];
    $queryBlog = "SELECT title, content, author, created_at FROM blogs WHERE id = ?";
    $stmtBlog = $conn->prepare($queryBlog);
    if ($stmtBlog) { $stmtBlog->bind_param("i", $blog_id); $stmtBlog->execute(); $resultBlog = $stmtBlog->get_result(); $blog = $resultBlog->fetch_assoc(); $stmtBlog->close(); } else { error_log("Prepare failed (fetch blog data): " . $conn->error); }
    if (!$blog) { http_response_code(404); die("Blog post not found."); }
    $queryImages = "SELECT image_filename FROM blog_images WHERE blog_id = ? ORDER BY id ASC";
    $stmtImages = $conn->prepare($queryImages);
    if ($stmtImages) { $stmtImages->bind_param("i", $blog_id); $stmtImages->execute(); $resultImages = $stmtImages->get_result(); while ($imgRow = $resultImages->fetch_assoc()) { $blog_images[] = $imgRow['image_filename']; } $stmtImages->close(); } else { error_log("Prepare failed (fetch images): " . $conn->error); }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($blog['title']); ?> - Your Blog Name</title>
    <script>
        function setReply(commentId) { cancelReply(); document.getElementById("parent_comment_id").value = commentId; const commentForm = document.querySelector(".comment-form"); const targetComment = document.getElementById("comment-" + commentId); if (commentForm && targetComment) { const statusP = document.createElement('p'); statusP.className = 'reply-status'; const username = targetComment.querySelector('.comment-username') ? targetComment.querySelector('.comment-username').textContent : 'user'; statusP.innerHTML = `Replying to <strong>${username}</strong> <button type="button" class="cancel-reply-btn" onclick="cancelReply()">Cancel</button>`; commentForm.insertAdjacentElement('afterbegin', statusP); } const textarea = document.querySelector(".comment-textarea"); if (textarea) textarea.focus(); }
        function cancelReply() { const parentInput = document.getElementById("parent_comment_id"); if (parentInput) parentInput.value = 'NULL'; const commentForm = document.querySelector(".comment-form"); const existingStatus = commentForm ? commentForm.querySelector('.reply-status') : null; if (existingStatus) existingStatus.remove(); }
        function toggleReplies(commentId) { var repliesDiv = document.getElementById("replies-" + commentId); var button = event.target; if (repliesDiv && button) { if (repliesDiv.style.display === "none") { repliesDiv.style.display = "block"; button.innerHTML = "▼ Hide Replies"; } else { repliesDiv.style.display = "none"; button.innerHTML = "▶ Show Replies"; } } }
    </script>
     <script src="vote.js" defer></script>
     <style>.image-gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 15px 0; } .blog-post-image { border: 1px solid #eee; padding: 3px; }</style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="main-content">
        <article class="blog-post-full">
            <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
            <p class="post-meta"><strong>By:</strong> <?php echo htmlspecialchars($blog['author']); ?> | <small><?php echo date("F j, Y", strtotime($blog['created_at'])); ?></small></p>
            <?php if (!empty($blog_images)): ?>
                <div class="image-gallery">
                    <?php foreach ($blog_images as $image_filename): ?>
                        <img class="blog-post-image" src="uploads/<?php echo htmlspecialchars($image_filename); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="post-content-full"><p><?php echo nl2br(htmlspecialchars($blog['content'])); ?></p></div>
        </article>
        <hr>
        <section class="comments-section"><h3>Comments</h3><?php fetch_comments($conn, $blog_id); ?></section>
        <?php if (isset($_SESSION['user_id'])): ?>
            <hr><section class="comment-form-section"><h3>Add a Comment</h3><form method="POST" action="post_comment.php" class="comment-form"><input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>"><input type="hidden" name="parent_comment_id" id="parent_comment_id" value="NULL"><label for="comment-textarea-input" class="sr-only">Comment:</label><textarea name="comment" id="comment-textarea-input" required placeholder="Write your comment..." class="comment-textarea" rows="5"></textarea><br><button type="submit" class="comment-btn">Post Comment</button></form></section>
        <?php else: ?>
             <hr><p class="login-prompt"><a href="login.php">Log in</a> or <a href="register.php">Register</a> to post a comment.</p>
        <?php endif; ?>
    </div>
    <?php include("footer.php"); ?>
</body>
</html>
    <?php
} else {
    $limit = 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $queryBlogs = "SELECT id, title, author, content, created_at FROM blogs ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmtBlogs = $conn->prepare($queryBlogs);
    $posts_html = '';
    $has_more_posts = false;

    if ($stmtBlogs) {  $stmtBlogs->bind_param("ii", $limit, $offset); $stmtBlogs->execute(); $resultBlogs = $stmtBlogs->get_result(); while ($row = $resultBlogs->fetch_assoc()) { $posts_html .= render_blog_post_item_for_list($conn, $row); } $stmtBlogs->close(); $queryCount = "SELECT COUNT(id) as total FROM blogs"; $countResult = $conn->query($queryCount); $totalPosts = 0; if ($countRow = $countResult->fetch_assoc()) { $totalPosts = $countRow['total']; } if (($offset + $limit) < $totalPosts) { $has_more_posts = true; }
    } else { error_log("Prepare failed (list view blogs): " . $conn->error); }

    if (isset($_GET['ajax_load']) && $_GET['ajax_load'] == 'true') {
        header('Content-Type: application/json');
        echo json_encode(['html' => $posts_html, 'has_more' => $has_more_posts]);
        exit();
    }

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Posts - Your Blog Name</title>
    <style>
        #loading-indicator { text-align: center; padding: 20px; display: none; font-style: italic; color: #555; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="main-content">
        <h1>Latest Blog Posts</h1>
        <div id="blog-posts-container">
            <?php echo $posts_html; ?>
        </div>
        <div id="loading-indicator">Loading more posts...</div>
    </div>
    <?php include("footer.php");  ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postsContainer = document.getElementById('blog-posts-container');
            const loadingIndicator = document.getElementById('loading-indicator');
            let currentOffset = <?php echo $limit; ?>;
            const initialLimit = <?php echo $limit; ?>;
            let isLoading = false;
            let hasMore = <?php echo $has_more_posts ? 'true' : 'false'; ?>;

            function loadMorePosts() {
                if (isLoading || !hasMore) return;
                isLoading = true;
                if(loadingIndicator) loadingIndicator.style.display = 'block';
                fetch(`doomscrollblog.php?ajax_load=true&offset=${currentOffset}&limit=${initialLimit}`)
                    .then(response => { if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); } return response.json(); })
                    .then(data => {
                        if (data.html && data.html.trim() !== '') {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data.html;
                            while(tempDiv.firstChild) { postsContainer.appendChild(tempDiv.firstChild); }
                            currentOffset += initialLimit;
                        }
                        hasMore = data.has_more;
                        if (!hasMore && loadingIndicator) { loadingIndicator.textContent = 'No more posts to load.'; }
                    })
                    .catch(error => { console.error('Error loading more posts:', error); if(loadingIndicator) loadingIndicator.textContent = 'Error loading posts. Please try again later.'; })
                    .finally(() => { isLoading = false; if(hasMore && loadingIndicator) { loadingIndicator.style.display = 'none'; } });
            }
            let scrollTimeout;
            window.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const threshold = 200;
                    if ((window.innerHeight + window.scrollY) >= (document.documentElement.offsetHeight - threshold)) { loadMorePosts(); }
                }, 100);
            });
            if (hasMore && (postsContainer.offsetHeight < window.innerHeight)) { loadMorePosts(); }
        });
    </script>
</body>
</html>
    <?php
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>