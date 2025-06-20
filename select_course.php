<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Get enrolled course IDs for this student
$enrolled = [];
$enrolledResult = $conn->query("SELECT courseID FROM enrollments WHERE userID = '$userID'");
while ($row = $enrolledResult->fetch_assoc()) {
    $enrolled[] = $row['courseID'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['courses'])) {
    $selectedCourses = $_POST['courses'];

    foreach ($selectedCourses as $courseID) {
        if (!in_array($courseID, $enrolled)) {
            $conn->query("INSERT INTO enrollments (userID, courseID, enrollmentDate, statusID)
                          VALUES ('$userID', '$courseID', CURDATE(), 4)");
        }
    }

    // Refresh enrolled list after submission
    $enrolled = [];
    $enrolledResult = $conn->query("SELECT courseID FROM enrollments WHERE userID = '$userID'");
    while ($row = $enrolledResult->fetch_assoc()) {
        $enrolled[] = $row['courseID'];
    }

    echo "<p style='color:green;'>✅ Courses enrolled successfully!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Courses</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?> — Select Your Courses</h2>

    <form method="post">
        <label>Select Courses:</label><br><br>
        <?php
        $result = $conn->query("SELECT courseID, courseTitle FROM courses");

        while ($row = $result->fetch_assoc()) {
            $courseID = $row['courseID'];
            $checked = in_array($courseID, $enrolled) ? "checked" : "";
            echo "<input type='checkbox' name='courses[]' value='$courseID' $checked> {$row['courseTitle']}<br>";
        }
        ?>
        <br>
        <input type="submit" value="Update Enrollments">
    </form>

    <br>
    <a href="dashboard.php">🔙 Back to Dashboard</a> | <a href="logout.php">Logout</a>
</body>
</html>
