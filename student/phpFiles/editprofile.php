<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please log in first.'); window.location.href='../../other/login.html';</script>";
    exit();
}

require_once("../../other/php/connect.php");
$enrollment = $_SESSION['user'];

// Fetch current student data
$query = "
    SELECT s.name, s.email, s.faculty, s.department, s.age, s.address, s.gender, s.profilephoto,
           GROUP_CONCAT(p.phone ORDER BY p.phone SEPARATOR ',') AS phones
    FROM student s
    LEFT JOIN student_phone p ON s.Enrollment = p.Enrollment
    WHERE s.Enrollment = '$enrollment'
    GROUP BY s.Enrollment
";
$result = mysqli_query($connect, $query);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo "<script>alert('Student not found.'); window.location.href='../../other/login.html';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // If user clicked "Remove Profile Photo" button
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        // Delete photo file if exists and safe
        if (!empty($student['profilephoto']) && strpos($student['profilephoto'], '../../uploads/') === 0 && file_exists($student['profilephoto'])) {
            unlink($student['profilephoto']);
        }
        // Clear photo path in DB
        mysqli_query($connect, "UPDATE student SET profilephoto = '' WHERE Enrollment = '$enrollment'");
        
        // Refresh data for display
        header("Location: editprofile.php");
        exit();
    }

    // Otherwise, process normal profile update
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $faculty = mysqli_real_escape_string($connect, $_POST['faculty']);
    $department = mysqli_real_escape_string($connect, $_POST['department']);
    $age = (int)$_POST['age'];
    $address = mysqli_real_escape_string($connect, $_POST['address']);
    $gender = mysqli_real_escape_string($connect, $_POST['gender']);
    $phones = array_filter([$_POST['phone1'], $_POST['phone2'], $_POST['phone3']]);

    $profilePhotoPath = $student['profilephoto'];

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

    $updateStudent = "
        UPDATE student SET 
            name = '$name',
            email = '$email',
            faculty = '$faculty',
            department = '$department',
            age = $age,
            address = '$address',
            gender = '$gender',
            profilephoto = '$profilePhotoPath'
        WHERE Enrollment = '$enrollment'
    ";
    mysqli_query($connect, $updateStudent);

    mysqli_query($connect, "DELETE FROM student_phone WHERE Enrollment = '$enrollment'");
    foreach ($phones as $phone) {
        $phone = mysqli_real_escape_string($connect, $phone);
        if (!empty($phone)) {
            mysqli_query($connect, "INSERT INTO student_phone (Enrollment, phone) VALUES ('$enrollment', '$phone')");
        }
    }

    echo "<script>alert('Profile updated successfully!'); window.location.href='../index.php';</script>";
    exit();
}

// Reload student data after possible changes
$query = "
    SELECT s.name, s.email, s.faculty, s.department, s.age, s.address, s.gender, s.profilephoto,
           GROUP_CONCAT(p.phone ORDER BY p.phone SEPARATOR ',') AS phones
    FROM student s
    LEFT JOIN student_phone p ON s.Enrollment = p.Enrollment
    WHERE s.Enrollment = '$enrollment'
    GROUP BY s.Enrollment
";
$result = mysqli_query($connect, $query);
$student = mysqli_fetch_assoc($result);

$phoneArray = explode(',', $student['phones']);
$phone1 = $phoneArray[0] ?? '';
$phone2 = $phoneArray[1] ?? '';
$phone3 = $phoneArray[2] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Profile</title>
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
    <!-- Other fields here... -->
    <div class="mb-3">
      <label class="form-label">Full Name (with initials)</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Faculty</label>
      <input type="text" name="faculty" class="form-control" value="<?= htmlspecialchars($student['faculty']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Department</label>
      <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($student['department']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Age</label>
      <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($student['age']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($student['address']) ?>" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select name="gender" class="form-select" required>
        <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
        <option value="Other" <?= $student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Profile Photo</label>
      <?php if (!empty($student['profilephoto'])): ?>
        <div class="profile-photo-container">
          <img src="<?= htmlspecialchars($student['profilephoto']) ?>" alt="Current Photo" />
          <!-- This button triggers removal -->
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
      <a href="studentprofile.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Update Profile</button>
    </div>
  </form>
</div>

</body>
</html>
