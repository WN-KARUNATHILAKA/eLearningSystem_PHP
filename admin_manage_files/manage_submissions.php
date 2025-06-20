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
    $assignmentID = intval($_POST['assignmentID']);
    $userID = intval($_POST['userID']);
    $submittedAt = $conn->real_escape_string($_POST['submittedAt']);
    $grade = $conn->real_escape_string($_POST['grade']);
    $fileURL = $conn->real_escape_string($_POST['fileURL']);
    $formAction = $_POST['form_action'];

    if ($formAction === "add") {
        $conn->query("INSERT INTO submissions (assignmentID, userID, submittedAt, grade, fileURL)
                      VALUES ($assignmentID, $userID, '$submittedAt', '$grade', '$fileURL')");
    } elseif ($formAction === "edit") {
        $submissionID = intval($_POST['submissionID']);
        $conn->query("UPDATE submissions SET 
                        assignmentID = $assignmentID,
                        userID = $userID,
                        submittedAt = '$submittedAt',
                        grade = '$grade',
                        fileURL = '$fileURL'
                      WHERE submissionID = $submissionID");
    }

    header("Location: manage_submissions.php");
    exit();
}
// Load Edit
$editSubmission = null;
if ($action === "edit" && $id) {
    $editSubmission = $conn->query("SELECT * FROM submissions WHERE submissionID = $id")->fetch_assoc();
}
// Delete
if ($action === "delete" && $id) {
    $_SESSION['deleted_submission'] = $conn->query("SELECT * FROM submissions WHERE submissionID = $id")->fetch_assoc();
    $conn->query("DELETE FROM submissions WHERE submissionID = $id");
    header("Location: manage_submissions.php");
    exit();
}

// Redo (Restore)
if ($action === "restore" && isset($_SESSION['deleted_submission'])) {
    $s = $_SESSION['deleted_submission'];
    $conn->query("INSERT INTO submissions (assignmentID, userID, submittedAt, grade, fileURL)
                  VALUES ({$s['assignmentID']}, {$s['userID']}, '{$s['submittedAt']}', '{$s['grade']}', '{$s['fileURL']}')");
    unset($_SESSION['deleted_submission']);
    header("Location: manage_submissions.php");
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Submissions</title>
</head>
<body>
    <h2>📥 Submissions Management</h2>
    <p><a href="../admin_dashboard.php">← Back to Dashboard</a></p>

    <?php if (isset($_SESSION['deleted_submission'])): ?>
        <p style="background: #fff3cd; padding: 10px;">
            🗑️ Submission for Assignment <?= $_SESSION['deleted_submission']['assignmentID'] ?> deleted.
            <a href="?action=restore"
               style="background-color: orange; padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px;">
               ↩️ Undo Delete</a>
        </p>
    <?php endif; ?>

    <h3><?= $editSubmission ? "✏️ Edit Submission" : "➕ Add Submission" ?></h3>
    <form method="post">
        <input type="hidden" name="form_action" value="<?= $editSubmission ? 'edit' : 'add' ?>">
        <?php if ($editSubmission): ?>
            <input type="hidden" name="submissionID" value="<?= $editSubmission['submissionID'] ?>">
        <?php endif; ?>
        Assignment ID: <input type="number" name="assignmentID" required value="<?= $editSubmission['assignmentID'] ?? '' ?>"><br><br>
        User ID: <input type="number" name="userID" required value="<?= $editSubmission['userID'] ?? '' ?>"><br><br>
        Submitted At: <input type="datetime-local" name="submittedAt" required
            value="<?= isset($editSubmission['submittedAt']) ? date('Y-m-d\TH:i', strtotime($editSubmission['submittedAt'])) : date('Y-m-d\TH:i') ?>"><br><br>
        Grade: <input type="text" name="grade" value="<?= htmlspecialchars($editSubmission['grade'] ?? '') ?>"><br><br>
        File URL: <input type="text" name="fileURL" value="<?= htmlspecialchars($editSubmission['fileURL'] ?? '') ?>"><br><br>
        <input type="submit" value="<?= $editSubmission ? 'Update' : 'Add' ?> Submission">
        <?php if ($editSubmission): ?>
            <a href="manage_submissions.php" style="margin-left: 10px; color: red;">🔄 Cancel</a>
        <?php endif; ?>
    </form>

    <hr>
    <h3>📋 Submissions List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th><th>Assignment</th><th>User</th><th>Date</th><th>Grade</th><th>File</th><th>Actions</th>
        </tr>
        <?php
        $subs = $conn->query("SELECT * FROM submissions");
        while ($s = $subs->fetch_assoc()) {
            echo "<tr>
                    <td>{$s['submissionID']}</td>
                    <td>{$s['assignmentID']}</td>
                    <td>{$s['userID']}</td>
                    <td>{$s['submittedAt']}</td>
                    <td>{$s['grade']}</td>
                    <td>" . htmlspecialchars($s['fileURL']) . "</td>
                    <td>
                        <a href='?action=edit&id={$s['submissionID']}'>✏️ Edit</a> |
                        <a href='?action=delete&id={$s['submissionID']}' onclick=\"return confirm('Delete this submission?')\">🗑️ Delete</a>
                    </td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
