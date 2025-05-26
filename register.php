<?php
session_start();
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $course = $_POST['course'] ?? '';
    $year = $_POST['year'] ?? '';

    $valid_courses = ['BSIT', 'BSBA', 'BEed'];
    $valid_years = ['1st', '2nd', '3rd', '4th'];

    if (empty($fullname) || empty($username) || empty($password) || empty($confirm_password) || empty($course) || empty($year)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!in_array($course, $valid_courses) || !in_array($year, $valid_years)) {
        $error = "Invalid course or year selected.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, role, course, year) VALUES (?, ?, ?, 'student', ?, ?)");
            $stmt->bind_param("sssss", $fullname, $username, $hashed, $course, $year);
            if ($stmt->execute()) {
                $success = "Account registered successfully! Redirecting to login...";
                header("refresh:3;url=login.php");
            } else {
                $error = "Registration failed. Try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register | Club Monitoring System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .form-container {
            max-width: 440px;
            margin: 60px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            outline: none;
            background-color: #fff;
            appearance: none;
            height: 44px;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: #007bff;
        }

        .btn {
            width: 100%;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .form-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 14px;
        }

        .form-footer a {
            color: #007bff;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .alert-error {
            background: #ffe5e5;
            color: #d00000;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #ffcccc;
        }

        .alert-success {
            background: #e5ffe8;
            color: #007d33;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #b3ffcc;
        }

        select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            outline: none;
            background-color: #fff;
            height: 44px;
        }

        @media (max-width: 500px) {
            .form-container {
                margin: 30px 20px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2 class="form-title">Student Registration</h2>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" required
                    value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label for="course">Course</label>
                <select id="course" name="course" required>
                    <option value="" disabled <?= empty($_POST['course']) ? 'selected' : '' ?>>Select your course</option>
                    <option value="BSIT" <?= (isset($_POST['course']) && $_POST['course'] === 'BSIT') ? 'selected' : '' ?>>BSIT</option>
                    <option value="BSBA" <?= (isset($_POST['course']) && $_POST['course'] === 'BSBA') ? 'selected' : '' ?>>BSBA</option>
                    <option value="BEed" <?= (isset($_POST['course']) && $_POST['course'] === 'BEed') ? 'selected' : '' ?>>BEed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="year">Year</label>
                <select id="year" name="year" required>
                    <option value="" disabled <?= empty($_POST['year']) ? 'selected' : '' ?>>Select your year</option>
                    <option value="1st" <?= (isset($_POST['year']) && $_POST['year'] === '1st') ? 'selected' : '' ?>>1st</option>
                    <option value="2nd" <?= (isset($_POST['year']) && $_POST['year'] === '2nd') ? 'selected' : '' ?>>2nd</option>
                    <option value="3rd" <?= (isset($_POST['year']) && $_POST['year'] === '3rd') ? 'selected' : '' ?>>3rd</option>
                    <option value="4th" <?= (isset($_POST['year']) && $_POST['year'] === '4th') ? 'selected' : '' ?>>4th</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <p class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</body>

</html>
