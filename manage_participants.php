<?php
ob_start();
session_start();
include 'includes/db.php';
include 'includes/navbar.php';

if ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$meeting_id = $_GET['meeting_id'];

$stmt_meeting = $conn->prepare('SELECT * FROM meetings WHERE ID = ?');
$stmt_meeting->bind_param('i', $meeting_id);
$stmt_meeting->execute();
$meeting_result = $stmt_meeting->get_result();
$meeting = $meeting_result->fetch_assoc();
$stmt_meeting->close();

$stmt_participants = $conn->prepare('
    SELECT u.ID, u.username 
    FROM users u
    JOIN user_meetings um ON u.ID = um.user_id
    WHERE um.meeting_id = ?
');
$stmt_participants->bind_param('i', $meeting_id);
$stmt_participants->execute();
$result_participants = $stmt_participants->get_result();
$participants = $result_participants->fetch_all(MYSQLI_ASSOC);
$stmt_participants->close();

if (isset($_POST['submit_grades'])) {
    $grades = $_POST['grades'];
    $current_user_id = $_SESSION['user_id'];

    foreach ($grades as $user_id => $grade) {
        if (!empty($grade)) {
            $stmt_check = $conn->prepare('SELECT ID FROM student_grades WHERE user_id = ? AND meeting_id = ?');
            $stmt_check->bind_param('ii', $user_id, $meeting_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $stmt_update = $conn->prepare('
                    UPDATE student_grades SET grade = ?, graded_by = ?
                    WHERE user_id = ? AND meeting_id = ?
                ');
                $stmt_update->bind_param('diii', $grade, $current_user_id, $user_id, $meeting_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                $stmt_insert = $conn->prepare('
                    INSERT INTO student_grades (user_id, meeting_id, grade, graded_by)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt_insert->bind_param('iidi', $user_id, $meeting_id, $grade, $current_user_id);
                $stmt_insert->execute();
                $stmt_insert->close();
            }

            $stmt_check->close();
        }
    }

    header('Location: manage_participants.php?meeting_id=' . $meeting_id);
    exit;
}

if (isset($_POST['delete_participant'])) {
    $participant_id = $_POST['participant_id'];

    if ($participant_id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete yourself as you are the creator of the meeting.');</script>";
    } else {
        $stmt_delete = $conn->prepare('DELETE FROM user_meetings WHERE user_id = ? AND meeting_id = ?');
        $stmt_delete->bind_param('ii', $participant_id, $meeting_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        header('Location: manage_participants.php?meeting_id=' . $meeting_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Participants - <?= htmlspecialchars($meeting['title']) ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Manage Participants for "<?= htmlspecialchars($meeting['title']) ?>"</h2>

    <h4>Participants:</h4>
    <form method="POST">
        <ul class="list-group">
            <?php foreach ($participants as $participant) { ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($participant['username']) ?>
                    <div>
                        <input type="number" step="0.01" name="grades[<?= $participant['ID'] ?>]"
                               placeholder="Enter grade" class="form-control form-control-sm d-inline-block" style="width: 120px;">
                        <button type="submit" name="delete_participant" class="btn btn-danger btn-sm ml-2"
                                value="<?= $participant['ID'] ?>">Delete</button>
                        <input type="hidden" name="participant_id" value="<?= $participant['ID'] ?>">
                    </div>
                </li>
            <?php } ?>
        </ul>

        <button type="submit" name="submit_grades" class="btn btn-primary mt-3">Submit Grades</button>
    </form>

    <a href="get_all_meetings.php" class="btn btn-secondary mt-3">Back to Meetings</a>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Participants - <?= htmlspecialchars($meeting['title']) ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Manage Participants for "<?= htmlspecialchars($meeting['title']) ?>"</h2>

    <h4>Participants:</h4>
    <ul class="list-group">
        <?php foreach ($participants as $participant) { ?>
            <li class="list-group-item">
                <?= htmlspecialchars($participant['username']) ?>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="delete_participant" class="btn btn-danger btn-sm"
                            value="<?= $participant['ID'] ?>">Delete</button>
                    <input type="hidden" name="participant_id" value="<?= $participant['ID'] ?>">
                </form>
            </li>
        <?php } ?>
    </ul>

    <a href="get_all_meetings.php" class="btn btn-secondary mt-3">Back to Meetings</a>
</div>

<!--<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>-->
<!--<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>-->
</body>
</html>
