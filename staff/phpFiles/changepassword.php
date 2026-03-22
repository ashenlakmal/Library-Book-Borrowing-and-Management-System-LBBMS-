<?php
session_start();
require_once("../../other/php/connect.php");

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please log in first.'); window.location.href='../../other/login.html';</script>";
    exit();
}

$userid = $_SESSION['user'];

if (isset($_POST['submit'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Fetch current hashed password from DB
    $query = "SELECT password FROM staff WHERE `User ID` = '$userid'";
    $result = mysqli_query($connect, $query);
    $row = mysqli_fetch_assoc($result);

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        echo "<script>alert('Current password is incorrect.');</script>";
    } else {
        // Validate new password
        $counter = 0;

        if (strlen($newPassword) >= 8) {
            $counter += 1;

            for ($i = 0; $i < strlen($newPassword); $i++) {
                if ($newPassword[$i] >= 'A' && $newPassword[$i] <= 'Z') {
                    $counter += 1;
                    break;
                }
            }

            for ($i = 0; $i < strlen($newPassword); $i++) {
                if ($newPassword[$i] >= 'a' && $newPassword[$i] <= 'z') {
                    $counter += 1;
                    break;
                }
            }

            for ($i = 0; $i < strlen($newPassword); $i++) {
                if (
                    strpos('!@#$%^&*()?><:;[]{}-+=~`/.,|', $newPassword[$i]) !== false
                ) {
                    $counter += 1;
                    break;
                }
            }
        }

        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 8 && $counter === 4) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = "UPDATE staff SET password = '$hashed' WHERE `User ID` = '$userid'";
                if (mysqli_query($connect, $update)) {
                    echo "<script>alert('Password changed successfully.'); window.location.href='staffprofile.php';</script>";
                } else {
                    echo "<script>alert('Something went wrong. Please try again.');</script>";
                }
            } else {
                echo "<script>alert('Password must include uppercase, lowercase, special character and be at least 8 characters long.');</script>";
            }
        } else {
            echo "<script>alert('New passwords do not match.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Change Password - Staff</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #d9ecff;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 500px;
      margin-top: 60px;
      background-color: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #004085;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Change Password</h2>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Current Password</label>
      <input type="password" name="currentPassword" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">New Password</label>
      <input type="password" name="newPassword" class="form-control" required>
      <small class="form-text text-muted">Must contain uppercase, lowercase, special character and be at least 8 characters long.</small>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirm New Password</label>
      <input type="password" name="confirmPassword" class="form-control" required>
    </div>

    <button type="submit" name="submit" class="btn btn-primary w-100">Change Password</button>
  </form>
</div>

</body>
</html>
