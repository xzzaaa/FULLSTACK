<?php session_start(); ?>

<nav>
    <ul>
        <?php if (isset($_SESSION['user_id'])): ?>
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        <?php else: ?>
        <?php endif; ?>
    </ul>
</nav>
