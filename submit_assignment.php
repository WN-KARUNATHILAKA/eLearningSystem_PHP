<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID']) || $_SESSION['roleID'] != 4) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$assignmentID = intval($_GET['assignmentID'] ?? 0);

if ($assignmentID === 0) {
    echo "Invalid assignment.";
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"];
    $uploadDir = "uploads/";
    $filename = basename($file["name"]);
    $targetFile = $uploadDir . time() . "_" . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        $submittedAt = date('Y-m-d H:i:s');
        $fileURL = $conn->real_escape_string($targetFile);

        $conn->query("INSERT INTO submissions (assignmentID, userID, submittedAt, fileURL)
                      VALUES ($assignmentID, $userID, '$submittedAt', '$fileURL')");

        echo "<p style='color:green;'>✅ Submission successful!</p>";
    } else {
        echo "<p style='color:red;'>❌ File upload failed.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Assignment</title>
</head>
<body>
    <h2>📤 Submit Assignment</h2>
    <p>Logged in as: <?= htmlspecialchars($username) ?></p>
    <form method="post" enctype="multipart/form-data">
        <label>Select file to upload (PDF, DOC, ZIP etc.):</label><br><br>
        <input type="file" name="file" required><br><br>
        <input type="submit" value="Submit Assignment">
    </form>
</body>
</html>
