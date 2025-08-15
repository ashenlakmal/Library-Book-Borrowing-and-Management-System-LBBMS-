<?php
session_start();
require_once("../other/php/connect.php");

$profilePhoto = "";
$userInitial = "S";

if (isset($_SESSION['user'])) {
    $staffId = $_SESSION['user'];
    $query = "SELECT name, profilephoto FROM staff WHERE `User ID` = '$staffId'";
    $result = mysqli_query($connect, $query);
    $staff = mysqli_fetch_assoc($result);

    if ($staff) {
        $name = $staff['name'];
        $photo = $staff['profilephoto'];

        if (!empty($photo)) {
            $cleanPath = str_replace("../../", "", $photo);
            $profilePhoto = "../" . $cleanPath;
        } else {
            $nameParts = explode(" ", $name);
            $firstName = $nameParts[0] ?? "";
            $userInitial = strtoupper(substr($firstName, 0, 1)) ?: "S";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us - Library System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #d2e5fa;
    }
    .navbar {
      background-color: #007BFF;
    }
    .navbar-brand {
      font-weight: bold;
      color: white;
    }
    .nav-link {
      color: white !important;
    }
    .nav-link:hover {
      text-decoration: underline;
    }
    .logo-img {
      height: 60px;
      margin-top: -10px;
      margin-bottom: -10px;
      border-radius: 50%;
    }
    .user-photo-link {
      display: inline-block;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      overflow: hidden;
      background-color: white;
    }
    .user-photo-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .user-icon {
      background-color: white;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      text-align: center;
      line-height: 40px;
      font-size: 18px;
      font-weight: bold;
      color: #007BFF;
      text-decoration: none;
      display: inline-block;
    }
    .banner {
      background-image: url('../images/Picture1-scaled-e1607252074978.jpg');
      background-size: cover;
      background-position: center;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .banner-overlay {
      background-color: rgba(0, 0, 0, 0.5);
      padding: 30px 50px;
      border-radius: 10px;
    }
    .banner h1 {
      color: white;
      font-size: 2.5rem;
      margin: 0;
      text-align: center;
    }
    .footer {
      background-color: #0056b3;
      color: white;
      padding: 30px 20px;
      text-align: center;
    }
    .footer h5 {
      margin-bottom: 15px;
      font-weight: bold;
    }
    .footer a {
      color: #ffffff;
      text-decoration: none;
      display: block;
      margin: 5px 0;
    }
    .footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="#">
      <img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo" class="logo-img">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon bg-light"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="managebook.php">Manage Books</a></li>
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link active" href="contactus.php"><u>Contact Us</u></a></li>
      </ul>
      <div class="d-flex align-items-center">
        <?php if (!empty($profilePhoto)) : ?>
          <a href="phpFiles/staffprofile.php" class="user-photo-link" data-bs-toggle="tooltip" title="Go to Profile">
            <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="Profile Photo" class="user-photo-img">
          </a>
        <?php else: ?>
          <a href="phpFiles/staffprofile.php" class="user-icon" data-bs-toggle="tooltip" title="Go to Profile">
            <?= htmlspecialchars($userInitial) ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Banner -->
<section class="banner">
  <div class="banner-overlay">
    <h1>Library Book Borrowing & Management System</h1>
  </div>
</section><br>

<!-- Contact Section -->
<section class="container my-5">
  <h2 class="text-center mb-4 text-primary fw-bold">Contact Us</h2>
  <div class="row g-4">
    <!-- Map -->
    <div class="col-md-6">
      <div class="border rounded shadow-sm p-2 h-100">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.2213391065466!2d81.07679557399824!3d6.983186093017676!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae4618a1a9fec37%3A0x1dd900702229654b!2sUva%20Wellassa%20University%20of%20Sri%20Lanka!5e0!3m2!1sen!2slk!4v1752090852876!5m2!1sen!2slk" 
          width="100%" 
          height="100%" 
          style="border:0; min-height: 320px;" 
          allowfullscreen="" 
          loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="col-md-6">
      <div class="bg-white p-4 rounded shadow-sm border h-100">
        <div class="contact-item d-flex align-items-center">
          <img src="https://img.icons8.com/ios-glyphs/30/007BFF/new-post.png" class="contact-icon" alt="Email icon"/>
          <strong>Email:</strong>&nbsp;
          <a href="mailto:library@uwu.ac.lk" class="text-decoration-none text-dark">library@uwu.ac.lk</a><br><br>
        </div>
        <div class="contact-item d-flex align-items-center">
          <img src="https://img.icons8.com/ios-glyphs/30/007BFF/phone.png" class="contact-icon" alt="Phone icon"/>
          <strong>Phone:</strong>&nbsp;
          <a href="tel:+94551234567" class="text-decoration-none text-dark">+94 55 123 4567</a><br><br>
        </div>
        <div class="contact-item d-flex align-items-center">
          <img src="https://img.icons8.com/ios-glyphs/30/007BFF/marker.png" class="contact-icon" alt="Location icon"/>
          <strong>Address:</strong>&nbsp;
          <span>Uva Wellassa University, Passara Road, Badulla, Sri Lanka</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <h5>Library Book Borrowing System</h5>
    <p>Empowering knowledge through easy access to books.</p>
    <a href="#">Home</a>
    <a href="#">Search Books</a>
    <a href="#">Help Center</a>
    <a href="#">Privacy Policy</a>
    <p class="mt-3">&copy; 2025 Library System. All rights reserved.</p>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  });
</script>
</body>
</html>
