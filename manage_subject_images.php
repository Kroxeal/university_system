<?php
session_start();
include('includes/db.php');
include('includes/navbar.php');

$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    echo "Invalid Subject ID.";
    exit();
}

$image_directory = "uploads/subjects/";

if (!file_exists($image_directory)) {
    $error = "The directory '$image_directory' does not exist. Please contact the administrator.";
}

$query = "SELECT image_path FROM subjects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Subject not found.";
    exit();
}

$row = $result->fetch_assoc();
$image_path = $row['image_path'];

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if ($image_path && file_exists($image_path)) {
        if (is_writable($image_path)) {
            if (unlink($image_path)) {
                $success = "Image deleted successfully.";
                $update_query = "UPDATE subjects SET image_path = NULL WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('i', $subject_id);
                $update_stmt->execute();
            } else {
                $error = "Failed to delete the image. Possible reasons: file is locked or permissions issue.";
            }
        } else {
            $error = "The file is not writable or locked by another process.";
        }
    } else {
        $error = "Image not found.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET'){
    if (file_exists($image_path)) {
        if (!is_writable($image_path)) {
            $error = "Access denied. Please check the file permissions.";
        }
    }
}

if ($image_path && !file_exists($image_path)) {
    $warning = "The file specified in the database does not exist on the server.";

    $update_query = "UPDATE subjects SET image_path = NULL WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('i', $subject_id);
    $update_stmt->execute();

    $image_path = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($image_directory)) {
        if (!mkdir($image_directory, 0777, true)) {
            $error = "Failed to create the directory '$image_directory'. Please check permissions.";
        }
    }

    if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $image = $_FILES['image'];
        $max_size = 2 * 1024 * 1024; // 2 MB
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];

        if ($image['size'] > $max_size) {
            $error = "Image size exceeds 2MB.";
        } elseif (!in_array($image['type'], $allowed_types)) {
            $error = "Unsupported file type. Only JPEG, JPG, and PNG are allowed.";
        } elseif ($image['error'] !== 0) {
            $error = "Error uploading the image.";
        } elseif (!@getimagesize($image['tmp_name'])) {
            $error = "The uploaded file is corrupted or not a valid image.";
        } else {
            $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
            do {
                $unique_name = uniqid($subject_id . "_", true) . ".$extension";
                $new_image_path = $image_directory . $unique_name;
            } while (file_exists($new_image_path));

            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }

            if (!is_writable($image_directory)) {
                $error = "The directory '$image_directory' is not writable. Please check the folder permissions.";
            } else {
                if (move_uploaded_file($image['tmp_name'], $new_image_path)) {
                    $success = "Image uploaded successfully.";
                    $image_path = $new_image_path;

                    $update_query = "UPDATE subjects SET image_path = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param('si', $new_image_path, $subject_id);
                    $update_stmt->execute();
                } else {
                    $error = "Error saving the image. Please check the directory permissions.";
                }
            }
        }
    } else {
        $error = "No file uploaded.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subject Image</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">Manage Image for Subject ID: <?= htmlspecialchars($subject_id) ?></h2>

    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php } ?>
    <?php if (isset($success)) { ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php } ?>
    <?php if (isset($warning)) { ?>
        <div class="alert alert-warning"><?= htmlspecialchars($warning) ?></div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="form-group">
            <label for="image">Upload Image (JPEG, JPG, PNG)</label>
            <input type="file" name="image" accept=".png, .jpg, .jpeg" id="image" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>

    <h4>Current Image</h4>
    <?php if ($image_path && file_exists($image_path)) { ?>
        <div class="card" style="width: 18rem;">
            <img src="<?= $image_path ?>" class="card-img-top" alt="Subject Image">
            <div class="card-body">
                <a href="manage_subject_images.php?action=delete&subject_id=<?= $subject_id ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Are you sure you want to delete this image?')">Delete</a>
            </div>
        </div>
    <?php } else { ?>
        <p>No image uploaded for this subject.</p>
    <?php } ?>
</div>

</body>
</html>
