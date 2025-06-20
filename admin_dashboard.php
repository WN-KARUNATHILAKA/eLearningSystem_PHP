<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Check admin (roleID = 1)
$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$roleCheck = $conn->query("SELECT roleID FROM users WHERE userID = '$userID'");
$roleRow = $roleCheck->fetch_assoc();
$role = $roleRow['roleID'];

if ($role != 1) {
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>👨‍💼 Admin Dashboard — Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <p><a href="logout.php">Logout</a></p>
    <hr>
    <h3>📂 Manage Tables</h3>
    <ul>
        <li><a href="admin_manage_files/manage_roles.php">👤 Roles</a></li>
        <li><a href="admin_manage_files/manage_users.php">👤 Users</a></li>
        <li><a href="admin_manage_files/manage_logins.php">📘 Logins</a></li>
        <li><a href="admin_manage_files/manage_instructors.php">👤 Instructors</a></li>
        <li><a href="admin_manage_files/manage_courses.php">🧾 Courses</a></li>
        <li><a href="admin_manage_files/manage_status.php">🧾 Status</a></li>
        <li><a href="admin_manage_files/manage_enrollments.php">📘 Enrollments</a></li>
        <li><a href="admin_manage_files/manage_assignments.php">📝 Assignments</a></li>
        <li><a href="admin_manage_files/manage_submissions.php">📥 Submissions</a></li>
    </ul>
</body>
</html>
