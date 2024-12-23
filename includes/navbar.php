<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>

</head>
<body class="bg-light">


<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="index.php">University</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">


            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">Analytics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="roomsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Room Management
                        </a>
                        <div class="dropdown-menu" aria-labelledby="roomsDropdown">
                            <a class="dropdown-item" href="add_room.php">Add Room</a>
<!--                            <a class="dropdown-item" href="view_rooms.php">View Rooms</a>-->
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="meetingsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Meetings
                        </a>
                        <div class="dropdown-menu" aria-labelledby="meetingsDropdown">
                            <a class="dropdown-item" href="add_meeting.php">Add Meeting</a>
                            <a class="dropdown-item" href="get_all_meetings.php">All Meetings</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="subjectsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Subjects Management
                        </a>
                        <div class="dropdown-menu" aria-labelledby="subjectsDropdown">
                            <a class="dropdown-item" href="add_subject.php">Add Subject</a>
                            <a class="dropdown-item" href="get_all_subjects.php">All Subjects</a>
                        </div>
                    </li>

                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">Analytics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_grades.php">Add Marks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="get_all_meetings.php">All Meetings</a>
                    </li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">Analytics</a>
                    </li>
<!--                    <li class="nav-item">-->
<!--                        <a class="nav-link" href="view_grades.php">My Marks</a>-->
<!--                    </li>-->
                    <li class="nav-item">
                        <a class="nav-link" href="get_all_meetings.php">All Meetings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="personal_cabinet.php">Cabinet</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Log Out</a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#loginModal">Sign In</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#registerModal">Sign Up</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>



<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Sign in</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <form id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" maxlength="50" required>
                    </div>
                    <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
                    <button type="submit" class="btn btn-primary">Sign in</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel">Sign up</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="registerFormm" method="POST" action="/university_course_project/register.php">
                    <div class="form-group">
                        <label for="username_da">Username</label>
                        <input type="text" class="form-control" id="username_da" name="username_da" maxlength="50" required>
                        <small id="usernameHelp" class="form-text text-danger" style="display: none;">Username already exists</small>
                    </div>
                    <div class="form-group">
                        <label for="password_da">Password</label>
                        <input type="password" class="form-control" id="password_da" name="password_da" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="50" required>
                        <small id="passwordHelp" class="form-text text-danger" style="display: none;">Passwords do not match</small>
                    </div>
                    <div id="errorMessageSignUp" class="alert alert-danger" style="display: none;"></div>
                    <button type="submit" class="btn btn-primary">Sign up</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('registerFormm').addEventListener('submit', function(event) {
        event.preventDefault();
        var registerForm = document.getElementById('registerFormm');
        console.log(registerForm)

        var username = document.getElementById('username_da').value;
        var password = document.getElementById('password_da').value;
        var confirmPassword = document.getElementById('confirm_password').value;

        var passwordHelp = document.getElementById('passwordHelp');
        var usernameHelp = document.getElementById('usernameHelp');
        var errorMessage = document.getElementById('errorMessageSignUp');
        console.log(username)
        console.log(password)
        console.log(confirmPassword)
        console.log(registerForm)


        passwordHelp.style.display = 'none';
        usernameHelp.style.display = 'none';
        errorMessage.style.display = 'none';

        if (password !== confirmPassword) {
            passwordHelp.textContent = "Password do not match";
            passwordHelp.style.display = 'block';
            return;
        }

        const formData = new FormData(registerForm);
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }


        fetch('/university_course_project/register.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration successful!');
                    $('#registerModal').modal('hide');
                    $('.modal-backdrop').remove();
                    registerForm.reset();
                } else {
                    if (data.error.includes('Username')) {
                        usernameHelp.textContent = data.error;
                        usernameHelp.style.display = 'block';
                    } else {
                        errorMessage.textContent = data.error;
                        errorMessage.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred while connecting to the server. Please try again.';
                errorMessage.style.display = 'block';
            });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');

        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(loginForm);

            fetch('/university_course_project/login.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        errorMessage.textContent = data.error;
                        errorMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    errorMessage.textContent = 'An unexpected error occurred. Please try again.';
                    errorMessage.style.display = 'block';
                });
        });
    });
</script>


</body>
</html>

