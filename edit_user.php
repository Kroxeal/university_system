<?php
ob_start();
session_start();
include 'includes/db.php';
include 'includes/navbar.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: 403.php");
    exit();
}


if (isset($_GET['ID'])) {
    $user_id = $_GET['ID'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['ID'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE ID = ?");
    $stmt->bind_param("ssi", $username, $role, $user_id);

    if ($stmt->execute()) {
        header("Location: manage_users.php?status=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit User</h2>
    <form action="edit_user.php" method="POST">
        <input type="hidden" name="ID" value="<?php echo $user['ID']; ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" maxlength="50" value="<?php echo $user['username']; ?>" required>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <input type="text" class="form-control" name="role" maxlength="50" value="<?php echo $user['role']; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
    </form>
</div>
</body>
</html>
<?php
ob_end_flush();
?>