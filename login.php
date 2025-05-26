<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit;
                } elseif ($user['role'] === 'student') {
                    header("Location: student_dashboard.php");
                    exit;
                } else {
                    $error = "User role is not recognized.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login | Club Monitoring System</title>
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
            max-width: 400px;
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
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
        }

        input[type="text"],
        input[type="password"],
        select {
            box-sizing: border-box;
            height: 44px;
        }

        .btn-primary {
            width: 100%;
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-primary:hover {
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
        <h2 class="form-title">Login</h2>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="form-box">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <span onclick="togglePassword()" style="position: absolute; right: 10px; top: 10px; cursor: pointer; font-size: 14px; color: #007bff;">👁</span>
                </div>
            </div>

            <script>
                function togglePassword() {
                    const pass = document.getElementById('password');
                    pass.type = pass.type === 'password' ? 'text' : 'password';
                }
            </script>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="form-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</body>

</html>