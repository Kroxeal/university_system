<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please enter both username and password.']);
        exit();
    }

    if (strlen($username) > 50) {
        echo json_encode(['error' => 'Username must not exceed 50 characters!']);
        exit();
    }
    if (strlen($password) > 50) {
        echo json_encode(['error' => 'Password must not exceed 50 characters!']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT ID, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Incorrect username or password.']);
        } else {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['role'] = $user['role'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Incorrect username or password.']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'An unexpected error occurred.']);
    } finally {
        $stmt->close();
        $conn->close();
    }
}
?>
