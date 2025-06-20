<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Check moderator (roleID=2)
$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$roleCheck = $conn->query("SELECT roleID FROM users WHERE userID = '$userID'");
$roleRow = $roleCheck->fetch_assoc();
$role = $roleRow['roleID'];

if ($role != 2) {
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
    <title>Moderator Dashboard</title>
</head>
<body>
    <h2>👮 Moderator Dashboard — Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    <p><a href="logout.php">Logout</a></p>
    <hr>

    <!-- Navigation Tabs -->
    <div>
            <button onclick="showTab('roles')">👤 Roles</button>
        <button onclick="showTab('users')">👤 Users</button>
                <button onclick="showTab('logins')">📘 Logins</button>
        <button onclick="showTab('instructors')">👤 Instructors</button>
        <button onclick="showTab('courses')">📝 Courses</button>
        <button onclick="showTab('status')">📝 Status</button>
                <button onclick="showTab('enrollments')">📘 Enrollments</button>
        <button onclick="showTab('assignments')">📝 Assignments</button>
        <button onclick="showTab('submissions')">📥 Submissions</button>
    </div>

    <hr>

    <!-- Sections -->
     <div id="roles" class="tab-section" style="display:none;">
        <h3>👤 Roles</h3>
        <table border="1" cellpadding="8">
            <tr><th>Role ID</th><th>Role</th></tr>
            <?php
            $roles = $conn->query("SELECT * FROM roles");
            while ($r = $roles->fetch_assoc()) {
                echo "<tr><td>{$r['roleID']}</td><td>{$r['role']}</td></tr>";
            }
            ?>
        </table>
    </div>
<div id="users" class="tab-section" style="display:none;">
        <h3>👤 Users</h3>
        <table border="1" cellpadding="8">
            <tr><th>User ID</th><th>FirstName</th><th>LastName</th><th>Email</th><th>Mobile</th><th>RoleID</th></tr>
            <?php
            $users = $conn->query("SELECT * FROM users");
            while ($u = $users->fetch_assoc()) {
                echo "<tr>
                        <td>{$u['userID']}</td>
                        <td>{$u['firstName']}</td>
                        <td>{$u['lastName']}</td>
                        <td>{$u['email']}</td>
                        <td>{$u['mobile']}</td>
                        <td>{$u['roleID']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>
<div id="logins" class="tab-section" style="display:none;">
        <h3>📘 Logins</h3>
        <table border="1" cellpadding="8">
            <tr><th>User ID</th><th>Username</th><th>passwordHash</th></tr>
            <?php
            $logins = $conn->query("SELECT * FROM logins");
            while ($l = $logins->fetch_assoc()) {
                echo "<tr>
                        <td>{$l['userID']}</td>
                        <td>{$l['username']}</td>
                        <td>{$l['passwordHash']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>
<div id="instructors" class="tab-section" style="display:none;">
        <h3>👤 Instructors</h3>
        <table border="1" cellpadding="8">
            <tr><th>InstructorsID</th><th>UserID</th><th>bio</th></tr>
            <?php
            $instructors = $conn->query("SELECT * FROM instructors");
            while ($i = $instructors->fetch_assoc()) {
                echo "<tr>
                        <td>{$i['instructorID']}</td>
                        <td>{$i['userID']}</td>
                        <td>{$i['bio']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>
<div id="courses" class="tab-section">
        <h3>📝 Course List</h3>
        <table border="1" cellpadding="8">
            <tr>
                <th>Course ID</th><th>Title</th><th>Description</th><th>Instructor ID</th>
            </tr>
            <?php
            $courses = $conn->query("SELECT * FROM courses");
            while ($c = $courses->fetch_assoc()) {
                echo "<tr>
                        <td>{$c['courseID']}</td>
                        <td>{$c['courseTitle']}</td>
                        <td>{$c['courseDescription']}</td>
                        <td>{$c['instructorID']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>

<div id="status" class="tab-section">
        <h3>📝 Status</h3>
        <table border="1" cellpadding="8">
            <tr>
                <th>StatusID</th><th>Status</th></tr>
            <?php
            $status = $conn->query("SELECT * FROM status");
            while ($s = $status->fetch_assoc()) {
                echo "<tr>
                        <td>{$s['statusID']}</td>
                        <td>{$s['status']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>
<div id="enrollments" class="tab-section" style="display:none;">
        <h3>📘 Enrollments</h3>
        <table border="1" cellpadding="8">
            <tr><th>Enrollments ID</th><th>User ID</th><th>Course ID</th><th>Enrollments Date</th><th>Status ID</th></tr>
            <?php
            $enrollments = $conn->query("SELECT * FROM enrollments");
            while ($e = $enrollments->fetch_assoc()) {
                echo "<tr>
                        <td>{$e['enrollmentID']}</td>
                        <td>{$e['userID']}</td>
                        <td>{$e['courseID']}</td>
                        <td>{$e['enrollmentDate']}</td>
                        <td>{$e['statusID']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>
<div id="assignments" class="tab-section" style="display:none;">
        <h3>📝 Assignments</h3>
        <table border="1" cellpadding="8">
            <tr>
                <th>Assignment ID</th><th>CourseID</th><th>Course</th><th>Title</th><th>Open</th><th>Due</th>
            </tr>
            <?php
            $assignments = $conn->query("
                SELECT a.*, c.courseTitle 
                FROM assignments a 
                JOIN courses c ON a.courseID = c.courseID
            ");
            while ($a = $assignments->fetch_assoc()) {
                echo "<tr>
                        <td>{$a['assignmentID']}</td>
                        <td>{$a['courseID']}</td>
                        <td>{$a['courseTitle']}</td>
                        <td>{$a['title']}</td>
                        <td>{$a['openDate']}</td>
                        <td>{$a['dueDate']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>
<div id="submissions" class="tab-section" style="display:none;">
        <h3>📥 Submissions</h3>
        <table border="1" cellpadding="8">
            <tr>
                <th>Submission ID</th><th>Assignment ID</th><th>User ID</th><th>Grade</th><th>Submitted At</th>
            </tr>
            <?php
            $subs = $conn->query("SELECT * FROM submissions");
            while ($s = $subs->fetch_assoc()) {
                echo "<tr>
                        <td>{$s['submissionID']}</td>
                        <td>{$s['assignmentID']}</td>
                        <td>{$s['userID']}</td>
                        <td>{$s['grade']}</td>
                        <td>{$s['submittedAt']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>


    <!-- JavaScript to toggle tabs -->
    <script>
        function showTab(tabId) {
            const sections = document.querySelectorAll('.tab-section');
            sections.forEach(sec => sec.style.display = 'none');
            document.getElementById(tabId).style.display = 'block';
        }
    </script>
</body>

</html>
