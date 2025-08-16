<?php
session_start();
require_once("../other/php/connect.php");

$profilePhoto = "";
$userInitial = "S";

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user'];
    $query = "SELECT name, profilephoto FROM staff WHERE `User ID` = '$userId'";
    $result = mysqli_query($connect, $query);

    if (!$result) {
        die("Query Error: " . mysqli_error($connect));
    }

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
  <title>Staff Home - Library System</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

    .card {
      border: none;
      transition: transform 0.3s, box-shadow 0.3s;
      overflow: hidden;
      position: relative;
    }

    .card:hover {
      transform: scale(1.03);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .card-hover-logo {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 0;
      transition: opacity 0.3s;
      z-index: 2;
    }

    .card-hover-logo img {
      width: 80px;
      height: 80px;
      opacity: 0.2;
      border-radius: 50%;
    }

    .card:hover .card-hover-logo {
      opacity: 1;
    }

    .card-title {
      text-align: center;
      margin-top: auto;
    }

    .footer {
      background-color: #0056b3;
      color: white;
      padding: 30px 20px;
      text-align: center;
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
        <li class="nav-item"><a class="nav-link active" href="#"><u>Home</u></a></li>
        <li class="nav-item"><a class="nav-link" href="managebook.php">Manage Books</a></li>
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="contactus.php">Contact Us</a></li>
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

<section class="banner">
  <div class="banner-overlay">
    <h1>Library Book Borrowing & Management System</h1>
  </div>
</section>

<section class="recent-books my-5">
  <h2 class="text-center">Recently Added Books</h2><br>
  <div class="container">
    <div class="row row-cols-1 row-cols-md-4 g-4">
      <!-- Sample Books (You can change these) -->
      <div class="col">
        <a href="#" class="text-decoration-none text-dark">
          <div class="card h-100 position-relative">
            <img src="../books/LIterary theory.jpg" class="card-img-top" alt="Book 1">
            <div class="card-body text-center">
              <h5 class="card-title">Literary Theory by Terry Eagleton</h5>
            </div>
            <div class="card-hover-logo">
              <img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo">
            </div>
          </div>
        </a>
      </div>

      <div class="col">
        <a href="#" class="text-decoration-none text-dark">
          <div class="card h-100 position-relative">
            <img src="../books/Learn Hindi.jpg" class="card-img-top" alt="Book 2">
            <div class="card-body text-center">
              <h5 class="card-title">Learn Hindi by Ajay Kumar Bhalla</h5>
            </div>
            <div class="card-hover-logo">
              <img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo">
            </div>
          </div>
        </a>
      </div>

      <div class="col">
        <a href="#" class="text-decoration-none text-dark">
          <div class="card h-100 position-relative">
            <img src="../books/CSS.jpg" class="card-img-top" alt="Book 3">
            <div class="card-body text-center">
              <h5 class="card-title">CSS For Web Designers Only</h5>
            </div>
            <div class="card-hover-logo">
              <img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo">
            </div>
          </div>
        </a>
      </div>

      <div class="col">
        <a href="#" class="text-decoration-none text-dark">
          <div class="card h-100 position-relative">
            <img src="../books/the osulivin.jpg" class="card-img-top" alt="Book 4">
            <div class="card-body text-center">
              <h5 class="card-title">The O'Sullivan Twins by Enid Blyton</h5>
            </div>
            <div class="card-hover-logo">
              <img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo">
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  });
</script>
</body>
</html>
