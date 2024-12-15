<?php
ob_start();
session_start();
include 'includes/db.php';
include 'includes/navbar.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: 403.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    ob_end_clean();
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $room_id = trim($_POST['room_id']);
    $start_time_input = $_POST['start_time'];

    $creator_id = $_SESSION['user_id'];

    if (
        empty($title) ||
        empty($description) ||
        empty($room_id) ||
        empty($start_time_input)
    ) {
        echo json_encode(['error' => 'All fields are required!']);
        exit();
    }

    if (strlen($title) < 3 || strlen($title) > 100) {
        echo json_encode(['error' => 'Title must be between 3 and 100 characters!']);
        exit();
    }
    if (strlen($description) < 3 || strlen($description) > 100) {
        echo json_encode(['error' => 'Description must be between 3 and 100 characters!']);
        exit();
    }

    $start_time = DateTime::createFromFormat('Y-m-d\TH:i', $start_time_input);

    if (!$start_time || $start_time->format('Y-m-d\TH:i') !== $start_time_input) {
        echo json_encode(['error' => 'Invalid start time format!']);
        exit();
    }

//    $year = (int)$start_time->format('Y');
//    $currentYear = (int)date('Y');
//    if ($year < 1900 || $year > $currentYear) {
//        echo json_encode(['error' => 'Start time year must be between 1900 and the current year!']);
//        exit();
//    }

    $currentDateTime = new DateTime();
    if ($start_time < $currentDateTime) {
        echo json_encode(['error' => 'Start time cannot be in the past!']);
        exit();
    }

    try {
        $stmt = $conn->prepare('INSERT INTO meetings (title, description, room_id, start_time) VALUES(?, ?, ?, ?)');
        $start_time_formatted = $start_time->format('Y-m-d H:i:s');
        $stmt->bind_param('ssis', $title, $description, $room_id, $start_time_formatted);
        $stmt->execute();
        $meeting_id = $stmt->insert_id;

        $stmt = $conn->prepare('INSERT INTO user_meetings (meeting_id, user_id) VALUES(?, ?)');
        $stmt->bind_param('ii', $meeting_id, $creator_id);
        $stmt->execute();

        echo json_encode(['success' => 'Meeting successfully created!']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error saving meeting: ' . $e->getMessage()]);
        exit();
    } finally {
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
        $conn->close();
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Meeting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Create a New Meeting</h4>
                </div>
                <div class="card-body">
                    <form id="createMeetingFormm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Meeting Title</label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="50" placeholder="Enter meeting title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" maxlength="50" rows="3" placeholder="Enter meeting description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Datetime of start meeting</label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" maxlength="50" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_id" class="form-label">Room</label>
                            <select class="form-control" id="room_id" name="room_id" required>
                                <option value="">Select a room</option>
                            </select>
                        </div>
                        <div id="errorMessages" class="text-danger"></div>
                        <button type="submit" class="btn btn-success w-100">Create Meeting</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        fetch('get_rooms.php')
            .then(response => response.json())
            .then(data => {
                if (data.rooms) {
                    const roomSelect = document.getElementById('room_id');
                    data.rooms.forEach(room => {
                        const option = document.createElement('option');
                        option.value = room.ID;
                        option.textContent = `${room.location} - Room ${room.number}`;
                        roomSelect.appendChild(option);
                    });
                } else if (data.error) {
                    alert(`Error fetching rooms: ${data.error}`);
                }
            })
            .catch(error => {
                console.error('Error fetching rooms:', error);
                alert('An error occurred while fetching rooms.');
            });

        document.getElementById('createMeetingFormm').addEventListener('submit', function (event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch('add_meeting.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    const errorMessage = document.getElementById('errorMessages');
                    if (data.error) {
                        errorMessage.textContent = data.error;
                    } else if (data.success) {
                        alert(data.success);
                        form.reset();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
        });
    });

</script>

</body>
</html>

