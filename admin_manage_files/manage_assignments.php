<?php
session_start();
include '../db.php';

if (!isset($_SESSION['userID']) || $_SESSION['roleID'] != 1) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Add/Edit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $courseID = intval($_POST['courseID']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $dueDate = $conn->real_escape_string($_POST['dueDate']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO assignments (courseID, title, description, dueDate)
                      VALUES ($courseID, '$title', '$description', '$dueDate')");
    } elseif ($formAction === "edit") {
        $assignmentID = intval($_POST['assignmentID']);
        $conn->query("UPDATE assignments SET 
                        courseID = $courseID, 
                        title = '$title', 
                        description = '$description', 
                        dueDate = '$dueDate' 
                      WHERE assignmentID = $assignmentID");
    }

    header("Location: manage_assignments.php");
    exit();
}

// Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_assignment'] = $conn->query("SELECT * FROM assignments WHERE assignmentID = $id")->fetch_assoc();
    $conn->query("DELETE FROM assignments WHERE assignmentID = $id");
    header("Location: manage_assignments.php");
    exit();
}
// Load Edit
$editAssignment = null;
if ($action === "edit" && $id) {
    $editAssignment = $conn->query("SELECT * FROM assignments WHERE assignmentID = $id")->fetch_assoc();
}

// Redo (Restore)
if ($action === "restore" && isset($_SESSION['deleted_assignment'])) {
    $a = $_SESSION['deleted_assignment'];
    $courseID = intval($a['courseID']);
    $title = $conn->real_escape_string($a['title']);
    $description = $conn->real_escape_string($a['description']);
    $dueDate = $conn->real_escape_string($a['dueDate']);

    $conn->query("INSERT INTO assignments (courseID, title, description, dueDate)
                  VALUES ($courseID, '$title', '$description', '$dueDate')");

    unset($_SESSION['deleted_assignment']);
    header("Location: manage_assignments.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Assignments</title>
</head>
<body>
    <h2>📝 Assignment Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_assignment'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Assignment \"<?= htmlspecialchars($_SESSION['deleted_assignment']['title']) ?>\" deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <h3><?= $editAssignment ? "✏️ Edit Assignment" : "➕ Add Assignment" ?></h3>
    <form method="post">
        <input type="hidden" name="form_action" value="<?= $editAssignment ? 'edit' : 'add' ?>">
        <?php if ($editAssignment): ?>
            <input type="hidden" name="assignmentID" value="<?= $editAssignment['assignmentID'] ?>">
        <?php endif; ?>
        Course ID: <input type="number" name="courseID" required value="<?= $editAssignment['courseID'] ?? '' ?>"><br><br>
        Title: <input type="text" name="title" required value="<?= htmlspecialchars($editAssignment['title'] ?? '') ?>"><br><br>
        Description:<br>
        <textarea name="description" rows="4" cols="50" required><?= htmlspecialchars($editAssignment['description'] ?? '') ?></textarea><br><br>
        Due Date: <input type="date" name="dueDate" required value="<?= $editAssignment['dueDate'] ?? date('Y-m-d') ?>"><br><br>
        <input type="submit" value="<?= $editAssignment ? 'Update' : 'Add' ?> Assignment">
        <?php if ($editAssignment): ?>
            <a href="manage_assignments.php" style="margin-left: 10px; color: red;">🔄 Cancel</a>
        <?php endif; ?>
    </form>

    <hr>
    <h3>📋 Assignment List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th><th>Course</th><th>Title</th><th>Due</th><th>Actions</th>
        </tr>
        <?php
        $assignments = $conn->query("SELECT * FROM assignments");
        while ($a = $assignments->fetch_assoc()) {
            echo "<tr>
                    <td>{$a['assignmentID']}</td>
                    <td>{$a['courseID']}</td>
                    <td>" . htmlspecialchars($a['title']) . "</td>
                    <td>{$a['dueDate']}</td>
                    <td>
                        <a href='?action=edit&id={$a['assignmentID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$a['assignmentID']}'
                           onclick=\"return confirm('Delete this assignment?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
