<?php
session_start();
require_once("../../other/php/connect.php");

// Ensure user is logged in
$enrollment = $_SESSION['user'] ?? '';
$feedback = '';

// Handle Borrow or Reserve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !empty($enrollment)) {
    $bookId = mysqli_real_escape_string($connect, $_POST['book_id']);
    $action = $_POST['action'];

    // Count borrowed and reserved books for this user
    $cntBorrow = mysqli_fetch_assoc(mysqli_query($connect, "
        SELECT COUNT(*) AS cnt 
        FROM book_record 
        WHERE student_id='$enrollment' 
          AND return_date >= CURDATE() 
          AND state IN ('ACTIVE', 'DELIVERED')
    "))['cnt'];

    $cntReserve = mysqli_fetch_assoc(mysqli_query($connect, "
        SELECT COUNT(*) AS cnt 
        FROM reserve_record 
        WHERE student_id='$enrollment' 
          AND state='ACTIVE'
    "))['cnt'];
    $total = $cntBorrow + $cntReserve;

    // Check if this book is already borrowed
    $alreadyBorrowed = mysqli_query($connect, "SELECT * FROM book_record WHERE student_id='$enrollment' AND book_id='$bookId' AND return_date >= CURDATE() AND state='ACTIVE'");

    if ($action === 'borrow') {
        if (mysqli_num_rows($alreadyBorrowed) > 0) {
            $feedback = "<div class='alert alert-warning text-center'>You have already borrowed this book.</div>";
        } elseif ($cntBorrow >= 2) {
            $feedback = "<div class='alert alert-danger text-center'>You already have 2 borrowed books.</div>";
        } elseif ($total >= 2) {
            $feedback = "<div class='alert alert-danger text-center'>You can only have 2 active books (borrowed or reserved).</div>";
        } else {
            $borrowDate = date('Y-m-d');
            $returnDate = date('Y-m-d', strtotime('+14 days'));
            mysqli_query($connect, "INSERT INTO book_record (book_id, student_id, borrow_date, return_date, fines, state)
                VALUES ('$bookId','$enrollment','$borrowDate','$returnDate',0,'ACTIVE')");
            mysqli_query($connect, "UPDATE book SET availability='Not Available' WHERE serialNumber='$bookId'");
            $feedback = "<div class='alert alert-success text-center'>Book borrowed successfully! Please collect within 1–2 days.</div>";
        }
    }

    if ($action === 'reserve') {
        $alreadyReserved = mysqli_query($connect, "SELECT * FROM reserve_record WHERE student_id='$enrollment' AND book_id='$bookId' AND state='ACTIVE'");
        if (mysqli_num_rows($alreadyBorrowed) > 0) {
            $feedback = "<div class='alert alert-warning text-center'>You cannot reserve a book you have already borrowed.</div>";
        } elseif (mysqli_num_rows($alreadyReserved) > 0) {
            $feedback = "<div class='alert alert-warning text-center'>You have already reserved this book.</div>";
        } elseif ($total >= 2) {
            $feedback = "<div class='alert alert-danger text-center'>Reservation limit reached. You can only have 2 books total (borrowed or reserved).</div>";
        } else {
            $row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT MIN(return_date) AS next_return FROM book_record WHERE book_id='$bookId'"));
            $expected = $row['next_return'] ?? 'Unknown';
            mysqli_query($connect, "INSERT INTO reserve_record (student_id, book_id, reservation_date, state)
                VALUES ('$enrollment','$bookId',CURRENT_DATE(),'ACTIVE')");
            $feedback = "<div class='alert alert-success text-center'>Book reserved! <br>Your dashboard will update when book is available.</div>";
        }
    }
}

// Filter books
$filter = $_GET['availability'] ?? 'all';
$where = "WHERE category='Textbooks'";
if ($filter === 'available') {
    $where .= " AND availability='Available'";
}
if ($filter === 'notavailable') {
    $where .= " AND availability='Not Available'";
}
if ($filter === 'currently') {
    $where .= " AND (
        EXISTS (SELECT 1 FROM reserve_record WHERE book_id = book.serialNumber AND state='ACTIVE')
    )";
}

// Fetch books with dynamic availability
$books = mysqli_query($connect, "
SELECT book.*, 
  CASE 
    WHEN EXISTS (SELECT 1 FROM reserve_record WHERE book_id = book.serialNumber AND state='ACTIVE')
    THEN 'Currently Unavailable' 
    ELSE book.availability 
  END AS availability_status
FROM book
$where
ORDER BY serialNumber DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Textbooks - Library System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #d2e5fa; font-family: 'Segoe UI', sans-serif; }
    .navbar { background-color: #007BFF; }
    .navbar-brand, .nav-link { color: white !important; }
    .logo-img { height: 60px; border-radius: 50%; margin-top: -10px; margin-bottom: -10px; }
    .banner {
      background-image: url('../../images/book categories/text.jpg');
      background-size: cover;
      background-position: center;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .banner-overlay { background-color: rgba(0, 0, 0, 0.5); padding: 30px 50px; border-radius: 10px; }
    .banner h1 { color: white; font-size: 2.5rem; }
    .footer { background-color: #0056b3; color: white; padding: 30px 20px; text-align: center; }
    .footer a { color: white; text-decoration: none; display: block; margin: 5px 0; }
    .footer a:hover { text-decoration: underline; }
    .card { border: none; transition: transform 0.3s, box-shadow 0.3s; position: relative; cursor: pointer; }
    .card:hover { transform: scale(1.03); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2); }
    .card-body h5 { font-size: 1rem; font-weight: bold; }
    .availability-badge {
      font-size: 0.8rem; padding: 4px 8px; border-radius: 20px; display: inline-block; color: white;
    }
    .bg-success { background-color: green !important; }
    .bg-warning { background-color: orange !important; }
    .bg-danger { background-color: red !important; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="../index.php">
      <img src="../../images/uni-logo-removebg-preview.png" alt="UWU Logo" class="logo-img">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon bg-light"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="../borrowbook.php"><u>Borrow Books</u></a></li>
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="contactus.php">Contact Us</a></li>
      </ul>
    </div>
  </div>
</nav>

<section class="banner">
  <div class="banner-overlay"><h1>Textbooks Collection</h1></div>
</section>

<!-- Back & Dashboard Buttons -->
<div class="container my-4">
  <div class="d-flex justify-content-between">
    <a href="../borrowbook.php" class="btn btn-outline-primary">
      ← Back to Borrow Books
    </a>
    <a href="../phpFiles/studentdashboard.php" class="btn btn-outline-success">
      Go to Dashboard →
    </a>
  </div>
</div>


<div class="container my-4 text-center">
  <?= $feedback ?>
  <a href="textbooks.php" class="btn btn-primary <?= $filter === 'all' ? 'active' : '' ?>">All</a>
  <a href="textbooks.php?availability=available" class="btn btn-success <?= $filter === 'available' ? 'active' : '' ?>">✓ Available</a>
  <a href="textbooks.php?availability=notavailable" class="btn btn-warning <?= $filter === 'notavailable' ? 'active' : '' ?>">✗ Not Available</a>
  <a href="textbooks.php?availability=currently" class="btn btn-danger <?= $filter === 'currently' ? 'active' : '' ?>">Currently Unavailable</a>
</div>

<div class="container mt-4 mb-5">
  <div class="row g-4">
    <?php while ($book = mysqli_fetch_assoc($books)): ?>
      <?php
        $status = strtolower($book['availability_status']);
        $badgeColor = 'secondary';
        $label = '';

        if ($status === 'available') {
            $badgeColor = 'success'; $label = '✓ Available';
        } elseif ($status === 'not available') {
            $badgeColor = 'warning'; $label = '✗ Not Available';
        } elseif ($status === 'currently unavailable') {
            $badgeColor = 'danger'; $label = 'Currently Unavailable';
        }
      ?>
      <div class="col-md-3">
        <div class="card h-100" data-bs-toggle="modal" data-bs-target="#bookModal<?= $book['serialNumber'] ?>">
          <img src="../../uploads/<?= htmlspecialchars($book['bookphoto']) ?>" class="card-img-top" style="height:250px;object-fit:cover;">
          <div class="card-body text-center d-flex flex-column">
            <h5 class="mb-1"><?= htmlspecialchars($book['title']) ?></h5>
            <p class="text-muted mb-2"><?= htmlspecialchars($book['author']) ?></p>
            <span class="availability-badge bg-<?= $badgeColor ?>"><?= $label ?></span>
          </div>
        </div>
      </div>

      <!-- Modal -->
      <div class="modal fade" id="bookModal<?= $book['serialNumber'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?= htmlspecialchars($book['title']) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row">
              <div class="col-md-5">
                <img src="../../uploads/<?= htmlspecialchars($book['bookphoto']) ?>" class="img-fluid rounded">
              </div>
              <div class="col-md-7">
                <p><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
                <p><strong>Category:</strong> <?= htmlspecialchars($book['category']) ?></p>
                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                <p><strong>Availability:</strong>
                  <span class="availability-badge bg-<?= $badgeColor ?>"><?= $label ?></span>
                </p>

                <?php if ($status === 'available'): ?>
                  <div class="alert alert-warning">
                    <strong>Borrow Policy:</strong><br>
                    • Borrow up to 2 books<br>
                    • Collect in 1–2 days<br>
                    • Return within 2 weeks (or fines apply)
                  </div>
                  <form method="post">
                    <input type="hidden" name="book_id" value="<?= $book['serialNumber'] ?>">
                    <input type="hidden" name="action" value="borrow">
                    <button class="btn btn-primary" type="submit">Borrow Book</button>
                  </form>
                <?php elseif ($status === 'not available'): ?>
                  <?php
                    $next = mysqli_fetch_assoc(mysqli_query($connect, "SELECT MIN(return_date) AS ret FROM book_record WHERE book_id='{$book['serialNumber']}'"))['ret'];
                    $late = ($next && strtotime($next) < time()) ? "<span class='text-danger'>(Delayed Return)</span>" : "";
                  ?>
                  <p><strong>Expected Available Date:</strong> <?= $next ?: "Unknown" ?> <?= $late ?></p>
                  <form method="post">
                    <input type="hidden" name="book_id" value="<?= $book['serialNumber'] ?>">
                    <input type="hidden" name="action" value="reserve">
                    <button class="btn btn-success" type="submit">Reserve Book</button>
                  </form>
                <?php elseif ($status === 'currently unavailable'): ?>
                  <p>This book is currently reserved by another user and unavailable for borrow or reserve.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>

    <?php if (mysqli_num_rows($books) === 0): ?>
      <div class="col-12 text-center"><div class="alert alert-info">No books found in this category.</div></div>
    <?php endif; ?>
  </div>
</div>

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
</body>
</html>
