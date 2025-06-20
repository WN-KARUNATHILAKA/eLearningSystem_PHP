<?php
session_start();
include '../db.php';

if (!isset($_SESSION['userID']) || $_SESSION['roleID'] != 1) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$roleID = $_GET['id'] ?? null;

// Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $roleName = $conn->real_escape_string($_POST['role']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO roles (role) VALUES ('$roleName')");
    } elseif ($formAction === "edit" && isset($_POST['roleID'])) {
        $rid = intval($_POST['roleID']);
        $conn->query("UPDATE roles SET role = '$roleName' WHERE roleID = $rid");
    }

    header("Location: manage_roles.php");
    exit();
}
// Load edit
$editRole = null;
if ($action === "edit" && $roleID) {
    $result = $conn->query("SELECT * FROM roles WHERE roleID = '$roleID'");
    if ($result->num_rows === 1) {
        $editRole = $result->fetch_assoc();
    }
}
// Delete
if ($action === "delete" && $roleID) {
    $deletedRole = $conn->query("SELECT * FROM roles WHERE roleID = '$roleID'")->fetch_assoc();
    $_SESSION['deleted_role'] = $deletedRole;
    $conn->query("DELETE FROM roles WHERE roleID = '$roleID'");
    header("Location: manage_roles.php");
    exit();
}

// Redo
if ($action === "restore" && isset($_SESSION['deleted_role'])) {
    $roleName = $conn->real_escape_string($_SESSION['deleted_role']['role']);
    $conn->query("INSERT INTO roles (role) VALUES ('$roleName')");
    unset($_SESSION['deleted_role']);
    header("Location: manage_roles.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Roles</title>
</head>
<body>
    <h2>👤 Role Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_role'])): ?>
        <p style="background: #fff3cd; padding: 10px; border: 1px solid #ffeeba;">
            🗑️ Role "<?php echo htmlspecialchars($_SESSION['deleted_role']['role']); ?>" deleted.
            <a href="manage_roles.php?action=restore" style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <h3><?= $editRole ? "✏️ Edit Role" : "➕ Add Role" ?></h3>
    <form method="post">
        <input type="hidden" name="form_action" value="<?= $editRole ? 'edit' : 'add' ?>">
        <?php if ($editRole): ?>
            <input type="hidden" name="roleID" value="<?= $editRole['roleID'] ?>">
        <?php endif; ?>
        Role Name: <input type="text" name="role" required value="<?= htmlspecialchars($editRole['role'] ?? '') ?>">
        <input type="submit" value="<?= $editRole ? 'Update' : 'Add' ?>">
        <?php if ($editRole): ?>
            <a href="manage_roles.php" style="margin-left: 20px; background-color: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px;">🔄 Cancel Edit</a>
        <?php endif; ?>
    </form>

    <hr>
    <h3>📋 Role List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Role ID</th><th>Role</th><th>Actions</th>
        </tr>
        <?php
        $roles = $conn->query("SELECT * FROM roles");
        while ($r = $roles->fetch_assoc()) {
            echo "<tr>
                    <td>{$r['roleID']}</td>
                    <td>" . htmlspecialchars($r['role']) . "</td>
                    <td>
                        <a href='manage_roles.php?action=edit&id={$r['roleID']}'>✏️ Edit</a> |
                        <a href='manage_roles.php?action=delete&id={$r['roleID']}' onclick=\"return confirm('Delete this role?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
