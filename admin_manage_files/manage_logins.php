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
    $userID = intval($_POST['userID']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    $conn->query("UPDATE logins SET username = '$username' WHERE userID = $userID");

    if (!empty($password)) {
        $conn->query("UPDATE logins SET passwordHash = '$password' WHERE userID = $userID");
    }

    header("Location: manage_logins.php");
    exit();
}
// Load Edit
$editLogin = null;
if ($action === "edit" && $id) {
    $editLogin = $conn->query("SELECT * FROM logins WHERE userID = $id")->fetch_assoc();
}
// Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_login'] = $conn->query("SELECT * FROM logins WHERE userID = $id")->fetch_assoc();
    $conn->query("DELETE FROM logins WHERE userID = $id");
    header("Location: manage_logins.php");
    exit();
}

// Restore (Redo)
if ($action === "restore" && isset($_SESSION['deleted_login'])) {
    $d = $_SESSION['deleted_login'];
    $conn->query("INSERT INTO logins (userID, username, passwordHash)
                  VALUES ({$d['userID']}, '{$d['username']}', '{$d['passwordHash']}')");
    unset($_SESSION['deleted_login']);
    header("Location: manage_logins.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Logins</title>
</head>
<body>
    <h2>🔐 Login Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_login'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Login for user ID <?= $_SESSION['deleted_login']['userID'] ?> deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <?php if ($editLogin): ?>
        <h3>✏️ Edit Login</h3>
        <form method="post">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="userID" value="<?= $editLogin['userID'] ?>">
            User ID: <input type="text" value="<?= $editLogin['userID'] ?>" disabled><br><br>
            Username: <input type="text" name="username" required value="<?= htmlspecialchars($editLogin['username']) ?>"><br><br>
            New Password: <input type="password" name="password"><br><br>
            <input type="submit" value="Update Login">
            <a href="manage_logins.php" style="margin-left: 10px; color: red;">Cancel</a>
        </form>
        <hr>
    <?php endif; ?>

    <h3>📋 Login List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>User ID</th><th>Username</th><th>passwordHash</th><th>Actions</th>
        </tr>
        <?php
        $logins = $conn->query("SELECT * FROM logins");
        while ($row = $logins->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['userID']}</td>
                    <td>" . htmlspecialchars($row['username']) . "</td>
                    <td>" . htmlspecialchars($row['passwordHash']) . "</td>
                    <td>
                        <a href='?action=edit&id={$row['userID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$row['userID']}' onclick=\"return confirm('Delete login for this user?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
