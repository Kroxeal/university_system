<?php
ob_start();
session_start();
include 'includes/db.php';
include 'includes/navbar.php';

if (!isset($_SESSION['role'])) {
    header("Location: 403.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'teacher' || $role === 'admin') {
    $stmt = $conn->prepare('
        SELECT m.*, r.location AS room_location, r.number AS room_number 
        FROM meetings m
        JOIN user_meetings um ON m.ID = um.meeting_id
        JOIN rooms r ON m.room_id = r.ID
        WHERE um.user_id = ?
    ');
    $stmt->bind_param('i', $user_id);
} else {
    $stmt = $conn->prepare('
        SELECT m.*, r.location AS room_location, r.number AS room_number,
            IF(um.user_id IS NOT NULL, 1, 0) AS is_participant
        FROM meetings m
        LEFT JOIN user_meetings um ON m.ID = um.meeting_id AND um.user_id = ?
        JOIN rooms r ON m.room_id = r.ID
    ');
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$meetings = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

if (isset($_GET['action'])) {
    $meeting_id = $_GET['meeting_id'];

    if ($_GET['action'] == 'join') {
        $stmt = $conn->prepare('INSERT INTO user_meetings (user_id, meeting_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $user_id, $meeting_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_GET['action'] == 'leave') {
        $stmt = $conn->prepare('DELETE FROM user_meetings WHERE user_id = ? AND meeting_id = ?');
        $stmt->bind_param('ii', $user_id, $meeting_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meetings</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Meeting List</h2>
    <form id="searchForm" class="mb-3">
        <input type="text" id="searchTitle" class="form-control" placeholder="Search by title" oninput="searchMeetings()">
    </form>

    <table class="table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Start Time</th>
            <th>Room</th>
            <th>Creator</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody id="meetingsList">
        <?php foreach ($meetings as $meeting) { ?>
            <tr>
                <td><?= htmlspecialchars($meeting['title']) ?></td>
                <td><?= htmlspecialchars($meeting['description']) ?></td>
                <td><?= htmlspecialchars($meeting['start_time']) ?></td>
                <td><?= htmlspecialchars($meeting['room_location'] . ' - ' . $meeting['room_number']) ?></td>
                <td>
                    <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin') { ?>
                        <span class="text-danger">You (Creator)</span>
                    <?php } else { ?>
                        <span>Participant</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin') { ?>
                        <a href="manage_participants.php?meeting_id=<?= $meeting['ID'] ?>" class="btn btn-info">Check Participants</a>
                    <?php } else { ?>
                        <?php if ($meeting['is_participant'] == 1) { ?>
                            <a href="?action=leave&meeting_id=<?= $meeting['ID'] ?>" class="btn btn-danger">Leave</a>
                        <?php } else { ?>
                            <a href="?action=join&meeting_id=<?= $meeting['ID'] ?>" class="btn btn-success">Join</a>
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script>
    function searchMeetings() {
        const searchTitle = document.getElementById('searchTitle').value.toLowerCase();
        const meetingsRows = document.querySelectorAll('#meetingsList tr');
        meetingsRows.forEach(row => {
            const title = row.querySelector('td').textContent.toLowerCase();
            if (title.includes(searchTitle)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

<!--<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>-->
<!--<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>-->
</body>
</html>
