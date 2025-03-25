<?php session_start(); ?>

<?php 
    $current_page = basename($_SERVER['PHP_SELF']); 
?>

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
            <a href="login.php">Login</a>
        <?php else: ?>
            <?php if ($current_page !== 'add_blog.php'): ?>
                <a href="add_blog.php">Add blog</a>
            <?php endif; ?>
            
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>

    <ul>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</li>
        <?php endif; ?>
    </ul>
</nav>
