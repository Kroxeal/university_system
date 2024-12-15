<?php
session_start();
include('includes/db.php');
include ('includes/navbar.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM users WHERE ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$query = "SELECT m.*, um.user_id FROM meetings m
          JOIN user_meetings um ON m.ID = um.meeting_id
          WHERE um.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meetings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Cabinet</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        </div>
        <div class="card-body">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p>Your role: <strong><?php echo htmlspecialchars($user['role']); ?></strong></p>
        </div>
    </div>

    <h2 class="mt-4">Your Meetings:</h2>
    <?php if ($meetings->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Title</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($meeting = $meetings->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($meeting['title']); ?></td>
                    <td>
                        <button class="btn btn-danger leave-meeting" data-meeting-id="<?php echo $meeting['ID']; ?>">
                            Leave
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No meetings found.</p>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $(document).on('click', '.leave-meeting', function () {
        const meetingId = $(this).data('meeting-id');

        if (confirm('Are you sure you want to leave this meeting?')) {
            $.ajax({
                url: 'leave_meeting.php',
                type: 'POST',
                data: { meeting_id: meetingId },
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        alert('Successfully left the meeting!');
                        location.reload();
                    } else {
                        alert(data.error || 'An error occurred. Please try again.');
                    }
                },
                error: function () {
                    alert('Failed to connect to the server.');
                }
            });
        }
    });
</script>
</body>
</html>
