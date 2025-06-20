<?php
session_start();
include '../db.php';

if (!isset($_SESSION['userID']) || $_SESSION['roleID'] != 1) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$userID = $_GET['id'] ?? null;

// Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $lastName  = $conn->real_escape_string($_POST['lastName']);
    $email     = $conn->real_escape_string($_POST['email']);
    $mobile    = $conn->real_escape_string($_POST['mobile']);
    $roleID    = intval($_POST['roleID']);
    $username  = $conn->real_escape_string($_POST['username']);
    $password  = $conn->real_escape_string($_POST['password']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO users (firstName, lastName, email, mobile, roleID)
                      VALUES ('$firstName', '$lastName', '$email', '$mobile', $roleID)");
        $newUserID = $conn->insert_id;

        $conn->query("INSERT INTO logins (userID, username, passwordHash)
                      VALUES ($newUserID, '$username', '$password')");

    } elseif ($formAction === "edit") {
        $editID = intval($_POST['userID']);

        $conn->query("UPDATE users SET 
                        firstName = '$firstName', 
                        lastName = '$lastName', 
                        email = '$email', 
                        mobile = '$mobile', 
                        roleID = $roleID 
                      WHERE userID = $editID");

        $conn->query("UPDATE logins SET username = '$username' WHERE userID = $editID");

        if (!empty($password)) {
            $conn->query("UPDATE logins SET passwordHash = '$password' WHERE userID = $editID");
        }
    }

    header("Location: manage_users.php");
    exit();
}
// Load Edit
$editUser = null;
if ($action === "edit" && $userID) {
    $result = $conn->query("SELECT u.*, l.username FROM users u 
                            LEFT JOIN logins l ON u.userID = l.userID
                            WHERE u.userID = '$userID'");
    if ($result->num_rows === 1) {
        $editUser = $result->fetch_assoc();
    }
}
// Delete
if ($action === "delete" && $userID) {
    $user = $conn->query("SELECT * FROM users WHERE userID = '$userID'")->fetch_assoc();
    $login = $conn->query("SELECT * FROM logins WHERE userID = '$userID'")->fetch_assoc();
    $enrollments = $conn->query("SELECT * FROM enrollments WHERE userID = '$userID'");
    $submissions = $conn->query("SELECT * FROM submissions WHERE userID = '$userID'");

    $_SESSION['deleted_user'] = [
        'user' => $user,
        'login' => $login,
        'enrollments' => $enrollments->fetch_all(MYSQLI_ASSOC),
        'submissions' => $submissions->fetch_all(MYSQLI_ASSOC)
    ];

    $conn->query("DELETE FROM enrollments WHERE userID = '$userID'");
    $conn->query("DELETE FROM submissions WHERE userID = '$userID'");
    $conn->query("DELETE FROM logins WHERE userID = '$userID'");
    $conn->query("DELETE FROM users WHERE userID = '$userID'");

    header("Location: manage_users.php");
    exit();
}

// Restore (Redo)
if ($action === "restore" && isset($_SESSION['deleted_user'])) {
    $data = $_SESSION['deleted_user'];
    $user = $data['user'];
    $login = $data['login'];
    $enrollments = $data['enrollments'];
    $submissions = $data['submissions'];

    $conn->query("INSERT INTO users (firstName, lastName, email, mobile, roleID)
                  VALUES (
                      '{$conn->real_escape_string($user['firstName'])}',
                      '{$conn->real_escape_string($user['lastName'])}',
                      '{$conn->real_escape_string($user['email'])}',
                      '{$conn->real_escape_string($user['mobile'])}',
                      {$user['roleID']}
                  )");

    $newUserID = $conn->insert_id;

    if ($login) {
        $username = $conn->real_escape_string($login['username']);
        $password = $conn->real_escape_string($login['passwordHash']);
        $conn->query("INSERT INTO logins (userID, username, passwordHash)
                      VALUES ($newUserID, '$username', '$password')");
    }

    foreach ($enrollments as $e) {
        $conn->query("INSERT INTO enrollments (userID, courseID, enrollmentDate, statusID)
                      VALUES (
                          $newUserID,
                          {$e['courseID']},
                          '{$e['enrollmentDate']}',
                          {$e['statusID']}
                      )");
    }

    foreach ($submissions as $s) {
        $fileURL = $conn->real_escape_string($s['fileURL'] ?? '');
        $conn->query("INSERT INTO submissions (assignmentID, userID, submittedAt, grade, fileURL)
                      VALUES (
                          {$s['assignmentID']},
                          $newUserID,
                          '{$s['submittedAt']}',
                          '{$s['grade']}',
                          '$fileURL'
                      )");
    }

    unset($_SESSION['deleted_user']);
    header("Location: manage_users.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <script>
        function toggleAddForm() {
            const form = document.getElementById("addUserForm");
            form.style.display = form.style.display === "none" ? "block" : "none";
        }
    </script>
</head>
<body>
    <h2>👤 User Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_user'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ User "<?php echo htmlspecialchars($_SESSION['deleted_user']['user']['firstName']); ?>" deleted.
            <a href="manage_users.php?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <?php if (!$editUser): ?>
        <button onclick="toggleAddForm()">➕ Add User</button>
    <?php endif; ?>

    <div id="addUserForm" style="<?= $editUser ? 'display:block;' : 'display:none;' ?> margin-top: 20px;">
        <h3><?= $editUser ? "✏️ Edit User" : "➕ Add User" ?>
            <?php if ($editUser): ?>
                <a href="manage_users.php" style="margin-left: 20px; background-color: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px;">🔄 Cancel Edit</a>
            <?php endif; ?>
        </h3>

        <form method="post">
            <input type="hidden" name="form_action" value="<?= $editUser ? 'edit' : 'add' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="userID" value="<?= $editUser['userID'] ?>">
            <?php endif; ?>
            First Name: <input type="text" name="firstName" required value="<?= htmlspecialchars($editUser['firstName'] ?? '') ?>"><br><br>
            Last Name: <input type="text" name="lastName" required value="<?= htmlspecialchars($editUser['lastName'] ?? '') ?>"><br><br>
            Email: <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>"><br><br>
            Mobile: <input type="text" name="mobile" required value="<?= htmlspecialchars($editUser['mobile'] ?? '') ?>"><br><br>
            Role:
            <select name="roleID" required>
                <?php
                $roles = $conn->query("SELECT * FROM roles");
                while ($r = $roles->fetch_assoc()) {
                    $selected = ($editUser && $editUser['roleID'] == $r['roleID']) ? 'selected' : '';
                    echo "<option value='{$r['roleID']}' $selected>" . htmlspecialchars($r['role']) . "</option>";
                }
                ?>
            </select><br><br>
            Username: <input type="text" name="username" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>"><br><br>
            Password: <input type="password" name="password" <?= $editUser ? '' : 'required' ?>><br><br>
            <input type="submit" value="<?= $editUser ? 'Update' : 'Add' ?> User">
        </form>
    </div>

    <hr>
    <h3>📋 User List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th> UserID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Username</th><th>passwordHash</th><th>Actions</th>
        </tr>
        <?php
        $users = $conn->query("SELECT u.*, r.role, l.username, l.passwordHash FROM users u 
                              JOIN roles r ON u.roleID = r.roleID
                              LEFT JOIN logins l ON u.userID = l.userID");
        while ($u = $users->fetch_assoc()) {
            echo "<tr>
                    <td>{$u['userID']}</td>
                    <td>" . htmlspecialchars($u['firstName']) . " " . htmlspecialchars($u['lastName']) . "</td>
                    <td>" . htmlspecialchars($u['email']) . "</td>
                    <td>" . htmlspecialchars($u['mobile']) . "</td>
                    <td>" . htmlspecialchars($u['role']) . "</td>
                    <td>" . htmlspecialchars($u['username'] ?? '') . "</td>
                    <td>" . htmlspecialchars($u['passwordHash'] ?? '') . "</td>
                    <td>
                        <a href='manage_users.php?action=edit&id={$u['userID']}'>✏️ Edit</a> |
                        <a href='manage_users.php?action=delete&id={$u['userID']}' onclick=\"return confirm('Delete this user and all related data?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
