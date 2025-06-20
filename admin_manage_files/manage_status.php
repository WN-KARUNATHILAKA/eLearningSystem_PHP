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
    $statusName = $conn->real_escape_string($_POST['status']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO status (status) VALUES ('$statusName')");
    } elseif ($formAction === "edit") {
        $sid = intval($_POST['statusID']);
        $conn->query("UPDATE status SET status = '$statusName' WHERE statusID = $sid");
    }

    header("Location: manage_status.php");
    exit();
}
// Load Edit
$editStatus = null;
if ($action === "edit" && $id) {
    $editStatus = $conn->query("SELECT * FROM status WHERE statusID = $id")->fetch_assoc();
}
// Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_status'] = $conn->query("SELECT * FROM status WHERE statusID = $id")->fetch_assoc();
    $conn->query("DELETE FROM status WHERE statusID = $id");
    header("Location: manage_status.php");
    exit();
}

// Redo (Restore)
if ($action === "restore" && isset($_SESSION['deleted_status'])) {
    $s = $_SESSION['deleted_status'];
    $status = $conn->real_escape_string($s['status']);
    $conn->query("INSERT INTO status (status) VALUES ('$status')");
    unset($_SESSION['deleted_status']);
    header("Location: manage_status.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Status</title>
</head>
<body>
    <h2>📌 Status Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_status'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Status \"<?= htmlspecialchars($_SESSION['deleted_status']['status']) ?>\" deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <h3><?= $editStatus ? "✏️ Edit Status" : "➕ Add Status" ?></h3>
    <form method="post">
        <input type="hidden" name="form_action" value="<?= $editStatus ? 'edit' : 'add' ?>">
        <?php if ($editStatus): ?>
            <input type="hidden" name="statusID" value="<?= $editStatus['statusID'] ?>">
        <?php endif; ?>
        Status Name: <input type="text" name="status" required value="<?= htmlspecialchars($editStatus['status'] ?? '') ?>">
        <input type="submit" value="<?= $editStatus ? 'Update' : 'Add' ?> Status">
        <?php if ($editStatus): ?>
            <a href="manage_status.php" style="margin-left: 20px; color: red;">🔄 Cancel</a>
        <?php endif; ?>
    </form>

    <hr>
    <h3>📋 Status List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Status ID</th><th>Status</th><th>Actions</th>
        </tr>
        <?php
        $statuses = $conn->query("SELECT * FROM status");
        while ($s = $statuses->fetch_assoc()) {
            echo "<tr>
                    <td>{$s['statusID']}</td>
                    <td>" . htmlspecialchars($s['status']) . "</td>
                    <td>
                        <a href='?action=edit&id={$s['statusID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$s['statusID']}' onclick=\"return confirm('Delete this status?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
