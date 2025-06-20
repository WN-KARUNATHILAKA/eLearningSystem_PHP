<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials
    $sql = "SELECT l.userID, l.username, u.roleID
            FROM logins l
            JOIN users u ON l.userID = u.userID
            WHERE l.username = '$username' AND l.passwordHash = '$password'";

    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['userID'] = $row['userID'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['roleID'] = $row['roleID'];

        // Redirect by role
        switch ($row['roleID']) {
            case 1:
                header("Location: admin_dashboard.php");
                break;
            case 2:
                header("Location: moderator_dashboard.php");
                break;
            case 3:
                header("Location: instructor_dashboard.php");
                break;
            case 4:
                header("Location: dashboard.php");
                break;
            default:
                echo "Unknown role. Access denied.";
        }

        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="post">
        Username: <input type="text" name="username" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <input type="submit" value="Login">
    </form>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
