<?php

session_start();
include('includes/db.php');

if (!isset($_SESSION['role'])) {
    header("Location: 403.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $meeting_id = intval($_POST['meeting_id']);

    if (!$meeting_id) {
        echo json_encode(['error' => 'Invalid meeting ID.']);
        exit;
    }

    $query = "DELETE FROM user_meetings WHERE user_id = ? AND meeting_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $meeting_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to leave the meeting.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}

