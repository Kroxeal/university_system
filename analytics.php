<?php
session_start();
include('includes/db.php');
include('includes/navbar.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$query = "SELECT COUNT(*) AS total_students FROM users WHERE role = 'student'";
$total_students = $conn->query($query)->fetch_assoc()['total_students'];

$query = "SELECT AVG(grade) AS avg_grade FROM student_grades";
$avg_grade = $conn->query($query)->fetch_assoc()['avg_grade'];

$query = "SELECT COUNT(*) AS total_meetings FROM meetings";
$total_meetings = $conn->query($query)->fetch_assoc()['total_meetings'];

$query = "SELECT m.title, COUNT(um.user_id) AS attendees
          FROM meetings m
          JOIN user_meetings um ON m.ID = um.meeting_id
          GROUP BY m.ID
          ORDER BY attendees DESC
          LIMIT 5";
$popular_meetings = $conn->query($query);

$query = "SELECT s.name, AVG(g.grade) AS avg_grade
          FROM users s
          JOIN student_grades g ON s.ID = g.user_id
          WHERE s.role = 'student'
          GROUP BY s.ID
          ORDER BY avg_grade DESC
          LIMIT 10";
$grade_data = $conn->query($query);

$grade_chart_labels = [];
$grade_chart_data = [];
while ($row = $grade_data->fetch_assoc()) {
    $grade_chart_labels[] = $row['name'];
    $grade_chart_data[] = $row['avg_grade'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Total Students</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_students; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Average Grade</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo number_format($avg_grade, 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Total Meetings</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_meetings; ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h4>Top 5 Meetings by Popularity</h4>
            <ul class="list-group">
                <?php while ($row = $popular_meetings->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($row['title']); ?>
                        <span class="badge badge-primary badge-pill"><?php echo $row['attendees']; ?> attendees</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <div class="col-md-6">
            <h4>Top 10 Students by Average Grade</h4>
            <canvas id="gradeChart"></canvas>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('gradeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($grade_chart_labels); ?>,
            datasets: [{
                label: 'Average Grade',
                data: <?php echo json_encode($grade_chart_data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
