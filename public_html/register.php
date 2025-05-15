<?php
session_start();
include("../db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];


    if (empty($username) || empty($email) || empty($password)) {
        echo "<p style='color: red;'>Alla fält måste fyllas i.</p>";
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);


        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<p style='color: red;'>Användarnamnet är redan taget.</p>";
        } else {
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                echo "<p style='color: green;'>Registreringen lyckades! Du kan nu <a href='login.php'>logga in</a>.</p>";
            } else {
                echo "<p style='color: red;'>Fel vid registrering. Försök igen senare.</p>";
            }
        }

        $stmt->close();
    }

    $conn->close();
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
    <div class="form-container">
    <div class="login-container">
        <form id="loginForm" method="POST" action="register.php">
        <input type="text" id="username" name="username" placeholder="Användarnamn" required>
            <input type="email" id="email" name="email" placeholder="E-postadress" required>
            <input type="password" id="password" name="password" placeholder="Lösenord" required>
            <button type="submit">Registrera dig</button>
        </form>



    
    <h3>Har du redan ett konto?:<form action="Login.php">
    <input type="submit" value="Log in" /></form></h3>
    </div>
    </div>
    <p id="response"></p>
</body>