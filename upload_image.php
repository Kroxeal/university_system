<?php
session_start();
include('includes/db.php');

const MAX_IMAGE_SIZE = 2 * 1024 * 1024; // 2MB
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

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

        if (strlen($image_data) > MAX_IMAGE_SIZE) {
            echo json_encode(['error' => 'Image exceeds the maximum size of 2MB']);
            exit;
        }

        $image_info = getimagesizefromstring($image_data);
        if ($image_info === false) {
            echo json_encode(['error' => 'The file is not a valid image']);
            exit;
        }

        $mime_type = $image_info['mime'];
        if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
            echo json_encode(['error' => 'Unsupported image type. Allowed types: JPEG, PNG, JPG']);
            exit;
        }

        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['error' => 'User is not authenticated']);
            exit;
        }

        $query = "UPDATE users SET image = ? WHERE ID = ?";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            error_log("Error preparing query: " . $conn->error);
            echo json_encode(['error' => 'Error preparing query']);
            exit;
        }

        $stmt->bind_param("si", $image_data, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Image successfully uploaded']);
        } else {
            error_log("Error uploading image: " . $stmt->error);
            echo json_encode(['error' => 'Error uploading image']);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => "No image data provided"]);
    }
} else {
    echo json_encode(['error' => "Invalid request method"]);
}
?>
