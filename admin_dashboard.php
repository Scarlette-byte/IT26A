<?php
session_start();
require 'db.php';

// Access control: only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

// Handle create club
$create_success = '';
$create_error = '';
if (isset($_POST['create_club'])) {
    $club_name = trim($_POST['club_name']);
    $description = trim($_POST['description']);

    if (empty($club_name)) {
        $create_error = "Club name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO clubs (club_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $club_name, $description);
        if ($stmt->execute()) {
            $create_success = "Club created successfully.";
        } else {
            $create_error = "Failed to create club. Try again.";
        }
        $stmt->close();
    }
}

// Handle update club
$edit_success = '';
$edit_error = '';
if (isset($_POST['update_club'])) {
    $club_id = $_POST['edit_club_id'];
    $club_name = trim($_POST['edit_club_name']);
    $description = trim($_POST['edit_description']);

    if (empty($club_name)) {
        $edit_error = "Club name is required.";
    } else {
        $stmt = $conn->prepare("UPDATE clubs SET club_name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $club_name, $description, $club_id);
        if ($stmt->execute()) {
            $edit_success = "Club updated successfully.";
        } else {
            $edit_error = "Failed to update club.";
        }
        $stmt->close();
    }
}

// Handle delete club
if (isset($_GET['delete'])) {
    $club_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM clubs WHERE id=?");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $stmt->close();
    // Redirect to avoid resubmission
    $redirect_url = "admin_dashboard.php?sort_by=" . urlencode($_GET['sort_by'] ?? '') . "&order=" . urlencode($_GET['order'] ?? '');
    header("Location: $redirect_url");
    exit;
}

$sort_by = $_GET['sort_by'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';

$allowed_sort = ['club_name', 'created_at'];
$allowed_order = ['asc', 'desc'];

if (!in_array($sort_by, $allowed_sort)) $sort_by = 'created_at';
if (!in_array($order, $allowed_order)) $order = 'desc';

$result = $conn->query("SELECT * FROM clubs ORDER BY $sort_by $order");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Manage Clubs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/lucide-static/font/lucide.css" />

    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #333;
        }

        .container {
            max-width: 960px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .welcome-message {
            font-weight: 600;
            color: #333;
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

        .btn,
        button {
            background-color: #007bff;
            border: none;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover,
        button:hover {
            background-color: #0056b3;
        }

        .action-btn {
            padding: 6px 10px;
            margin: 0 4px;
            font-size: 0.85rem;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        s
        .edit-btn {
            background: #17a2b8;
            color: white;
        }

        .edit-btn:hover {
            background: #138496;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #a71d2a;
        }

        .view-btn {
            background: #6c757d;
            color: white;
        }

        .view-btn:hover {
            background: #5a6268;
        }

        #createClubForm,
        #editClubForm {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #ddd;
            display: none;
        }

        label {
            display: block;
            font-weight: 600;
            margin-top: 10px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            resize: vertical;
        }

        .alert {
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .club-card {
            background: #ffffff;
            border: 1px solid #e3e6ea;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .club-card h3 {
            margin: 0 0 10px 0;
        }

        .club-description {
            margin-bottom: 10px;
            color: #555;
        }

        .club-actions {
            display: flex;
            flex-wrap: wrap;
        }

        .lucide {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 600px) {
            .container {
                margin: 20px 10px;
            }

            .club-actions {
                flex-direction: column;
            }

            .action-btn {
                margin-bottom: 6px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Clubs Management</h2>

        <div class="admin-header">
            <div class="welcome-message">Welcome, Administrator!</div>
            <div class="logout-bar">
            <a href="logout.php">Logout</a>
        </div>
        </div>

        <button id="toggleFormBtn">+ Add Club</button>

        <?php if ($create_success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($create_success) ?></div>
        <?php endif; ?>
        <?php if ($create_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($create_error) ?></div>
        <?php endif; ?>

        <form id="createClubForm" method="POST">
            <label for="club_name">Club Name</label>
            <input type="text" id="club_name" name="club_name" required />

            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>

            <button type="submit" name="create_club">Create Club</button>
        </form>

        <?php if ($edit_success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($edit_success) ?></div>
        <?php endif; ?>
        <?php if ($edit_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($edit_error) ?></div>
        <?php endif; ?>

        <form id="editClubForm" method="POST">
            <input type="hidden" id="edit_club_id" name="edit_club_id" />
            <label for="edit_club_name">Club Name</label>
            <input type="text" id="edit_club_name" name="edit_club_name" required />

            <label for="edit_description">Description</label>
            <textarea id="edit_description" name="edit_description"></textarea>

            <button type="submit" name="update_club">Update Club</button>
            <button type="button" id="editCancelBtn" class="delete-btn">Cancel</button>
        </form>

        <div style="text-align:center; margin: 20px 0;">
            <strong>Sort By:</strong>
            <a class="btn" href="?sort_by=club_name&order=asc">Name ↑</a>
            <a class="btn" href="?sort_by=club_name&order=desc">Name ↓</a>
            <a class="btn" href="?sort_by=created_at&order=asc">Date ↑</a>
            <a class="btn" href="?sort_by=created_at&order=desc">Date ↓</a>
        </div>

        <?php while ($club = $result->fetch_assoc()): ?>
            <div class="club-card">
                <h3><?= htmlspecialchars($club['club_name']) ?></h3>
                <p class="club-description"><?= nl2br(htmlspecialchars($club['description'])) ?></p>
                <div class="club-actions">
                    <button class="action-btn edit-btn" data-id="<?= $club['id'] ?>"
                        data-name="<?= htmlspecialchars($club['club_name'], ENT_QUOTES) ?>"
                        data-description="<?= htmlspecialchars($club['description'], ENT_QUOTES) ?>">
                        <i class="lucide" data-icon="pencil"></i> Edit
                    </button>
                    <a class="action-btn delete-btn" href="?delete=<?= $club['id'] ?>&sort_by=<?= urlencode($sort_by) ?>&order=<?= urlencode($order) ?>"
                        onclick="return confirm('Delete this club?');">
                        <i class="lucide" data-icon="trash"></i> Delete
                    </a>
                    <a class="action-btn view-btn" href="view_club_students.php?club_id=<?= $club['id'] ?>">
                        <i class="lucide" data-icon="users"></i> View Members
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const createForm = document.getElementById('createClubForm');
        const editForm = document.getElementById('editClubForm');
        const editCancelBtn = document.getElementById('editCancelBtn');

        toggleFormBtn.addEventListener('click', () => {
            createForm.style.display = createForm.style.display === 'block' ? 'none' : 'block';
            editForm.style.display = 'none';
        });

        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const name = button.dataset.name;
                const description = button.dataset.description;

                document.getElementById('edit_club_id').value = id;
                document.getElementById('edit_club_name').value = name;
                document.getElementById('edit_description').value = description;

                createForm.style.display = 'none';
                editForm.style.display = 'block';
                window.scrollTo({
                    top: editForm.offsetTop - 20,
                    behavior: 'smooth'
                });
            });
        });

        editCancelBtn.addEventListener('click', () => {
            editForm.style.display = 'none';
        });
    </script>
</body>

</html>