<?php

session_start();
include("db_connect.php");

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "fullstack";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Anslutningsfel: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];


    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();


        if (md5($pass) == $row['password']) {
            $_SESSION['username'] = $user;
            header("Location: dashboard.php"); 
            exit();
        } else {
            echo "Felaktigt lösenord.";
        }
    } else {
        echo "Användaren finns inte.";
    }

    $stmt->close();
}
$conn->close();
?>
