<?php
session_start();
require_once '../db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


header('Content-Type: application/json');

if (!isset($_SESSION['user_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Du måste vara inloggad för att rösta.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['comment_id']) || !isset($_POST['vote'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incomplete data.']);
        exit;
    }
    $comment_id = intval($_POST['comment_id']);
    $vote = intval($_POST['vote']);
    
    if ($comment_id <= 0 || ($vote !== 1 && $vote !== -1)) {
        echo json_encode(['status' => 'error', 'message' => 'Ogiltiga data skickades.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['user_name']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $user_id = $user['id'];

    $stmt = $conn->prepare("SELECT id FROM comment_votes WHERE comment_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $existing_vote = $stmt->get_result()->fetch_assoc();

    if ($existing_vote) {
        $stmt = $conn->prepare("UPDATE comment_votes SET vote = ? WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $vote, $comment_id, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO comment_votes (comment_id, user_id, vote) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $comment_id, $user_id, $vote);
    }
    $stmt->execute();

    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END) AS upvotes, 
        SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END) AS downvotes 
        FROM comment_votes 
        WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $votes = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'status' => 'success',
        'upvotes' => $votes['upvotes'] ?? 0,
        'downvotes' => $votes['downvotes'] ?? 0
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Felaktig begäran.']);
}
?>
