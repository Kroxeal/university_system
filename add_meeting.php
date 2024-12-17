<?php
ob_start();
session_start();
include 'includes/db.php';
include 'includes/navbar.php';

// Проверяем роль пользователя
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: 403.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    ob_end_clean();

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $room_id = trim($_POST['room_id']);
    $subject_ids = $_POST['subject_ids'] ?? [];
    $start_time_input = $_POST['start_time'];

    $creator_id = $_SESSION['user_id'];  // ID текущего пользователя, создающего встречу

    // Проверка на пустые поля
    if (empty($title) || empty($description) || empty($room_id) || empty($subject_ids) || empty($start_time_input)) {
        echo json_encode(['error' => 'All fields are required!']);
        exit();
    }

    // Проверка длины поля title и description
    if (strlen($title) < 3 || strlen($title) > 100) {
        echo json_encode(['error' => 'Title must be between 3 and 100 characters!']);
        exit();
    }
    if (strlen($description) < 3 || strlen($description) > 100) {
        echo json_encode(['error' => 'Description must be between 3 and 100 characters!']);
        exit();
    }

    // Проверка правильности формата start_time
    $start_time = DateTime::createFromFormat('Y-m-d\TH:i', $start_time_input);

    if (!$start_time || $start_time->format('Y-m-d\TH:i') !== $start_time_input) {
        echo json_encode(['error' => 'Invalid start time format!']);
        exit();
    }

    // Проверка, чтобы время старта было в будущем
    $currentDateTime = new DateTime();
    if ($start_time < $currentDateTime) {
        echo json_encode(['error' => 'Start time cannot be in the past!']);
        exit();
    }

    try {
        // Вставка новой встречи в таблицу meetings с учётом поля creator_id
        $stmt = $conn->prepare('INSERT INTO meetings (title, description, room_id, start_time, creator_id) VALUES(?, ?, ?, ?, ?)');

        $start_time_formatted = $start_time->format('Y-m-d H:i:s');
        $stmt->bind_param('ssisi', $title, $description, $room_id, $start_time_formatted, $creator_id);  // Добавляем creator_id
        $stmt->execute();
        $meeting_id = $stmt->insert_id;

        // Вставка связи встречи с предметами
        $stmt = $conn->prepare('INSERT INTO meeting_subjects (meeting_id, subject_id) VALUES (?, ?)');
        foreach ($subject_ids as $subject_id) {
            $stmt->bind_param('ii', $meeting_id, $subject_id);
            $stmt->execute();
        }

        // Вставка пользователя, создавшего встречу, в таблицу user_meetings
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
                    <form id="createMeetingFormm" method="POST">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" maxlength="100" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" maxlength="100" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="room_id">Room</label>
                            <select id="room_id" name="room_id" class="form-control" required>
                                <option value="">Select a room</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="subject_ids">Select Subjects</label>
                            <select id="subject_ids" name="subject_ids[]" class="form-control" multiple required>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="datetime-local" id="start_time" name="start_time" class="form-control" required>
                        </div>

                        <div id="errorMessages" style="color: red;"></div>

                        <button type="submit" class="btn btn-primary">Create Meeting</button>
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

        fetch('get_subjects.php')
            .then(response => response.json())
            .then(data => {
                if (data.subjects) {
                    const subjectSelect = document.getElementById('subject_ids');
                    data.subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.ID;
                        option.textContent = subject.title;
                        subjectSelect.appendChild(option);
                    });
                } else if (data.error) {
                    alert(`Error fetching subjects: ${data.error}`);
                }
            })
            .catch(error => {
                console.error('Error fetching subjects:', error);
                alert('An error occurred while fetching subjects.');
            });

        document.getElementById('createMeetingFormm').addEventListener('submit', function (event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            document.getElementById('errorMessages').textContent = '';

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

