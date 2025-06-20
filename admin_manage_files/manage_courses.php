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
    $courseTitle = $conn->real_escape_string($_POST['courseTitle']);
    $courseDescription = $conn->real_escape_string($_POST['courseDescription']);
    $instructorID = intval($_POST['instructorID']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO courses (courseTitle, courseDescription, instructorID)
                      VALUES ('$courseTitle', '$courseDescription', $instructorID)");
    } elseif ($formAction === "edit") {
        $courseID = intval($_POST['courseID']);
        $conn->query("UPDATE courses SET 
                        courseTitle = '$courseTitle', 
                        courseDescription = '$courseDescription', 
                        instructorID = $instructorID 
                      WHERE courseID = $courseID");
    }

    header("Location: manage_courses.php");
    exit();
}

// Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_course'] = $conn->query("SELECT * FROM courses WHERE courseID = $id")->fetch_assoc();
    $conn->query("DELETE FROM courses WHERE courseID = $id");
    header("Location: manage_courses.php");
    exit();
}
// Load Edit
$editCourse = null;
if ($action === "edit" && $id) {
    $editCourse = $conn->query("SELECT * FROM courses WHERE courseID = $id")->fetch_assoc();
}

// Redo (Restore)
if ($action === "restore" && isset($_SESSION['deleted_course'])) {
    $c = $_SESSION['deleted_course'];
    $courseTitle = $conn->real_escape_string($c['courseTitle']);
    $courseDescription = $conn->real_escape_string($c['courseDescription']);
    $instructorID = intval($c['instructorID']);

    $conn->query("INSERT INTO courses (courseTitle, courseDescription, instructorID)
                  VALUES ('$courseTitle', '$courseDescription', $instructorID)");

    unset($_SESSION['deleted_course']);
    header("Location: manage_courses.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
</head>
<body>
    <h2>📚 Course Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_course'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Course "<?= htmlspecialchars($_SESSION['deleted_course']['courseTitle'] ?? 'Unknown') ?>" deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <h3><?= $editCourse ? "✏️ Edit Course" : "➕ Add Course" ?></h3>
    <form method="post">
        <input type="hidden" name="form_action" value="<?= $editCourse ? 'edit' : 'add' ?>">
        <?php if ($editCourse): ?>
            <input type="hidden" name="courseID" value="<?= $editCourse['courseID'] ?>">
        <?php endif; ?>
        Title: <input type="text" name="courseTitle" required value="<?= htmlspecialchars($editCourse['courseTitle'] ?? '') ?>"><br><br>
        Description:<br>
        <textarea name="courseDescription" rows="4" cols="50" required><?= htmlspecialchars($editCourse['courseDescription'] ?? '') ?></textarea><br><br>
        Instructor ID: <input type="number" name="instructorID" required value="<?= $editCourse['instructorID'] ?? '' ?>"><br><br>
        <input type="submit" value="<?= $editCourse ? 'Update' : 'Add' ?> Course">
        <?php if ($editCourse): ?>
            <a href="manage_courses.php" style="margin-left: 10px; color: red;">🔄 Cancel</a>
        <?php endif; ?>
    </form>

    <hr>
    <h3>📋 Courses List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>CourseID</th><th>Title</th><th>Description</th><th>Instructor ID</th><th>Actions</th>
        </tr>
        <?php
        $courses = $conn->query("SELECT * FROM courses");
        while ($course = $courses->fetch_assoc()) {
            echo "<tr>
                    <td>{$course['courseID']}</td>
                    <td>" . htmlspecialchars($course['courseTitle']) . "</td>
                    <td>" . htmlspecialchars($course['courseDescription']) . "</td>
                    <td>{$course['instructorID']}</td>
                    <td>
                        <a href='?action=edit&id={$course['courseID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$course['courseID']}' onclick=\"return confirm('Delete this course?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
