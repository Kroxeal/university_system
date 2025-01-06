<?php

include('includes/db.php');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You need to log in first']);
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "UPDATE users SET image = NULL WHERE ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Image deleted successfully']);
} else {
    echo json_encode(['error' => 'Error deleting image']);
}

