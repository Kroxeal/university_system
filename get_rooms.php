<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    header('Content-Type: application/json');
    try {
        $rooms = [];
        $result = $conn->query("SELECT ID, location, number FROM rooms ORDER BY location ASC");
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        echo json_encode(['rooms' => $rooms]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
