<?php
session_start();

// Logout handler
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>window.location.href='../../index.html';</script>";
    exit();
}

// Check login session
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please log in first.'); window.location.href='../../other/login.html';</script>";
    exit();
}

$userid = $_SESSION['user'];
require_once("../../other/php/connect.php");

// Fetch staff data with phones
$query = "
    SELECT s.`User ID`, s.name, s.email, s.address, s.age, s.profilephoto, p.phone
    FROM staff s
    LEFT JOIN staff_phone p ON s.`User ID` = p.`User ID`
    WHERE s.`User ID` = '$userid'
";
$result = mysqli_query($connect, $query);

$phones = [];
$staff = null;

while ($row = mysqli_fetch_assoc($result)) {
    if (!$staff) {
        $staff = $row;
    }
    if (!empty($row['phone']) && !in_array($row['phone'], $phones)) {
        $phones[] = $row['phone'];
    }
}

if (!$staff) {
    echo "<script>alert('Staff member not found.'); window.location.href='../../other/login.html';</script>";
    exit();
}

$initialLetter = strtoupper(substr(trim($staff['name']), 0, 1)) ?: "S";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Staff Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #d2e5fa;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 700px;
      margin-top: 60px;
      background-color: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #0056b3;
    }
    .btn-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
      margin-top: 30px;
    }
    .btn {
      flex: 1 1 45%;
      min-width: 150px;
    }
    .profile-info {
      font-size: 1.1rem;
    }
    .profile-info strong {
      width: 140px;
      display: inline-block;
    }
    .profile-photo {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #0056b3;
    }
    .initial-circle {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background-color: #0056b3;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 64px;
      font-weight: bold;
      margin: 0 auto 20px auto;
      border: 3px solid #0056b3;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Staff Profile</h2>

  <?php if (!empty($staff['profilephoto'])): ?>
    <div style="text-align: center; margin-bottom: 20px;">
      <img src="<?= htmlspecialchars($staff['profilephoto']) ?>" alt="Profile Photo" class="profile-photo">
    </div>
  <?php else: ?>
    <div class="initial-circle"><?= htmlspecialchars($initialLetter) ?></div>
  <?php endif; ?>

  <div class="profile-info">
    <p><strong>User ID:</strong> <?= htmlspecialchars($staff['User ID']) ?></p>
    <p><strong>Full Name:</strong> <?= htmlspecialchars($staff['name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
    <p><strong>Phone:</strong> <?= count($phones) > 0 ? htmlspecialchars(implode(', ', $phones)) : 'Not Available' ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($staff['address']) ?></p>
    <p><strong>Age:</strong> <?= htmlspecialchars($staff['age']) ?></p>
  </div>

  <div class="btn-group mt-4">
    <a href="staffdashboard.php" class="btn btn-success">Go to Dashboard</a>
    <a href="editprofile.php" class="btn btn-primary">Edit Profile</a>
    <a href="changepassword.php" class="btn btn-warning">Change Password</a>
    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
  </div>
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">Are you sure you want to log out?</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="logout" class="btn btn-danger">Yes, Logout</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
