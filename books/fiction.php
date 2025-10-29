<?php
session_start();
require_once("../other/php/connect.php");

// Fetch staff profile
$profilePhoto = "";
$userInitial = "S";

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user'];
    $query = "SELECT name, profilephoto FROM staff WHERE `User ID` = '$userId'";
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

// Filter logic
$filter = $_GET['availability'] ?? 'all';
$filterQuery = "WHERE category='fiction'";
if ($filter === 'available') {
    $filterQuery .= " AND availability='Available'";
} elseif ($filter === 'notavailable') {
    $filterQuery .= " AND availability='Not Available'";
}

$booksQuery = "SELECT * FROM book $filterQuery ORDER BY serialNumber DESC";
$booksResult = mysqli_query($connect, $booksQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fiction - Library System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 <style>
body {
	background-color: #d2e5fa;
	font-family: 'Segoe UI', sans-serif;
}

.navbar {
	background-color: #007BFF;
}

.navbar-brand,
.nav-link {
	color: white !important;
}

.logo-img {
	height: 60px;
	border-radius: 50%;
	margin-top: -10px;
	margin-bottom: -10px;
}

.user-photo-link,
.user-icon {
	width: 40px;
	height: 40px;
	border-radius: 50%;
	background-color: white;
	text-align: center;
	line-height: 40px;
	font-size: 18px;
	font-weight: bold;
	color: #007BFF;
	text-decoration: none;
	overflow: hidden;
}

.user-photo-img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.banner {
	background-image: url('../images/book categories/fiction.jpg');
	background-size: cover;
	background-position: center;
	height: 300px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.banner-overlay {
	background-color: rgba(0, 0, 0, 0.5);
	padding: 30px 50px;
	border-radius: 10px;
}

.banner h1 {
	color: white;
	font-size: 2.5rem;
}

.footer {
	background-color: #0056b3;
	color: white;
	padding: 30px 20px;
	text-align: center;
}

.footer a {
	color: white;
	text-decoration: none;
	display: block;
	margin: 5px 0;
}

.footer a:hover {
	text-decoration: underline;
}

.card {
	border: none;
	transition: transform 0.3s, box-shadow 0.3s;
	position: relative;
	cursor: pointer;
}

.card:hover {
	transform: scale(1.03);
	box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.card-body h5 {
	font-size: 1rem;
	font-weight: bold;
}

.availability-badge {
	font-size: 0.8rem;
	padding: 4px 8px;
	border-radius: 20px;
	display: inline-block;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="#"><img src="../images/uni-logo-removebg-preview.png" alt="UWU Logo" class="logo-img"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon bg-light"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="borrowbook.php"><u>Borrow Books</u></a></li>
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="contactus.php">Contact Us</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <?php if (!empty($profilePhoto)) : ?>
          <a href="phpFiles/staffprofile.php" class="user-photo-link" data-bs-toggle="tooltip" title="Go to Profile">
            <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="Profile" class="user-photo-img">
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

<!-- Hero Banner -->
<section class="banner">
  <div class="banner-overlay">
    <h1>Fiction Collection</h1>
  </div>
</section>

<!-- Filter Buttons (Green/Red Below Hero) -->
<div class="container my-4">
  <div class="text-center">
    <a href="fiction.php" class="btn btn-primary <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    <a href="fiction.php?availability=available" class="btn btn-success <?= $filter === 'available' ? 'active' : '' ?>">Available</a>
    <a href="fiction.php?availability=notavailable" class="btn btn-danger <?= $filter === 'notavailable' ? 'active' : '' ?>">Not Available</a>
  </div>
</div>

<!-- Book Cards -->
<div class="container mt-4 mb-5">
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
    <?php if ($booksResult && mysqli_num_rows($booksResult) > 0): ?>
      <?php while ($book = mysqli_fetch_assoc($booksResult)): ?>
        <div class="col">
          <div class="card h-100" data-bs-toggle="modal" data-bs-target="#bookModal<?= $book['serialNumber'] ?>">
            <img src="../uploads/<?= htmlspecialchars($book['bookphoto']) ?>" class="card-img-top" style="height:250px; object-fit:cover;" alt="<?= htmlspecialchars($book['title']) ?>">
            <div class="card-body text-center d-flex flex-column">
              <h5 class="mb-1"><?= htmlspecialchars($book['title']) ?></h5>
              <p class="text-muted mb-2"><?= htmlspecialchars($book['author']) ?></p>
              <div>
                <span class="availability-badge bg-<?= strtolower($book['availability']) === 'available' ? 'success' : 'danger' ?>">
                  <?= $book['availability'] ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Book Detail Modal -->
        <div class="modal fade" id="bookModal<?= $book['serialNumber'] ?>" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($book['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body row">
                <div class="col-md-5">
                  <img src="../uploads/<?= htmlspecialchars($book['bookphoto']) ?>" class="img-fluid rounded">
                </div>
                <div class="col-md-7">
                  <p><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
                  <p><strong>Category:</strong> <?= htmlspecialchars($book['category']) ?></p>
                  <p><strong>Availability:</strong>
                    <span class="availability-badge bg-<?= strtolower($book['availability']) === 'available' ? 'success' : 'danger' ?>">
                      <?= $book['availability'] ?>
                    </span>
                  </p>
                  <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12 text-center">
        <div class="alert alert-info">No books found in this category.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

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
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
</body>
</html>
