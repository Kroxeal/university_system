<?php
session_start();
include('includes/db.php');
include('includes/navbar.php');

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

$query = "SELECT * FROM user_index_weights WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$index_weights = $stmt->get_result()->fetch_assoc();

$query = "SELECT m.*, um.user_id FROM meetings m
          JOIN user_meetings um ON m.ID = um.meeting_id
          WHERE um.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meetings = $stmt->get_result();

$query = "SELECT sg.grade, sg.graded_by, m.title AS meeting_title, u.username AS graded_by_name 
          FROM student_grades sg
          JOIN meetings m ON sg.meeting_id = m.ID
          JOIN users u ON sg.graded_by = u.ID
          WHERE sg.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$grades = $stmt->get_result();

if (!$index_weights) {
    $query = "INSERT INTO user_index_weights (user_id, author_weight, date_weight, popularity_weight, subject_weight) VALUES (?, 5, 5, 5, 5)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $index_weights = [
        'author_weight' => 0,
        'date_weight' => 0,
        'popularity_weight' => 0,
        'subject_weight' => 0
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author_weight = intval($_POST['author_weight']);
    $date_weight = intval($_POST['date_weight']);
    $popularity_weight = intval($_POST['popularity_weight']);
    $subject_weight = intval($_POST['subject_weight']);

    $query = "UPDATE user_index_weights SET author_weight = ?, date_weight = ?, popularity_weight = ?, subject_weight = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiii", $author_weight, $date_weight, $popularity_weight, $subject_weight, $user_id);
    $stmt->execute();

    $index_weights = [
        'author_weight' => $author_weight,
        'date_weight' => $date_weight,
        'popularity_weight' => $popularity_weight,
        'subject_weight' => $subject_weight
    ];

    echo "<script>alert('Weights updated successfully!');</script>";
}
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

    <h2 class="mt-4">Set Weights for Meeting Rankings:</h2>
    <form method="POST">
        <div class="form-group">
            <label for="author_weight">Author Weight (0-10):</label>
            <input type="number" class="form-control" id="author_weight" name="author_weight" min="0" max="10" value="<?php echo htmlspecialchars($index_weights['author_weight']); ?>" required>
        </div>
        <div class="form-group">
            <label for="date_weight">Date Weight (0-10):</label>
            <input type="number" class="form-control" id="date_weight" name="date_weight" min="0" max="10" value="<?php echo htmlspecialchars($index_weights['date_weight']); ?>" required>
        </div>
        <div class="form-group">
            <label for="popularity_weight">Popularity Weight (0-10):</label>
            <input type="number" class="form-control" id="popularity_weight" name="popularity_weight" min="0" max="10" value="<?php echo htmlspecialchars($index_weights['popularity_weight']); ?>" required>
        </div>
        <div class="form-group">
            <label for="subject_weight">Subject Weight (0-10):</label>
            <input type="number" class="form-control" id="subject_weight" name="subject_weight" min="0" max="10" value="<?php echo htmlspecialchars($index_weights['subject_weight']); ?>" required>
        </div>
        <button type="submit" class="btn btn-success">Save Weights</button>
    </form>

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

    <h2 class="mt-4">Your Grades:</h2>
    <?php if ($grades->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Meeting</th>
                <th>Grade</th>
                <th>Graded By</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($grade = $grades->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grade['meeting_title']); ?></td>
                    <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                    <td><?php echo htmlspecialchars($grade['graded_by_name']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No grades found.</p>
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
