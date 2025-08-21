<?php
session_start();
require_once("../other/php/connect.php");

$profilePhoto = "";
$userInitial = "S";

if (isset($_SESSION['user'])) {
    $enrollment = $_SESSION['user'];
    $query = "SELECT name, profilephoto FROM student WHERE Enrollment = '$enrollment'";
    $result = mysqli_query($connect, $query);
    $student = mysqli_fetch_assoc($result);

    if ($student) {
        $name = $student['name'];
        $photo = $student['profilephoto'];

        if (!empty($photo)) {
            // Adjust path to be relative from this page
            $cleanPath = str_replace("../../", "", $photo);
            $profilePhoto = "../" . $cleanPath;
        } else {
            $nameParts = explode(" ", $name);
            $firstName = $nameParts[0] ?? "";
            $userInitial = strtoupper(substr($firstName, 0, 1)) ?: "S";
        }
    }
}

$booksQuery = mysqli_query($connect, "
    SELECT serialNumber, title, author, category, bookphoto
    FROM book
    ORDER BY serialNumber DESC
    LIMIT 6
");

$staffBooksQuery = mysqli_query($connect, "
    SELECT serialNumber, title, author, category, bookphoto
    FROM book
    ORDER BY serialNumber DESC
    LIMIT 6
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Borrow Books - Library System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #e4eaf1;
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
      background-image: url('../images/becca-tapert-GnY_mW1Q6Xc-unsplash.jpg');
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

    .book-card-link {
      text-decoration: none;
      color: inherit;
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
      padding: 10px;
      background-color: #ffffffff;
      color: black;
      font-weight: 600;
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
<body style="background-color: #d2e5fa;">

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
        <li class="nav-item"><a class="nav-link active" href="borrowbook.php"><u>Borrow Books</u></a></li>
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="contactus.php">Contact Us</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <?php if (!empty($profilePhoto)) : ?>
          <a href="phpFiles/studentprofile.php" class="user-photo-link" data-bs-toggle="tooltip" title="Go to Profile">
            <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="Profile Photo" class="user-photo-img">
          </a>
        <?php else: ?>
          <a href="phpFiles/studentprofile.php" class="user-icon" data-bs-toggle="tooltip" title="Go to Profile">
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
    <h1>Choose your book here.</h1>
  </div>
</section>

<!-- Category Filter -->
<div class="container mb-4">
  <div class="row">
    <div class="col">
      <label class="form-label fw-bold">Categories</label>
      <div class="d-flex flex-wrap gap-3">
        <a href="book/textbooks.php" class="btn btn-outline-primary">Textbooks</a>
        <a href="book/generalbooks.php" class="btn btn-outline-primary">General Books</a>
        <a href="book/fiction.php" class="btn btn-outline-primary">Fiction</a>
        <a href="book/nonfiction.php" class="btn btn-outline-primary">Non-Fiction</a>
        <a href="book/magazinesandperiodicals.php" class="btn btn-outline-primary">Magazines & Periodicals</a>
        <a href="book/languageandliterature.php" class="btn btn-outline-primary">Language & Literature</a>
        <a href="book/allbooks.php" class="btn btn-outline-primary">All</a>
      </div>
    </div>
  </div>
</div>

<!-- Book Cards Dynamic -->
  <div class="container">
  <h4 class="mb-3">Rcently added Books</h4>
  <div class="row row-cols-1 row-cols-md-3 row-cols-lg-6 g-4">
    <?php
    while ($book = mysqli_fetch_assoc($booksQuery)):
      $photo = !empty($book['bookphoto']) ? "../uploads/" . htmlspecialchars($book['bookphoto']) : "../images/default-book.png";
    ?>
      <div class="col">
        <a href="#" class="book-card-link">
          <div class="card h-100">
            <img src="<?= $photo ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
            <div class="card-hover-logo">
              <img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo">
            </div>
            <div class="card-body text-center card-title">
              <h6 class="card-title mb-1"><?= htmlspecialchars($book['title']) ?></h6>
              <p class="text-muted card-title" style="font-size: 14px;"><?= htmlspecialchars($book['author']) ?></p>
            </div>
          </div>
        </a>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Footer -->
<footer class="footer mt-5">
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

<!-- Bootstrap tooltip init -->
<script>
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  });
</script>

</body>
</html>
