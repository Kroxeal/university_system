<?php
ob_start();
include 'includes/db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username_da'];
    $password = $_POST['password_da'];

    if (strlen($username) < 3 || strlen($username) > 20 || !preg_match("/^[a-zA-Z0-9]+$/", $username)) {
        echo json_encode(['success' => false, 'error' => 'Username must be 3-20 characters long and contain only letters and numbers.']);
        exit();
    }

    if (strlen($password) > 50) {
        echo json_encode(['error' => 'Password must not exceed 50 characters!']);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


    $stmt = null;

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Unknown error occurred during registration');
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
        } else {
            echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
        }
    } finally {
        if ($stmt) {
            $stmt->close();
        }
        $conn->close();
    }
}
ob_end_flush();
?>
