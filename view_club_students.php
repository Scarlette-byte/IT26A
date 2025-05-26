<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

if (!isset($_GET['club_id'])) {
    echo "No club selected.";
    exit;
}

$club_id = intval($_GET['club_id']);

// Sorting setup
$valid_sort_columns = ['fullname', 'course', 'year', 'registered_at'];
$sort_column = $_GET['sort_column'] ?? 'registered_at';
$sort_order = strtoupper($_GET['sort_order'] ?? 'DESC');

if (!in_array($sort_column, $valid_sort_columns)) {
    $sort_column = 'registered_at';
}
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Get club info
$club_stmt = $conn->prepare("SELECT * FROM clubs WHERE id = ?");
$club_stmt->bind_param("i", $club_id);
$club_stmt->execute();
$club_result = $club_stmt->get_result();

if ($club_result->num_rows === 0) {
    echo "Club not found.";
    exit;
}

$club = $club_result->fetch_assoc();

// Build dynamic ORDER BY
switch ($sort_column) {
    case 'course':
        $order_by = "FIELD(u.course, 'BSIT', 'BSBA', 'BEEd') $sort_order";
        break;
    case 'year':
        $order_by = "FIELD(u.year, '1st', '2nd', '3rd', '4th') $sort_order";
        break;
    case 'fullname':
        $order_by = "u.fullname $sort_order";
        break;
    default:
        $order_by = "r.registered_at $sort_order";
}

// This query retrieves all registered students for a specific club
// It joins the club_registrations table with users table to get student details
// The WHERE clause filters for a specific club ID
// Results are sorted dynamically based on the $order_by parameter
$query = "
    SELECT u.fullname, u.course, u.year, r.registered_at 
    FROM club_registrations r
    INNER JOIN users u ON r.user_id = u.id
    WHERE r.club_id = ?
    ORDER BY $order_by
";
$students_stmt = $conn->prepare($query);
$students_stmt->bind_param("i", $club_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$total_students = $students_result->num_rows;

// Sorting helper functions
function sortUrl($column, $currentColumn, $currentOrder, $club_id)
{
    $order = ($column === $currentColumn && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return "?club_id=$club_id&sort_column=$column&sort_order=$order";
}

function sortArrow($column, $currentColumn, $currentOrder) {
    if ($column !== $currentColumn) return '↕'; 
    return $currentOrder === 'ASC' ? '▲' : '▼';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>View Club Members - <?= htmlspecialchars($club['club_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        h2 {
            margin-top: 0;
            color: #333;
        }

        .club-info {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .club-info p {
            margin: 8px 0;
        }

        a.back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }

        a.back-link:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
            word-wrap: break-word;
        }

        th {
            background: #f1f1f1;
            font-weight: 600;
            width: 25%;
            position: relative;
        }

        th a {
            display: inline-block;
            width: 100%;
            color: #333;
            text-decoration: none;
        }

        .sort-arrow {
            display: inline-block;
            width: 1em;
            text-align: center;
            margin-left: 5px;
            opacity: 0.6;
        }


        tr:nth-child(even) {
            background: #fafafa;
        }

        .no-data {
            padding: 20px;
            text-align: center;
            color: #777;
            background: #f8f9fa;
            border-radius: 8px;
        }

        @media (max-width: 600px) {
            .container {
                margin: 20px 10px;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                border-bottom: 1px solid #ccc;
                padding-bottom: 10px;
            }

            td {
                padding: 8px 10px;
                text-align: right;
                position: relative;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                text-align: left;
                font-weight: bold;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2><?= htmlspecialchars($club['club_name']) ?> Members</h2>

        <div class="club-info">
            <p><strong>Total Registered Students:</strong> <?= $total_students ?></p>
        </div>

        <?php if ($total_students > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><a href="<?= sortUrl('fullname', $sort_column, $sort_order, $club_id) ?>">Full Name<span class="sort-arrow"><?= sortArrow('fullname', $sort_column, $sort_order) ?></span></a></th>
                        <th><a href="<?= sortUrl('course', $sort_column, $sort_order, $club_id) ?>">Course<span class="sort-arrow"><?= sortArrow('course', $sort_column, $sort_order) ?></span></a></th>
                        <th><a href="<?= sortUrl('year', $sort_column, $sort_order, $club_id) ?>">Year<span class="sort-arrow"><?= sortArrow('year', $sort_column, $sort_order) ?></span></a></th>
                        <th><a href="<?= sortUrl('registered_at', $sort_column, $sort_order, $club_id) ?>">Registered At<span class="sort-arrow"><?= sortArrow('registered_at', $sort_column, $sort_order) ?></span></a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Full Name"><?= htmlspecialchars($student['fullname']) ?></td>
                            <td data-label="Course"><?= htmlspecialchars($student['course']) ?></td>
                            <td data-label="Year"><?= htmlspecialchars($student['year']) ?></td>
                            <td data-label="Registered At"><?= htmlspecialchars($student['registered_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No students have registered for this club yet.</div>
        <?php endif; ?>
    </div>
</body>

</html>