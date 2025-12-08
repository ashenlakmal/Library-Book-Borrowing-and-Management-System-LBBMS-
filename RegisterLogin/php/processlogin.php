<?php
require_once("connect.php");
session_start();

$enrollment = $_POST['enrollment'];
$password = $_POST['password'];

// Check Student table
$query1 = "SELECT * FROM student WHERE Enrollment='$enrollment'";
$result1 = mysqli_query($connect, $query1);

if ($row1 = mysqli_fetch_assoc($result1)) {
    if (password_verify($password, $row1['password'])) {
        $_SESSION['user'] = $enrollment;
        $_SESSION['role'] = 'student'; 
        echo "<script>alert('Logged in as Student'); window.location.href='../../student/index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Incorrect password for student.'); window.location.href='../login.html';</script>";
        exit();
    }
}

// Check Staff table
$query2 = "SELECT * FROM staff WHERE `User ID`='$enrollment'";
$result2 = mysqli_query($connect, $query2);

if ($row2 = mysqli_fetch_assoc($result2)) {
    $stored = $row2['password'];

    if (password_verify($password, $stored) || $password === $stored) {
        $_SESSION['user'] = $enrollment;
        $_SESSION['role'] = 'staff'; 

        // Optional: rehash if plain password
        if (!password_verify($password, $stored)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = "UPDATE staff SET password='$newHash' WHERE `User ID`='$enrollment'";
            mysqli_query($connect, $update);
        }

        echo "<script>alert('Logged in as Staff'); window.location.href='../../staff/index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Incorrect password for Staff.'); window.location.href='../login.html';</script>";
        exit();
    }
}

echo "<script>alert('User ID not found.'); window.location.href='../login.html';</script>";
mysqli_close($connect);
?>
