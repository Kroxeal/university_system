<?php
session_start();
include 'includes/db.php';
include 'includes/navbar.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "You do not have permission to view this page.";
    exit();
}

$query = "SELECT * FROM users";
$result = $conn->query($query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1 class="text-center mb-4">User Management</h1>

    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by username">
    </div>

    <table id="userTable" class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $user['ID']; ?></td>
                <td><?= $user['username']; ?></td>
                <td><?= $user['role']; ?></td>
                <td>
                    <a href="view_user.php?ID=<?= $user['ID']; ?>" class="btn btn-info btn-sm">View</a>
                    <button class="deleteUserBtn btn btn-danger btn-sm" data-id="<?= $user['ID']; ?>">Delete</button>
                    <a href="edit_user.php?ID=<?= $user['ID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <a href="add_user.php" class="btn btn-success">Add New User</a>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>-->
<!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>-->

<script>
    $(document).ready(function() {
        $('.deleteUserBtn').on('click', function(e) {
            e.preventDefault();

            var userId = $(this).data('id');

            if (confirm('Are you sure you want to delete this user?')) {
                $.ajax({
                    type: 'GET',
                    url: 'delete_user.php',
                    data: { delete: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.success);
                            location.reload();
                        } else {
                            alert(response.error || 'An error occurred.');
                        }
                    },
                    error: function() {
                        alert('Failed to connect to the server.');
                    }
                });
            }
        });

        $('#searchInput').on('keyup', function() {
            var searchValue = $(this).val().toLowerCase();
            $('#userTable tbody tr').each(function() {
                var username = $(this).find('td').eq(1).text().toLowerCase();
                if (username.indexOf(searchValue) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>
</body>
</html>
