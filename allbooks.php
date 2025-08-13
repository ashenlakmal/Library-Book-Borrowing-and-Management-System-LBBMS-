<?php
   session_start();
   require_once("../../other/php/connect.php");
   
   $enrollment = $_SESSION['user'] ?? '';
   $feedback = '';
   
   // Handle Borrow or Reserve
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
       $bookId = $_POST['book_id'];
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
               $feedback = "<div class='alert alert-success text-center'>Book reserved! <br>Your dashboard will update when book is available..</div>";
           }
       }
   }
   
   // Filter & Sort
   $filter = $_GET['availability'] ?? 'all';
   $sort = $_GET['sort'] ?? 'serialNumber_desc';
   
  $where = "WHERE 1";
if ($filter === 'available') {
    $where .= " AND availability='Available'";
}
if ($filter === 'notavailable') {
    $where .= " AND availability='Not Available' 
                AND NOT EXISTS (
                    SELECT 1 FROM reserve_record 
                    WHERE book_id = book.serialNumber AND state='ACTIVE'
                )";
}
if ($filter === 'currently') {
    $where .= " AND EXISTS (
                    SELECT 1 FROM reserve_record 
                    WHERE book_id = book.serialNumber AND state='ACTIVE'
                )";
}
   switch ($sort) {
       case "title_asc": $order = "ORDER BY title ASC"; break;
       case "title_desc": $order = "ORDER BY title DESC"; break;
       case "serialNumber_asc": $order = "ORDER BY serialNumber ASC"; break;
       default: $order = "ORDER BY serialNumber DESC";
   }
   
   $books = mysqli_query($connect, "
   SELECT book.*, 
     CASE 
       WHEN EXISTS (SELECT 1 FROM reserve_record WHERE book_id = book.serialNumber AND state='ACTIVE')
       THEN 'Currently Unavailable' 
       ELSE book.availability 
     END AS availability_status
   FROM book
   $where
   $order
   ");
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <title>All Books - Library System</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
         body { background-color: #d2e5fa; font-family: 'Segoe UI', sans-serif; }
         .navbar { background-color: #007BFF; }
          .navbar { background-color: #007BFF; }
          .navbar-brand, .nav-link { color: white !important; }
          .logo-img { height: 60px; border-radius: 50%; margin-top: -10px; margin-bottom: -10px; }
         .navbar-brand, .nav-link { color: white !important; }
         .logo-img { height: 60px; border-radius: 50%; }
         .banner { background-image: url('../../images/book categories/allbooks.jpg'); background-size: cover; height: 300px; display:flex;align-items:center;justify-content:center;}
         .banner-overlay {background-color:rgba(0,0,0,0.5);padding:30px;border-radius:10px;}
         .card {border:none;cursor:pointer;transition:.3s;}
         .card:hover {transform:scale(1.03);box-shadow:0 6px 20px rgba(0,0,0,.2);}
         .availability-badge {font-size:.8rem;padding:4px 8px;border-radius:20px;color:white;}
         .search-results img {height:40px;width:40px;object-fit:cover;margin-right:10px;}
         .footer { background-color: #0056b3; color: white; padding: 30px 20px; text-align: center; }
    .footer a { color: white; text-decoration: none; display: block; margin: 5px 0; }
    .footer a:hover { text-decoration: underline; }
    .status-circle {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

      </style>
   </head>
   <body>
      <!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="../index.php">
      <img src="../../images/uni-logo-removebg-preview.png" alt="UWU Logo" class="logo-img" />
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
      <!-- Hero -->
      <section class="banner">
         <div class="banner-overlay">
            <h1 class="text-white">All Books Collection</h1>
         </div>
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


      <!-- Search -->
      <div class="container mt-4">
         <div class="row">
            <div class="col-md-6">
               <input type="text" class="form-control" id="searchInput" placeholder="Search books by title...">
               <div id="searchResults" class="list-group search-results mt-2"></div>
            </div>
            <div class="col-md-6">
               <input type="text" class="form-control" id="authorInput" placeholder="Search books by author...">
               <div id="authorResults" class="list-group search-results mt-2"></div>
            </div>
         </div>
      </div>
      <!-- Filters -->
      <div class="container my-4 text-center">
         <?= $feedback ?>
         <a href="allbooks.php" class="btn btn-primary <?= $filter==='all'?'active':'' ?>">All</a>
         <a href="allbooks.php?availability=available" class="btn btn-success <?= $filter==='available'?'active':'' ?>">✓ Available</a>
         <a href="allbooks.php?availability=notavailable" class="btn btn-warning <?= $filter==='notavailable'?'active':'' ?>">✗ Not Available</a>
         <a href="allbooks.php?availability=currently" class="btn btn-danger <?= $filter==='currently'?'active':'' ?>">Currently Unavailable</a>
      </div>
      <!-- Book Grid -->
      <div class="container mt-4 mb-5">
         <div class="row g-4">
            <?php while($book=mysqli_fetch_assoc($books)): 
               $status=strtolower($book['availability_status']);
               $badgeColor=$status==='available'?'success':($status==='not available'?'warning':'danger');
               $label=$status==='available'?'✓ Available':($status==='not available'?'✗ Not Available':'Currently Unavailable');
               ?>
            <div class="col-md-3">
               <div class="card h-100" data-bs-toggle="modal" data-bs-target="#bookModal<?= $book['serialNumber'] ?>">
                  <img src="../../uploads/<?= htmlspecialchars($book['bookphoto']) ?>" class="card-img-top" style="height:250px;object-fit:cover;">
                  <div class="card-body text-center">
                     <h5><?= htmlspecialchars($book['title']) ?></h5>
                     <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                     <span class="availability-badge bg-<?= $badgeColor ?>"><?= $label ?></span>
                  </div>
               </div>
            </div>
<!-- Modal -->
<div class="modal fade" id="bookModal<?= $book['serialNumber'] ?>" tabindex="-1">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5><?= htmlspecialchars($book['title']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body row">
            <div class="col-md-5">
               <img src="../../uploads/<?= htmlspecialchars($book['bookphoto']) ?>" class="img-fluid rounded">
            </div>
            <div class="col-md-7">
               <p><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
               <p><strong>Category:</strong> <?= htmlspecialchars($book['category']) ?></p>
               <p><?= nl2br(htmlspecialchars($book['description'])) ?></p>
               <p><span class="availability-badge bg-<?= $badgeColor ?>"><?= $label ?></span></p>

               <?php if ($status === 'available'): ?>
                  <form method="post">
                     <input type="hidden" name="book_id" value="<?= $book['serialNumber'] ?>">
                     <input type="hidden" name="action" value="borrow">
                     <button class="btn btn-primary">Borrow Book</button>
                  </form>

               <?php elseif ($status === 'not available'): ?>
                  <?php
                     // Get the latest return_date by ordering descending by borrow_id
                     $query = mysqli_query($connect, "
                        SELECT return_date
                        FROM book_record
                        WHERE book_id = '{$book['serialNumber']}' AND state = 'ACTIVE'
                        ORDER BY borrow_id DESC
                        LIMIT 1
                     ");
                     $next = mysqli_fetch_assoc($query)['return_date'] ?? 'Unknown';
                  ?>
                  <p><strong>Expected Return Date:</strong> <?= htmlspecialchars($next) ?></p>
                  <form method="post">
                     <input type="hidden" name="book_id" value="<?= $book['serialNumber'] ?>">
                     <input type="hidden" name="action" value="reserve">
                     <button class="btn btn-success">Reserve Book</button>
                  </form>

               <?php else: ?>
                  <p>This book is currently reserved by another user.</p>
               <?php endif; ?>

            </div>
         </div>
      </div>
   </div>
</div>
<?php endwhile; ?>
<?php if (mysqli_num_rows($books) === 0): ?>
<div class="col-12 text-center">
   <div class="alert alert-info">No books found.</div>
</div>
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
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <script>
         // Title search
$('#searchInput').on('keyup', function () {
   let keyword = $(this).val();
   if (keyword.length > 2) {
      $.post('search_books.php', {
         keyword: keyword,
         type: 'title'
      }, function (data) {
         $('#searchResults').html(data).show();
      });
   } else {
      $('#searchResults').hide();
   }
});
// Author search
$('#authorInput').on('keyup', function () {
   let keyword = $(this).val();
   if (keyword.length > 2) {
      $.post('search_books.php', {
         keyword: keyword,
         type: 'author'
      }, function (data) {
         $('#authorResults').html(data).show();
      });
   } else {
      $('#authorResults').hide();
   }
});
      </script>
   </body>
</html>