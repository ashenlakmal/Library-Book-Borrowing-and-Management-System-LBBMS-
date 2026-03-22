<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please log in first.'); window.location.href='../../other/login.html';</script>";
    exit();
}

require_once("../../other/php/connect.php");
$userid = $_SESSION['user'];

// Fetch current staff data with phones
$query = "
    SELECT s.name, s.email, s.address, s.age, s.profilephoto,
           GROUP_CONCAT(p.phone ORDER BY p.phone SEPARATOR ',') AS phones
    FROM staff s
    LEFT JOIN staff_phone p ON s.`User ID` = p.`User ID`
    WHERE s.`User ID` = '$userid'
    GROUP BY s.`User ID`
";
$result = mysqli_query($connect, $query);
$staff = mysqli_fetch_assoc($result);

if (!$staff) {
    echo "<script>alert('Staff member not found.'); window.location.href='../../other/login.html';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Remove profile photo if requested
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        if (!empty($staff['profilephoto']) && strpos($staff['profilephoto'], '../../uploads/') === 0 && file_exists($staff['profilephoto'])) {
            unlink($staff['profilephoto']);
        }
        mysqli_query($connect, "UPDATE staff SET profilephoto = '' WHERE `User ID` = '$userid'");
        header("Location: editprofile.php");
        exit();
    }

    // Process profile update
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $address = mysqli_real_escape_string($connect, $_POST['address']);
    $age = (int)$_POST['age'];
    $phones = array_filter([$_POST['phone1'], $_POST['phone2'], $_POST['phone3']]);

    $profilePhotoPath = $staff['profilephoto'];

    if (isset($_FILES['profilephoto']) && $_FILES['profilephoto']['error'] == 0) {
        $targetDir = "../../uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $filename = basename($_FILES['profilephoto']['name']);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES['profilephoto']['tmp_name'], $targetFile)) {
            $profilePhotoPath = "../../uploads/" . $filename;
        } else {
            echo "<script>alert('Error uploading profile photo.'); window.location.href='editprofile.php';</script>";
            exit();
        }
    }

    $updateStaff = "
        UPDATE staff SET 
            name = '$name',
            email = '$email',
            address = '$address',
            age = $age,
            profilephoto = '$profilePhotoPath'
        WHERE `User ID` = '$userid'
    ";
    mysqli_query($connect, $updateStaff);

    // Update phones
    mysqli_query($connect, "DELETE FROM staff_phone WHERE `User ID` = '$userid'");
    foreach ($phones as $phone) {
        $phone = mysqli_real_escape_string($connect, $phone);
        if (!empty($phone)) {
            mysqli_query($connect, "INSERT INTO staff_phone (`User ID`, phone) VALUES ('$userid', '$phone')");
        }
    }

    echo "<script>alert('Profile updated successfully!'); window.location.href='../index.php';</script>";
    exit();
}

// Reload staff data for display after updates
$query = "
    SELECT s.name, s.email, s.address, s.age, s.profilephoto,
           GROUP_CONCAT(p.phone ORDER BY p.phone SEPARATOR ',') AS phones
    FROM staff s
    LEFT JOIN staff_phone p ON s.`User ID` = p.`User ID`
    WHERE s.`User ID` = '$userid'
    GROUP BY s.`User ID`
";
$result = mysqli_query($connect, $query);
$staff = mysqli_fetch_assoc($result);

$phoneArray = explode(',', $staff['phones']);
$phone1 = $phoneArray[0] ?? '';
$phone2 = $phoneArray[1] ?? '';
$phone3 = $phoneArray[2] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Profile - Staff</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f1f6ff;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 700px;
      margin-top: 60px;
      background-color: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #0d6efd;
    }
    .profile-photo-container {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 15px;
    }
    .profile-photo-container img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #0d6efd;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Edit Profile</h2>
  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Full Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($staff['address']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Age</label>
      <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($staff['age']) ?>" required />
    </div>

    <div class="mb-3">
      <label class="form-label">Profile Photo</label>
      <?php if (!empty($staff['profilephoto'])): ?>
        <div class="profile-photo-container">
          <img src="<?= htmlspecialchars($staff['profilephoto']) ?>" alt="Current Photo" />
          <button type="submit" name="remove_photo" value="1" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove your profile photo?');">
            Remove Profile Photo
          </button>
        </div>
      <?php else: ?>
        <div class="form-text mb-2">No profile photo uploaded yet.</div>
      <?php endif; ?>
      <input type="file" name="profilephoto" class="form-control" accept="image/*" />
      <div class="form-text">Upload a new photo to replace the current one.</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Phone 1 (Required)</label>
      <input type="tel" name="phone1" class="form-control" value="<?= htmlspecialchars($phone1) ?>" required />
    </div>
    <div class="row">
      <div class="col">
        <label class="form-label">Phone 2 (Optional)</label>
        <input type="tel" name="phone2" class="form-control" value="<?= htmlspecialchars($phone2) ?>" />
      </div>
      <div class="col">
        <label class="form-label">Phone 3 (Optional)</label>
        <input type="tel" name="phone3" class="form-control" value="<?= htmlspecialchars($phone3) ?>" />
      </div>
    </div>

    <div class="mt-4 d-flex justify-content-between">
      <a href="staffprofile.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Update Profile</button>
    </div>
  </form>
</div>

</body>
</html>
