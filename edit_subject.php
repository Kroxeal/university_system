<?php
ob_start();
session_start();
include('includes/db.php');
include 'includes/navbar.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: 403.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['id'];
    $title = $_POST['title'];

    $stmt = $conn->prepare('UPDATE subjects SET title = ? WHERE ID = ?');
    $stmt->bind_param('si', $title, $subject_id);
    $stmt->execute();
    $stmt->close();

    header('Location: get_all_subjects.php');
    exit;
}

$subject_id = $_GET['id'];
$stmt = $conn->prepare('SELECT * FROM subjects WHERE ID = ?');
$stmt->bind_param('i', $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">Edit Subject</h2>
    <form action="edit_subject.php" method="POST">
        <input type="hidden" name="id" value="<?= $subject['ID'] ?>">
        <div class="form-group">
            <label for="title">Subject Title:</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($subject['title']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Subject</button>
    </form>
</div>

<!--<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>-->
<!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>-->

</body>
</html>
