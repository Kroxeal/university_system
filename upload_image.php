<?php
session_start();
include('includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $image = $_FILES['image']['tmp_name'];
    $image_data = file_get_contents($image);
    $user_id = $_SESSION['user_id'];

    error_log("Type of image: " . gettype($image_data));
    error_log("Size of image: " . strlen($image_data) . " байт");
    $query = "UPDATE users SET image = ? WHERE ID = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        error_log("Error of query: " . $conn->error);
        echo json_encode(['error' => 'Error of query']);
        exit;
    }

    $stmt->bind_param("si", $image_data, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Image successfully uploaded']);
    } else {
        error_log("Error uploading image: " . $stmt->error);
        echo json_encode(['error' => 'Error uploading image: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => "There's no image to upload"]);
}
?>
