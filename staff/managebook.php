<?php
session_start();
require_once("../other/php/connect.php");

$profilePhoto = "";
$userInitial = "S";
$uploadSuccess = false;

// Get staff profile photo
if (isset($_SESSION['user'])) {
    $staffId = $_SESSION['user'];
    $query = "SELECT name, profilephoto FROM staff WHERE `User ID` = '$staffId'";
    $result = mysqli_query($connect, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $staff = mysqli_fetch_assoc($result);
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

// Handle book upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadBook'])) {
    $serialNumber = $_POST['serialNumber'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $uploadedUser = $_SESSION['user'];
    $availability = "Available";

    // Handle photo upload
    $photoName = "";
    if (isset($_FILES['bookphoto']) && $_FILES['bookphoto']['error'] === 0) {
        $targetDir = "../uploads/";
        $photoName = basename($_FILES["bookphoto"]["name"]);
        $targetFile = $targetDir . $photoName;
        move_uploaded_file($_FILES["bookphoto"]["tmp_name"], $targetFile);
    }

    $stmt = $connect->prepare("INSERT INTO book (serialNumber, title, author, category, availability, description, bookphoto, uploadedUser) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $serialNumber, $title, $author, $category, $availability, $description, $photoName, $uploadedUser);

    if ($stmt->execute()) {
        $uploadSuccess = true;
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
    WHERE uploadedUser = '$staffId'
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
      background-color: #007BFF;
      color: white;
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
    .card-img-top {
  height: 250px; /* Fixed height for all book images */
  object-fit: cover; /* Crop image without distortion */
}.card {
  height: 350px; /* same size for all cards */
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.card-title {
    text-align: center;
    padding: 10px;
    background-color: #ffffffff; /* ← Blue Background */
    color: black;
    font-weight: 600;
}

  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="#"><img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo" class="logo-img"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon bg-light"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="managebook.php"><u>Manage Books</u></a></li>
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
    <h1>Choose your book here.</h1>
  </div>
</section>

<!-- Upload Book Button -->
<div class="container my-4">
  <?php if ($uploadSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Book uploaded!</strong> The book has been added successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadBookModal">Upload Book</button>
      <a href="phpFiles/staffdashboard.php" class="btn btn-outline-primary" style="float:right;">
        Go-to Book Borrowing Management
    </a>
</div>

<!-- Staff Uploaded Books -->
<div class="container mt-4">
  <h4 class="mb-3">Your Uploaded Books</h4>
  <div class="row row-cols-1 row-cols-md-3 row-cols-lg-6 g-4">
    <?php if (mysqli_num_rows($staffBooksQuery) > 0): ?>
      <?php while ($book = mysqli_fetch_assoc($staffBooksQuery)):
        $photo = !empty($book['bookphoto']) ? "../uploads/" . htmlspecialchars($book['bookphoto']) : "../images/default-book.png";
      ?>
        <div class="col">
          <div class="card h-100">
            <img src="<?= $photo ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
            <div class="card-body text-center card-title">
              <h6 class="card-title mb-1"><?= htmlspecialchars($book['title']) ?></h6>
              <p class="text-muted card-title" style="font-size: 14px;"><?= htmlspecialchars($book['author']) ?></p>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-muted">You have not uploaded any books yet.</p>
    <?php endif; ?>
  </div>
  <div class="text-center mt-3 float-end">
    <a href="phpFiles/managebooks.php" class="btn btn-outline-primary">
        View All & Manage
    </a>
  </div>
</div>



<!-- Book Cards Dynamic -->
<br><br><div class="container">
  <br><br><h4 class="mb-3">Rcently added Books</h4>
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
    <div class="text-center mt-3 float-end">
    <a href="phpFiles/allbooks.php" class="btn btn-outline-primary">
        View All Books
    </a>
</div>
</div>


<!-- Upload Book Modal -->
<div class="modal fade" id="uploadBookModal" tabindex="-1" aria-labelledby="uploadBookModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="uploadBookModalLabel">Upload a New Book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Serial Number</label>
            <input type="text" name="serialNumber" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Author</label>
            <input type="text" name="author" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" required>
              <option value="" disabled selected>Select Category</option>
              <option value="Textbooks">Textbooks-- Course-related books used for academic study.</option>
              <option value="General Books">General Books-- Books on various topics for general reading or research.</option>
              <option value="Fiction">Fiction-- Novels, stories, and literary works for leisure or literature studies.</option>
              <option value="Non-Fiction">Non-Fiction-- Factual books such as history, biographies, science, and more.</option>
              <option value="Magazines & Periodicals">Magazines & Periodicals-- Popular magazines or academic periodicals that can be borrowed.</option>
              <option value="Language & Literature">Language & Literature-- Books for learning languages, literature studies, poetry, and essays.</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" required></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Book Cover</label>
            <input type="file" name="bookphoto" class="form-control" accept="image/*" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="uploadBook" class="btn btn-primary">Upload Book</button>
      </div>
    </form>
  </div>
</div>

<!-- Footer -->
<br><br><footer class="footer mt-5">
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
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach(el => new bootstrap.Tooltip(el));
</script>
</body>
</html>
