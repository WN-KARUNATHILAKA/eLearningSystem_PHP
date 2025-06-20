<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Confirm role is instructor (roleID = 3)
$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$roleCheck = $conn->query("SELECT roleID FROM users WHERE userID = '$userID'");
$roleRow = $roleCheck->fetch_assoc();
$role = $roleRow['roleID'];

if ($role != 3) {
    echo "
    <html><head><title>Access Denied</title></head><body style='text-align:center; margin-top:100px; font-family:sans-serif;'>
        <h2>🚫 Access Denied</h2>
        <p>You do not have permission to access this page.</p>
        <a href='login.php' style='
            display:inline-block;
            padding:10px 20px;
            background-color:#007BFF;
            color:white;
            text-decoration:none;
            border-radius:5px;
            margin-top:20px;
        '>🔙 Back to Login</a>
    </body></html>";
    exit();
}

// Get instructorID from instructors table
$getInstructor = $conn->query("SELECT instructorID FROM instructors WHERE userID = '$userID'");
if ($getInstructor->num_rows !== 1) {
    echo "<h3>Instructor record not found for this user.</h3>";
    exit();
}
$instructorID = $getInstructor->fetch_assoc()['instructorID'];

// assignment submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $courseID = $_POST['courseID'];
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $openDate = $_POST['openDate'];
    $dueDate = $_POST['dueDate'];

    $conn->query("INSERT INTO assignments (courseID, title, description, openDate, dueDate)
                  VALUES ('$courseID', '$title', '$description', '$openDate', '$dueDate')");
    echo "<p style='color:green;'>✅ Assignment created successfully!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instructor Dashboard</title>
</head>
<body>
    <h2>👨‍🏫 Instructor Dashboard — Welcome, <?= htmlspecialchars($username) ?></h2>

    <p>
        <a href="profile.php" style="margin-right:15px;">👤 Edit Profile</a> | 
        <a href="logout.php">Logout</a>
    </p>

    <hr>
    <h3>📝 Create Assignment</h3>
    <form method="post">
        Course:
        <select name="courseID" required>
            <option value="">-- Select Course --</option>
            <?php
            $courses = $conn->query("SELECT * FROM courses WHERE instructorID = '$instructorID'");
            while ($c = $courses->fetch_assoc()) {
                echo "<option value='{$c['courseID']}'>" . htmlspecialchars($c['courseTitle']) . "</option>";
            }
            ?>
        </select><br><br>
        Title: <input type="text" name="title" required><br><br>
        Description: <textarea name="description"></textarea><br><br>
        Open Date: <input type="date" name="openDate" required><br><br>
        Due Date: <input type="date" name="dueDate" required><br><br>
        <input type="submit" value="Create Assignment">
    </form>

    <hr>
    <h3>📥 Student Submissions</h3>
    <?php
    $assignments = $conn->query("
        SELECT a.assignmentID, a.title, c.courseTitle
        FROM assignments a
        JOIN courses c ON a.courseID = c.courseID
        WHERE c.instructorID = '$instructorID'
    ");

    while ($a = $assignments->fetch_assoc()) {
        echo "<h4>" . htmlspecialchars($a['title']) . " (" . htmlspecialchars($a['courseTitle']) . ")</h4>";

        $assignmentID = $a['assignmentID'];
        $subs = $conn->query("
            SELECT s.*, u.firstName, u.lastName
            FROM submissions s
            JOIN users u ON s.userID = u.userID
            WHERE s.assignmentID = '$assignmentID'
        ");

        if ($subs->num_rows > 0) {
            echo "<table border='1' cellpadding='6'>
                    <tr>
                        <th>Student</th><th>Submitted At</th><th>Grade</th><th>File</th>
                    </tr>";
            while ($s = $subs->fetch_assoc()) {
                $fileLink = $s['fileURL'] ? "<a href='" . htmlspecialchars($s['fileURL']) . "' target='_blank'>📎 View</a>" : "No File";
                echo "<tr>
                        <td>" . htmlspecialchars($s['firstName']) . " " . htmlspecialchars($s['lastName']) . "</td>
                        <td>" . htmlspecialchars($s['submittedAt']) . "</td>
                        <td>" . htmlspecialchars($s['grade']) . "</td>
                        <td>$fileLink</td>
                      </tr>";
            }
            echo "</table><br>";
        } else {
            echo "<p>No submissions yet.</p>";
        }
    }
    ?>
</body>
</html>
