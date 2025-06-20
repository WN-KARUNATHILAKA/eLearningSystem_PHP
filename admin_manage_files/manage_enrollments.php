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
    $userID = intval($_POST['userID']);
    $courseID = intval($_POST['courseID']);
    $statusID = intval($_POST['statusID']);
    $enrollmentDate = $conn->real_escape_string($_POST['enrollmentDate']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO enrollments (userID, courseID, enrollmentDate, statusID)
                      VALUES ($userID, $courseID, '$enrollmentDate', $statusID)");
    } elseif ($formAction === "edit") {
        $enrollmentID = intval($_POST['enrollmentID']);
        $conn->query("UPDATE enrollments SET 
                        userID = $userID, 
                        courseID = $courseID, 
                        enrollmentDate = '$enrollmentDate', 
                        statusID = $statusID 
                      WHERE enrollmentID = $enrollmentID");
    }

    header("Location: manage_enrollments.php");
    exit();
}

// Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_enrollment'] = $conn->query("SELECT * FROM enrollments WHERE enrollmentID = $id")->fetch_assoc();
    $conn->query("DELETE FROM enrollments WHERE enrollmentID = $id");
    header("Location: manage_enrollments.php");
    exit();
}
// Load Edit
$editEnrollment = null;
if ($action === "edit" && $id) {
    $editEnrollment = $conn->query("SELECT * FROM enrollments WHERE enrollmentID = $id")->fetch_assoc();
}
// Redo (Restore)
if ($action === "restore" && isset($_SESSION['deleted_enrollment'])) {
    $e = $_SESSION['deleted_enrollment'];
    $conn->query("INSERT INTO enrollments (userID, courseID, enrollmentDate, statusID)
                  VALUES ({$e['userID']}, {$e['courseID']}, '{$e['enrollmentDate']}', {$e['statusID']})");
    unset($_SESSION['deleted_enrollment']);
    header("Location: manage_enrollments.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Enrollments</title>
</head>
<body>
    <h2>📘 Enrollment Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_enrollment'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Enrollment (User ID <?= $_SESSION['deleted_enrollment']['userID'] ?> in Course <?= $_SESSION['deleted_enrollment']['courseID'] ?>) deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <h3><?= $editEnrollment ? "✏️ Edit Enrollment" : "➕ Add Enrollment" ?></h3>
    <form method="post">
        <input type="hidden" name="form_action" value="<?= $editEnrollment ? 'edit' : 'add' ?>">
        <?php if ($editEnrollment): ?>
            <input type="hidden" name="enrollmentID" value="<?= $editEnrollment['enrollmentID'] ?>">
        <?php endif; ?>

        <!-- Student Dropdown -->
        User:
        <select name="userID" required>
    <option value="">-- Select Student --</option>
    <?php
    $students = $conn->query("SELECT u.userID, u.firstName, u.lastName 
                              FROM users u 
                              WHERE roleID = 4");
    while ($s = $students->fetch_assoc()) {
        $selected = ($editEnrollment && $editEnrollment['userID'] == $s['userID']) ? 'selected' : '';
        echo "<option value='{$s['userID']}' $selected>({$s['userID']}) {$s['firstName']} {$s['lastName']}</option>";
    }
    ?>
</select>
<br><br>

        <!-- Course Dropdown -->
        Course:
        <select name="courseID" required>
            <option value="">-- Select Course --</option>
            <?php
            $courses = $conn->query("SELECT courseID, courseTitle FROM courses");
            while ($c = $courses->fetch_assoc()) {
                $selected = ($editEnrollment && $editEnrollment['courseID'] == $c['courseID']) ? 'selected' : '';
                echo "<option value='{$c['courseID']}' $selected>{$c['courseTitle']}</option>";
            }
            ?>
        </select><br><br>

        Enrollment Date: 
        <input type="date" name="enrollmentDate" required value="<?= $editEnrollment['enrollmentDate'] ?? date('Y-m-d') ?>"><br><br>

        <!-- Status Dropdown -->
        Status:
        <select name="statusID" required>
            <option value="">-- Select Status --</option>
            <?php
            $statuses = $conn->query("SELECT * FROM status");
while ($s = $statuses->fetch_assoc()) {
    $selected = ($editEnrollment && $editEnrollment['statusID'] == $s['statusID']) ? 'selected' : '';
    echo "<option value='{$s['statusID']}' $selected>{$s['status']}</option>";
}

            ?>
        </select><br><br>

        <input type="submit" value="<?= $editEnrollment ? 'Update' : 'Add' ?> Enrollment">
        <?php if ($editEnrollment): ?>
            <a href="manage_enrollments.php" style="margin-left: 10px; color: red;">🔄 Cancel</a>
        <?php endif; ?>
    </form>

    <hr>
    <h3>📋 Enrollments List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th><th>Student</th><th>Course</th><th>Date</th><th>Status</th><th>Actions</th>
        </tr>
        <?php
       $enrollments = $conn->query("SELECT e.*, u.firstName, u.lastName, c.courseTitle, s.status 
                             FROM enrollments e 
                             JOIN users u ON e.userID = u.userID 
                             JOIN courses c ON e.courseID = c.courseID 
                             JOIN status s ON e.statusID = s.statusID");

        while ($e = $enrollments->fetch_assoc()) {
            echo "<tr>
                    <td>{$e['enrollmentID']}</td>
                    <td>{$e['firstName']} {$e['lastName']}</td>
                    <td>{$e['courseTitle']}</td>
                    <td>{$e['enrollmentDate']}</td>
                    <td>{$e['status']}</td>
                    <td>
                        <a href='?action=edit&id={$e['enrollmentID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$e['enrollmentID']}' onclick=\"return confirm('Delete this enrollment?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
