<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'You do not have permission to delete users.']);
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $current_user_id = intval($_SESSION['user_id']);

    if ($user_id === $current_user_id) {
        echo json_encode(['error' => 'You cannot delete yourself!']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM `users` WHERE `ID` = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'User deleted successfully!']);
    } else {
        echo json_encode(['error' => 'Error deleting the user. Please try again.']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid user ID.']);
}

$conn->close();
?>
