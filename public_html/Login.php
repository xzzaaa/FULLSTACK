<?php
session_start();
include("../db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = $_POST['username_or_email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $stmt->bind_result($user_id, $username, $hashed_password_from_database);
    $stmt->fetch();

    if ($user_id && password_verify($password, $hashed_password_from_database)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $username;
        header("Location: index.php"); 
        exit();
    } else {
        echo "<p style='color: red;'>Felaktigt användarnamn/e-post eller lösenord.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Login</title>
</head>
<body>
    <div class="form-container">
        <div class="login-container">
            <form id="loginForm" method="POST" action="login.php">
                <input type="text" id="username_or_email" name="username_or_email" placeholder="Användarnamn eller E-post" required>
                <input type="password" id="password" name="password" placeholder="Lösenord" required>
                <button type="submit">Logga in</button>
            </form>
            <h3>Registrera dig <a href="register.php">Sign up</a></h3>
        </div>
    </div>
    <p id="response"></p>
</body>
</html>
