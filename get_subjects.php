<?php
include 'includes/db.php';

$stmt = $conn->prepare('SELECT ID, title FROM subjects');
$stmt->execute();
$result = $stmt->get_result();
$subjects = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['subjects' => $subjects]);
?>
