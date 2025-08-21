<?php
session_start();
require_once("../../other/php/connect.php");

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please log in first.'); window.location.href='../../other/login.html';</script>";
    exit;
}

$enrollment = $_SESSION['user'];

// Fetch student info
$student_query = mysqli_query($connect, "SELECT name, profilephoto FROM student WHERE Enrollment = '$enrollment'");
if (!$student_query) die("DB Error: " . mysqli_error($connect));

$student = mysqli_fetch_assoc($student_query);
$name = $student['name'] ?? 'Student';
$profilePhoto = $student['profilephoto'] ?? '';
$userInitial = strtoupper(substr(explode(" ", $name)[0] ?? "S", 0, 1));

// Fetch borrowed books
$borrowed_query = mysqli_query($connect, "
    SELECT br.*, b.title, b.author, b.category, b.bookphoto
    FROM book_record br
    JOIN book b ON br.book_id = b.serialNumber
    WHERE br.student_id = '$enrollment'
    ORDER BY br.borrow_date DESC
");
if (!$borrowed_query) die("DB Error (borrowed books): " . mysqli_error($connect));

// Fetch reserved books
$reserved_query = mysqli_query($connect, "
    SELECT rr.*, b.title, b.author, b.category, b.bookphoto
    FROM reserve_record rr
    JOIN book b ON rr.book_id = b.serialNumber
    WHERE rr.student_id = '$enrollment'
    ORDER BY rr.reservation_date DESC
");
if (!$reserved_query) die("DB Error (reserved books): " . mysqli_error($connect));

// Cancel Borrow Request
if (isset($_POST['cancel_borrow'])) {
    $borrow_id = $_POST['borrow_id'];
    $res = mysqli_query($connect, "SELECT book_id FROM book_record WHERE borrow_id='$borrow_id'");
    if (!$res) die("Error getting book_id: " . mysqli_error($connect));
    $bookId = mysqli_fetch_assoc($res)['book_id'];

    mysqli_query($connect, "UPDATE book_record SET state='CANCELED', return_date=CURDATE() WHERE borrow_id='$borrow_id'");
    mysqli_query($connect, "UPDATE book SET availability='Available' WHERE serialNumber='$bookId'");
    mysqli_query($connect, "
        UPDATE reserve_record 
        SET state='CANCELED' 
        WHERE book_id='$bookId' AND student_id='$enrollment' AND state='ACTIVE'
    ");

    header("Location: studentdashboard.php");
    exit;
}

// Cancel Reservation Request
if (isset($_POST['cancel_reserve'])) {
    $reservation_id = $_POST['reservation_id'];
    $res = mysqli_query($connect, "SELECT book_id FROM reserve_record WHERE reservation_id='$reservation_id'");
    if (!$res) die("Error getting book_id: " . mysqli_error($connect));
    $bookId = mysqli_fetch_assoc($res)['book_id'];

    mysqli_query($connect, "UPDATE reserve_record SET state='CANCELED' WHERE reservation_id='$reservation_id'");

    $borrowCheck = mysqli_query($connect, "SELECT * FROM book_record WHERE book_id='$bookId' AND state='ACTIVE'");
    $reserveCheck = mysqli_query($connect, "SELECT * FROM reserve_record WHERE book_id='$bookId' AND state='ACTIVE'");
    if (mysqli_num_rows($borrowCheck) == 0 && mysqli_num_rows($reserveCheck) == 0) {
        mysqli_query($connect, "UPDATE book SET availability='Available' WHERE serialNumber='$bookId'");
    }

    header("Location: studentdashboard.php");
    exit;
}

// Borrow from Reserve
if (isset($_POST['borrow_from_reserve'])) {
    $bookId = $_POST['borrow_from_reserve'];

    mysqli_query($connect, "
        UPDATE reserve_record
        SET state='COMPLETED'
        WHERE book_id='$bookId' AND student_id='$enrollment' AND state='ACTIVE'
    ");

    $borrowDate = date('Y-m-d');
    $returnDate = date('Y-m-d', strtotime('+14 days'));

    mysqli_query($connect, "
        INSERT INTO book_record (book_id, student_id, borrow_date, return_date, state)
        VALUES ('$bookId', '$enrollment', '$borrowDate', '$returnDate', 'ACTIVE')
    ");

    mysqli_query($connect, "UPDATE book SET availability='Not Available' WHERE serialNumber='$bookId'");

    header("Location: studentdashboard.php");
    exit;
}

// Fine Calculation Function
function calculateFine($borrowDate, $returnDate) {
    $today = new DateTime();
    $dueDate = new DateTime($returnDate);
    if ($today <= $dueDate) return 0;

    $diff = $dueDate->diff($today);
    $daysLate = $diff->days;

    $months = floor($daysLate / 30);
    $daysLate %= 30;
    $weeks = floor($daysLate / 7);
    $days = $daysLate % 7;

    return ($months * 50) + ($weeks * 10) + ($days * 5);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Student Dashboard</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #d2e5fa; font-family: 'Segoe UI', sans-serif; }
    .navbar { background-color: #007BFF; }
    .navbar-brand, .nav-link { color: white !important; }
    .logo-img { height: 60px; border-radius: 50%; }
    .user-photo-link {
      width: 40px; height: 40px; border-radius: 50%; overflow: hidden;
      background-color: white; display: inline-block;
    }
    .user-photo-img { width: 100%; height: 100%; object-fit: cover; }
    .user-icon {
      background-color: white; border-radius: 50%; width: 40px; height: 40px;
      text-align: center; line-height: 40px; font-size: 18px; font-weight: bold;
      color: #007BFF; text-decoration: none; display: inline-block;
    }
    .footer {
      background-color: #0056b3; color: white; padding: 30px 20px; text-align: center;
    }
    .footer a { color: white; text-decoration: none; display: block; margin: 5px 0; }
    .footer a:hover { text-decoration: underline; }
    .card-img-top { height: 220px; object-fit: cover; }

      .history-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
  }
  .history-table thead {
    background: #004080;
    color: white;
    text-transform: uppercase;
  }
  .history-table tbody tr {
    background: white;
    transition: all 0.3s ease;
  }
  .history-table tbody tr:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  .row-borrow { background: #e8f4fa; } /* Light Blue */
  .row-reserve { background: #f4e8fa; } /* Light Purple */
  .status-active { background-color: #d4edda; color: #155724; font-weight: bold; } /* Green */
  .status-canceled { background-color: #f8d7da; color: #721c24; font-weight: bold; } /* Red */
  .status-returned { background-color: #fff3cd; color: #856404; font-weight: bold; } /* Yellow */
  .badge-status {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.85rem;
  }

  .status-delivered {
  background-color: #d1ecf1;
  color: #0c5460;
  font-weight: bold;
}

  </style>


</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="../index.php">
      <img src="../../images/uni-logo-removebg-preview.png" alt="Logo" class="logo-img" />
    </a>
    <div class="collapse navbar-collapse justify-content-end">
      <div class="d-flex align-items-center">
        <?php if (!empty($profilePhoto)) : ?>
          <a href="studentprofile.php" class="user-photo-link" data-bs-toggle="tooltip" title="Profile">
            <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="Profile Photo" class="user-photo-img" />
          </a>
        <?php else : ?>
          <a href="studentprofile.php" class="user-icon"><?= htmlspecialchars($userInitial) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-3">
  <a href="../index.php" class="btn btn-secondary">← Back to Home</a>
</div>

<div class="container mt-4">
  <h2 class="text-center mb-4">Active Borrowed Books</h2>
  <div class="row g-4">
<?php
$hasActive = false; // Define default state

while ($borrow = mysqli_fetch_assoc($borrowed_query)) {
    if (in_array($borrow['state'], ['ACTIVE', 'DELIVERED'])) {
        $hasActive = true; // Set to true if at least one active or delivered record found
        ?>
        <div class="col-md-4">
          <div class="card h-100">
            <img src="../../uploads/<?= htmlspecialchars($borrow['bookphoto']) ?>" class="card-img-top" alt="Book Photo" />
            <div class="card-body">
              <h5><?= htmlspecialchars($borrow['title']) ?></h5>
              <p>Author: <?= htmlspecialchars($borrow['author']) ?></p>
              <p>Borrowed: <?= htmlspecialchars($borrow['borrow_date']) ?><br />
                 Return by: <?= htmlspecialchars($borrow['return_date']) ?></p>
              <p id="fine_<?= $borrow['borrow_id'] ?>" class="text-danger fw-bold">Fine: Calculating...</p>
              <?php if ($borrow['state'] === 'ACTIVE'): ?>
                <form id="cancelBorrowForm-<?= $borrow['borrow_id'] ?>" method="post">
                  <input type="hidden" name="cancel_borrow" value="1" />
                  <input type="hidden" name="borrow_id" value="<?= $borrow['borrow_id'] ?>" />
                  <button type="button" class="btn btn-danger cancel-borrow" data-id="<?= $borrow['borrow_id'] ?>">
                    Cancel Borrow
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php
    }
}
?>

<?php
if (!$hasActive) {
    echo '<div class="col-12 text-center"><div class="alert alert-info">No active borrowed books.</div></div>';
}
?>

    <div class="alert alert-warning" role="alert">
      <strong>Important:</strong> Fines are calculated as follows:
      <ul>
        <li>After due date:</li>
        <li><strong>Rs. 50 per month</strong></li>
        <li><strong>Rs. 10 per week</strong></li>
        <li><strong>Rs. 5 per day</strong></li>
      </ul>
      <p><em>Example:</em> 50 days late = 1 month, 2 weeks, 6 days → Rs. 100</p>
    </div>
    
  </div>
    <div id="totalFineDisplay" class="mt-3"></div>
  <hr class="my-5" />

  <h2 class="text-center mb-4">Active Reserved Books</h2>
  <div class="row g-4">
    <?php
    mysqli_data_seek($reserved_query, 0);
    $hasActive = false;
    while ($reserve = mysqli_fetch_assoc($reserved_query)) {
        if ($reserve['state'] === 'ACTIVE') {
            $hasActive = true;
    ?>
    <div class="col-md-4">
      <div class="card h-100">
        <img src="../../uploads/<?= htmlspecialchars($reserve['bookphoto']) ?>" class="card-img-top" alt="Book Photo" />
        <div class="card-body">
          <h5><?= htmlspecialchars($reserve['title']) ?></h5>
          <p>Author: <?= htmlspecialchars($reserve['author']) ?></p>
          <p>Reserved on: <?= htmlspecialchars($reserve['reservation_date']) ?></p>
          <?php
              $expectedResult = mysqli_query($connect, "
                  SELECT MAX(return_date) AS latest_return
                  FROM book_record
                  WHERE book_id = '{$reserve['book_id']}'
              ");
              $expected = mysqli_fetch_assoc($expectedResult)['latest_return'] ?? 'Unknown';
          ?>
          <p>Expected Available: <?= htmlspecialchars($expected) ?></p>
          <form id="cancelReserveForm-<?= $reserve['reservation_id'] ?>" method="post">
            <input type="hidden" name="cancel_reserve" value="1" />
            <input type="hidden" name="reservation_id" value="<?= $reserve['reservation_id'] ?>" />
            <button type="button" class="btn btn-danger cancel-reserve" data-id="<?= $reserve['reservation_id'] ?>">
              Cancel Reservation
            </button>
          </form>

          <?php
          // - This student is first in queue
          // - Last borrow record is RETURNED or CANCELED
          $isFirstQuery = mysqli_query($connect, "
              SELECT student_id FROM reserve_record
              WHERE book_id='{$reserve['book_id']}' AND state='ACTIVE'
              ORDER BY reservation_date ASC LIMIT 1
          ");
          $firstUser = mysqli_fetch_assoc($isFirstQuery)['student_id'] ?? null;

          // Check last borrow state
          $borrowCheck = mysqli_query($connect, "
              SELECT state FROM book_record
              WHERE book_id='{$reserve['book_id']}'
              ORDER BY borrow_id DESC LIMIT 1
          ");
          $borrowState = mysqli_fetch_assoc($borrowCheck)['state'] ?? null;
          if ($firstUser === $enrollment && in_array($borrowState, ['CANCELED','RETURNED',null])) {
              ?><br>
              <form id="borrowFromReserveForm-<?= $reserve['book_id'] ?>" method="post">
                  <input type="hidden" name="borrow_from_reserve" value="<?= $reserve['book_id'] ?>" />
                  <button
                    type="button"
                    class="btn btn-primary borrow-from-reserve"
                    data-id="<?= $reserve['book_id'] ?>"
                    data-title="<?= htmlspecialchars($reserve['title'], ENT_QUOTES) ?>"
                  >
                    Borrow Now
                  </button>
              </form>
              <?php
          }
          ?>
        </div>
      </div>
    </div>
    <?php
        }
    }
    if (!$hasActive) {
        echo '<div class="col-12 text-center"><div class="alert alert-info">No active reservations.</div></div>';
    }
    ?>
  </div>

  <hr class="my-5" />

  <h2 class="text-center mb-4">Borrowing & Reservation History</h2>
<div class="table-responsive">
<table class="table history-table">
  <thead>
    <tr>
      <th>Type</th>
      <th>Title</th>
      <th>Author</th>
      <th>Borrow Date</th>
      <th>Return Date</th>
      <th>Status</th>
      <th>Fine</th>
    </tr>
  </thead>
  <tbody>
    <?php
    mysqli_data_seek($borrowed_query, 0);
    while ($borrow = mysqli_fetch_assoc($borrowed_query)) {
        $borrowDate = $borrow['borrow_date'] ?? "-";
        $returnDate = $borrow['return_date'] ?? "-";
        $fine = isset($borrow['fines']) ? (int)$borrow['fines'] : 0;

        // Status logic
        $rowClass = "row-borrow";
        $statusClass = "badge-status ";

        if ($borrow['state'] === 'ACTIVE') {
            $statusClass .= "status-active";
            $statusText = "Active";
        } elseif ($borrow['state'] === 'DELIVERED') {
            $statusClass .= "status-delivered";
            $statusText = "Delivered";
            $rowClass = "row-delivered";
        } elseif (in_array($borrow['state'], ['CANCELED', 'CANCELLED'])) {
            $statusClass .= "status-canceled";
            $statusText = "Canceled";
        } else {
            $statusClass .= "status-returned";
            $statusText = "Returned";
        }

        echo "<tr class='$rowClass'>
            <td><strong>Borrow</strong></td>
            <td>" . htmlspecialchars($borrow['title']) . "</td>
            <td>" . htmlspecialchars($borrow['author']) . "</td>
            <td>" . htmlspecialchars($borrowDate) . "</td>
            <td>" . htmlspecialchars($returnDate) . "</td>
            <td><span class='$statusClass'>$statusText</span></td>
            <td>" . ($fine > 0 ? "Rs. $fine" : "-") . "</td>
        </tr>";
    }

    mysqli_data_seek($reserved_query, 0);
    while ($reserve = mysqli_fetch_assoc($reserved_query)) {
        $rowClass = "row-reserve";
        $statusClass = "badge-status ";

        if ($reserve['state'] === 'ACTIVE') {
            $statusClass .= "status-active";
            $statusText = "Active";
        } elseif (in_array($reserve['state'], ['CANCELED', 'CANCELLED'])) {
            $statusClass .= "status-canceled";
            $statusText = "Canceled";
        } else {
            $statusClass .= "status-returned";
            $statusText = ucfirst(strtolower($reserve['state']));
        }

        echo "<tr class='$rowClass'>
            <td><strong>Reserve</strong></td>
            <td>" . htmlspecialchars($reserve['title']) . "</td>
            <td>" . htmlspecialchars($reserve['author']) . "</td>
            <td>" . htmlspecialchars($reserve['reservation_date']) . "</td>
            <td>-</td>
            <td><span class='$statusClass'>$statusText</span></td>
            <td>-</td>
        </tr>";
    }
    ?>
  </tbody>
</table>

</div>
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
<script>
function updateFines() {
    fetch('get_fines.php')
        .then(response => response.json())
        .then(data => {
            // Total Fine Display
            const fineElement = document.getElementById('totalFineDisplay');
            if (data.totalFine > 0) {
                fineElement.innerHTML = `<div class="alert alert-danger">
                    <strong>Total Outstanding Fine:</strong> Rs. ${data.totalFine}
                </div>`;
            } else {
                fineElement.innerHTML = `<div class="alert alert-success">
                    <strong>No fines at the moment!</strong>
                </div>`;
            }

            // Book-wise Fine Update
            data.books.forEach(book => {
                const fineField = document.getElementById(`fine_${book.borrow_id}`);
                if (fineField) {
                    fineField.textContent = book.fine > 0 
                        ? `Fine: Rs. ${book.fine}`
                        : `Fine: None`;
                }
            });
        })
        .catch(error => console.error('Error fetching fines:', error));
}

// Refresh every 30 seconds
setInterval(updateFines, 30000);
updateFines();
</script>

<script>
$('.cancel-borrow').click(function(){
  let id = $(this).data('id');
  Swal.fire({
    title: 'Are you sure?',
    text: 'You are about to cancel this borrow request.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, cancel it!'
  }).then((res) => {
    if (res.isConfirmed) {
      let form = $('#cancelBorrowForm-' + id);
      if (form.find('input[name="cancel_borrow"]').length === 0) {
        $('<input>').attr({
          type: 'hidden',
          name: 'cancel_borrow',
          value: '1'
        }).appendTo(form);
      }
      form.submit();
    }
  });
});

$('.cancel-reserve').click(function(){
  let id = $(this).data('id');
  Swal.fire({
    title: 'Are you sure?',
    text: 'You are about to cancel this reservation.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, cancel it!'
  }).then((res) => {
    if (res.isConfirmed) {
      let form = $('#cancelReserveForm-' + id);
      if (form.find('input[name="cancel_reserve"]').length === 0) {
        $('<input>').attr({
          type: 'hidden',
          name: 'cancel_reserve',
          value: '1'
        }).appendTo(form);
      }
      form.submit();
    }
  });
});

$('.borrow-from-reserve').click(function(){
  let book = $(this).data('title') || 'this book';
  let id = $(this).data('id');
  Swal.fire({
    title: 'Borrow Now?',
    text: `Do you want to borrow "${book}" now?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#198754',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, borrow'
  }).then((res) => {
    if (res.isConfirmed) {
      let form = $('#borrowFromReserveForm-' + id);
      if (form.find('input[name="borrow_from_reserve"]').length === 0) {
        $('<input>').attr({
          type: 'hidden',
          name: 'borrow_from_reserve',
          value: id
        }).appendTo(form);
      }
      form.submit();
    }
  });
});

</script>
</body>
</html>
