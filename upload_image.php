<?php
session_start();
include('includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['image'])) {
        $base64_image = $data['image'];

        $image_data = base64_decode(preg_replace(
            '#^data:image/\w+;base64,#i',
            '', $base64_image)
        );

        if ($image_data === false) {
            echo json_encode(['error' => 'Invalid image data']);
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $query = "UPDATE users SET image = ? WHERE ID = ?";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            error_log("Error preparing query: " . $conn->error);
            echo json_encode(['error' => 'Error preparing query']);
            exit;
        }

        $stmt->bind_param("si", $image_data, $user_id);

        // Выполняем запрос
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Image successfully uploaded']);
        } else {
            error_log("Error uploading image: " . $stmt->error);
            echo json_encode(['error' => 'Error uploading image: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => "No image data provided"]);
    }
} else {
    echo json_encode(['error' => "Invalid request method"]);
}
?>
