<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="stylesheet.css">

<nav>
    <div class="homepagenavbar">
        <?php if ($current_page !== 'index.php'): ?>
            <a href="index.php" class="button">Home</a>
        <?php endif; ?>
        
        <?php if ($current_page !== 'doomscrollblog.php'): ?>
            <a href="doomscrollblog.php" class="button">Past blogs</a>
        <?php endif; ?>
        
        <?php if ($current_page !== 'contact.php'): ?>
            <a href="contact.php" class="button">Contact us</a>
        <?php endif; ?>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <?php
                  if ($current_page !== 'login.php') : ?>
                <a href="login.php" class="button">Login</a>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($current_page !== 'add_blog.php'): ?>
                <a href="add_blog.php" class="button">Add blog</a>
            <?php endif; ?>
            
            <a href="logout.php" class="button">Logout</a>
        <?php endif; ?>
    </div>

    <ul>
        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])): ?>
            <li>Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</li>
        <?php endif; ?>
    </ul>
</nav>