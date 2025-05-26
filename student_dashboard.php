<?php
session_start();
require 'db.php';

// Access control: only students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login_student.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle club registration
if (isset($_POST['register_club'])) {
    $club_id = $_POST['club_id'];

    // Prevent duplicate registration using unique constraint
    $stmt = $conn->prepare("INSERT IGNORE INTO club_registrations (user_id, club_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $club_id);
    $stmt->execute();
}


$allowed_sort_by = ['club_name'];
$allowed_sort_by = ['club_name', 'is_registered'];
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_by) ? $_GET['sort_by'] : 'club_name';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';

//This query gets all clubs along with registration status for a specific student
// It uses a LEFT JOIN to include all clubs regardless of registration status
// The IF() function checks if a registration exists (1) or not (0)
// The ON clause matches clubs to registrations for a specific student
// Results are sorted dynamically based on $sort_by and $order parameters
$sql = "
        SELECT c.id, c.club_name, c.description,
        IF (cr.id IS NULL, 0, 1) AS is_registered
        FROM clubs c
        LEFT JOIN club_registrations cr
        ON c.id = cr.club_id AND cr.user_id = ?
        ORDER BY $sort_by $order";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Student Dashboard - Clubs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
        }

        h2 {
            margin-bottom: 10px;
            font-weight: 600;
            color: #222;
            text-align: center;
        }

        .logout-bar {
            text-align: right;
            margin-bottom: 30px;
        }

        .logout-bar a {
            text-decoration: none;
            color: rgb(255, 0, 0);
            font-weight: 600;
            border: 1.5px solid rgb(255, 0, 0);
            padding: 6px 12px;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
        }

        .logout-bar a:hover {
            background-color: rgb(255, 0, 0);
            color: white;
        }

        h3 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 6px;
            font-weight: 600;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
            color: #444;
        }

        table.data-table th,
        table.data-table td {
            padding: 14px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table.data-table tbody tr:hover {
            background-color: #f1f8ff;
        }

        .registered {
            color: #1e7e34;
            font-weight: 600;
            background-color: #d4edda;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
        }

        .not-registered {
            color: #856404;
            font-weight: 600;
            background-color: #fff3cd;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
        }

        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover:not(:disabled) {
            background-color: #0056b3;
        }

        .btn:disabled {
            background-color: #6c757d;
            cursor: default;
            color: #eee;
        }

        form {
            margin: 0;
        }

        @media (max-width: 600px) {
            .container {
                margin: 20px 15px;
                padding: 15px 20px;
            }

            table.data-table th,
            table.data-table td {
                padding: 10px 8px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Welcome Student, <?= htmlspecialchars($_SESSION['username']) ?></h2>
        <div class="logout-bar">
            <a href="logout.php">Logout</a>
        </div>

        <h3>Available Clubs</h3>

        <table class="data-table" aria-label="List of clubs and registration status">
            <thead>
                <tr>
                    <th scope="col">
                        <a href="?sort_by=club_name&order=<?= $sort_by === 'club_name' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                            Club Name <?= $sort_by === 'club_name' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th scope="col">Description</th>
                    <th scope="col" style="background-color: white;">
                        <a href="?sort_by=is_registered&order=<?= $sort_by === 'is_registered' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                            Status <?= $sort_by === 'is_registered' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($club = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($club['club_name']) ?></td>
                        <td><?= htmlspecialchars($club['description']) ?></td>
                        <td>
                            <?= $club['is_registered']
                                ? '<span class="registered" aria-label="Registered">Registered</span>'
                                : '<span class="not-registered" aria-label="Not Registered">Not Registered</span>'
                            ?>
                        </td>
                        <td>
                            <?php if (!$club['is_registered']): ?>
                                <form method="POST" aria-label="Register for <?= htmlspecialchars($club['club_name']) ?>">
                                    <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                    <button type="submit" name="register_club" class="btn">Register</button>
                                </form>
                            <?php else: ?>
                                <button class="btn" disabled>Registered</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>