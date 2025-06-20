<?php
session_start();
include '../db.php';

if (!isset($_SESSION['userID']) || $_SESSION['roleID'] != 1) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Edit
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['form_action'] === "edit") {
    $instructorID = intval($_POST['instructorID']);
    $bio = $conn->real_escape_string($_POST['bio']);
    $conn->query("UPDATE instructors SET bio = '$bio' WHERE instructorID = $instructorID");
    header("Location: manage_instructors.php");
    exit();
}
// Load Edit
$editInstructor = null;
if ($action === "edit" && $id) {
    $editInstructor = $conn->query("SELECT * FROM instructors WHERE instructorID = $id")->fetch_assoc();
}
//  Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_instructor'] = $conn->query("SELECT * FROM instructors WHERE instructorID = $id")->fetch_assoc();
    $conn->query("DELETE FROM instructors WHERE instructorID = $id");
    header("Location: manage_instructors.php");
    exit();
}

// Restore (Redo)
if ($action === "restore" && isset($_SESSION['deleted_instructor'])) {
    $i = $_SESSION['deleted_instructor'];
    $bio = $conn->real_escape_string($i['bio']);
    $conn->query("INSERT INTO instructors (userID, bio) VALUES ({$i['userID']}, '$bio')");
    unset($_SESSION['deleted_instructor']);
    header("Location: manage_instructors.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Instructors</title>
</head>
<body>
    <h2>👨‍🏫 Instructor Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_instructor'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Instructor (User ID <?= $_SESSION['deleted_instructor']['userID'] ?>) deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <?php if ($editInstructor): ?>
        <h3>✏️ Edit Instructor Bio</h3>
        <form method="post">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="instructorID" value="<?= $editInstructor['instructorID'] ?>">
            User ID: <input type="text" disabled value="<?= $editInstructor['userID'] ?>"><br><br>
            Bio:<br>
            <textarea name="bio" rows="4" cols="50" required><?= htmlspecialchars($editInstructor['bio']) ?></textarea><br><br>
            <input type="submit" value="Update Bio">
            <a href="manage_instructors.php" style="margin-left: 10px; color: red;">Cancel</a>
        </form>
        <hr>
    <?php endif; ?>

    <h3>📋 Instructors List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>InstructorID</th><th>User ID</th><th>Bio</th><th>Actions</th>
        </tr>
        <?php
        $result = $conn->query("SELECT * FROM instructors");
        while ($r = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$r['instructorID']}</td>
                    <td>{$r['userID']}</td>
                    <td>" . nl2br(htmlspecialchars($r['bio'])) . "</td>
                    <td>
                        <a href='?action=edit&id={$r['instructorID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$r['instructorID']}' onclick=\"return confirm('Delete this instructor?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
