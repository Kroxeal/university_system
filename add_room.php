<?php
ob_start();
session_start();
include 'includes/db.php' ;
include 'includes/navbar.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: 403.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    ob_end_clean();
    $location = trim($_POST['location']);
    $number = trim($_POST['number']);
    $capacity = (int)trim($_POST['capacity']);

    if (
        empty($location) ||
        empty($number) ||
        empty($capacity)
    ) {
        echo json_encode(['error' => 'All fields are required!']);
        exit();
    }

    if (strlen($location) < 3 || strlen($location) > 100) {
        echo json_encode(['error' => 'Location must be between 3 and 100 characters!']);
        exit();
    }
    if (strlen($number) < 3 || strlen($number) > 100) {
        echo json_encode(['error' => 'Number must be between 3 and 100 characters!']);
        exit();
    }
    if ($capacity > 100 || $capacity < 5) {
        echo json_encode(['error' => 'Capacity must not be greater than 100 characters!']);
        exit();
    }

    try{
        $stmt = $conn->prepare("INSERT INTO rooms (location, number, capacity) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $location, $number, $capacity);
        $stmt->execute();

        echo json_encode(['success' => 'Room successfully added!']);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    } finally{
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
    <title>Add Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Add a New Room</h4>
                    </div>
                    <div class="card-body">
                        <form id="addRoomForm" method="POST" action="add_room.php">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" maxlength="50" placeholder="Enter room location" required>
                            </div>
                            <div class="mb-3">
                                <label for="number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="number" name="number" maxlength="50" placeholder="Enter room number" required>
                            </div>
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" maxlength="50" max="100" placeholder="Enter room capacity" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Add Room</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.getElementById('addRoomForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch(form.action, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
        if (data.error) {
            alert(data.error);
        } else if (data.success) {
            alert(data.success);
            form.reset();
        }
        console.log(data)
    })
        .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again later.');
    });
        });
    </script>
<script>
    document.getElementById('capacity').addEventListener('input', function (e) {
        const input = e.target;
        const maxLength = 3;

        if (input.value.length > maxLength) {
            input.value = input.value.slice(0, maxLength);
            alert(`Capacity cannot exceed ${maxLength} digits.`);
        }
    });
</script>
</body>
</html>
