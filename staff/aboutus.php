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
  <title>About Us - Library System</title>

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

          /* About Us Section */
    .about-section {
      padding: 50px 0;
    }
    .about-section h2 {
      color: #0056b3;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .about-section p {
      text-align: justify;
      color: #333;
      line-height: 1.6;
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

    .carousel-inner img {
    border-radius: 15px; 
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
        <li class="nav-item"><a class="nav-link active" href="aboutus.php"><u>About Us</u></a></li>
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

<!-- Banner -->
<section class="banner">
  <div class="banner-overlay">
    <h1>About Our Library</h1>
  </div>
</section>

<!-- About Us Content -->
<section class="about-section">
  <div class="container">
    <div class="row">
      <!-- Left Column: Text -->
      <div class="col-md-6">
        <h2>About Us</h2>
        <p>The Uva Wellassa University Library is one of the central support services of the University, dedicated to advancing education, research, and innovation. It works in line with the University’s vision by connecting information, learning, and creativity through excellent library and information services.</p>
        <p>Established in August 2006 alongside the founding of Uva Wellassa University in Badulla, the Library has grown into a vital hub for academic and research activities. It plays an important role in developing collections and sharing scientific and technical knowledge to meet the needs of both present and future generations of learners.</p>
        <p>Today, the Library serves over 2,000 readers. It provides a wide range of quality services, access to both physical and digital resources, and a supportive environment for teaching and learning. The Library is also a space where knowledge and diverse resources come together to encourage continuous learning.</p>
        <p>As a center of learning and an information hub, the Uva Wellassa University Library shares the same vision as the University itself to inspire, educate, and innovate. </p>
      </div>

      <!-- Right Column: Carousel -->
      <div class="col-md-6">
        <div id="libraryCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="../about us/images/library1.jpeg" class="d-block w-100" alt="Library Image 1">
            </div>
            <div class="carousel-item">
              <img src="../about us/images/library2.jpeg" class="d-block w-100" alt="Library Image 2">
            </div>
            <div class="carousel-item">
              <img src="../about us/images/library3.jpeg" class="d-block w-100" alt="Library Image 3">
            </div>
            <div class="carousel-item">
              <img src="../about us/images/library4.jpeg" class="d-block w-100" alt="Library Image 4">
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#libraryCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#libraryCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  });
</script>

</body>
</html>
