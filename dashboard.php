<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Check student (roleID = 4)
$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$roleCheck = $conn->query("SELECT roleID FROM users WHERE userID = '$userID'");
$roleRow = $roleCheck->fetch_assoc();
$role = $roleRow['roleID'];

if ($role != 4) {
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

// Get all enrolled courses
$enrolled = [];
$enrolledResult = $conn->query("SELECT courseID FROM enrollments WHERE userID = '$userID'");
while ($row = $enrolledResult->fetch_assoc()) {
    $enrolled[] = $row['courseID'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>
    <h2>🎓 Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <p>
      <a href="select_course.php">📘 Select Courses</a> | 
      <a href="profile.php">👤 Profile</a> | 
      <a href="logout.php">Logout</a>
    </p>

    <hr>

    <h3>📚 Your Assignments</h3>

    <?php
    if (count($enrolled) > 0) {
        
        $enrolledList = implode(",", array_map('intval', $enrolled));
        $assignmentQuery = "
            SELECT a.assignmentID, a.title, a.description, a.openDate, a.dueDate, c.courseTitle
            FROM assignments a
            JOIN courses c ON a.courseID = c.courseID
            WHERE a.courseID IN ($enrolledList)
            ORDER BY a.dueDate ASC
        ";

        $assignments = $conn->query($assignmentQuery);

        if ($assignments && $assignments->num_rows > 0) {
            echo "<table border='1' cellpadding='8'>
                    <tr>
                        <th>Course</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Open Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>";

            while ($row = $assignments->fetch_assoc()) {
                $assignmentID = (int)$row['assignmentID'];

                // Check if the student has submitted this assignment
                $submissionCheck = $conn->query("
                    SELECT * FROM submissions 
                    WHERE assignmentID = $assignmentID AND userID = $userID
                ");

                $status = ($submissionCheck && $submissionCheck->num_rows > 0) ? "✅ Done" : "❌ Not Submitted";

                echo "<tr>
                        <td>" . htmlspecialchars($row['courseTitle']) . "</td>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td>" . htmlspecialchars($row['description']) . "</td>
                        <td>" . htmlspecialchars($row['openDate']) . "</td>
                        <td>" . htmlspecialchars($row['dueDate']) . "</td>
                        <td>
    $status
    " . (($status === '❌ Not Submitted') ? 
        "<br><a href='submit_assignment.php?assignmentID=$assignmentID' target='_blank'>
            📤 Submit
        </a>" : "") . "
</td>

                      </tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No assignments found.</p>";
        }
    } else {
        echo "<p>You are not enrolled in any courses.</p>";
    }
    ?>
</body>
</html>
