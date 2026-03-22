<?php
session_start();
require_once("../../other/php/connect.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    echo "<script>alert('Please log in as Staff.'); window.location.href='../../other/login.html';</script>";
    exit;
}

// Handle search inputs (sanitize to prevent SQL injection)
$searchEnrollment = isset($_GET['enroll']) ? mysqli_real_escape_string($connect, trim($_GET['enroll'])) : '';
$searchSerial = isset($_GET['serial']) ? mysqli_real_escape_string($connect, trim($_GET['serial'])) : '';

// Build search filter
$searchFilter = "";
if ($searchEnrollment !== '') {
    $searchFilter .= " AND s.Enrollment LIKE '%$searchEnrollment%' ";
}
if ($searchSerial !== '') {
    $searchFilter .= " AND b.serialNumber LIKE '%$searchSerial%' ";
}

// Fetch Pending
$pendingQuery = mysqli_query($connect, "
    SELECT br.*, b.title, b.bookphoto, s.profilephoto, s.Enrollment, b.serialNumber
    FROM book_record br
    JOIN book b ON br.book_id = b.serialNumber
    JOIN student s ON br.student_id = s.Enrollment
    WHERE (br.state IS NULL OR br.state='ACTIVE') $searchFilter
    ORDER BY br.borrow_date DESC
");

// Delivered
$deliveredQuery = mysqli_query($connect, "
    SELECT br.*, b.title, b.bookphoto, s.profilephoto, s.Enrollment, b.serialNumber
    FROM book_record br
    JOIN book b ON br.book_id = b.serialNumber
    JOIN student s ON br.student_id = s.Enrollment
    WHERE br.state='DELIVERED' $searchFilter
    ORDER BY br.borrow_date DESC
");

// Returned
$returnedQuery = mysqli_query($connect, "
    SELECT br.*, b.title, b.bookphoto, s.profilephoto, s.Enrollment, b.serialNumber
    FROM book_record br
    JOIN book b ON br.book_id = b.serialNumber
    JOIN student s ON br.student_id = s.Enrollment
    WHERE br.state='RETURNED' $searchFilter
    ORDER BY br.return_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
body { background:#eef3f8; font-family:'Segoe UI'; }
.navbar { background:#007BFF; }
.navbar-brand { color:white !important; font-weight:bold; }
.table img { width:50px; height:50px; object-fit:cover; border-radius:5px; }
.tab-pane { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
tr.pending-row { background:#fff8dc !important; } /* Light Yellow */
tr.delivered-row { background:#ffe5e5 !important; } /* Light Red */
tr.returned-row { background:#e8ffe8 !important; } /* Light Green */
.profile-pic { width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:8px; }

/* Search bar styling */
.search-container {
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
}
.search-container input {
    flex: 1;
}

</style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand">Book Borrowing Management</a>
    </div>
</nav>

<!-- Back & Dashboard Buttons -->
<div class="container my-4">
  <div class="d-flex justify-content-between">
    <a href="../index.php" class="btn btn-outline-primary">
      ← Back to Home
    </a>
    <a href="managebooks.php" class="btn btn-outline-success">
      Go to Books Management →
    </a>
  </div>
</div>


<div class="container my-4">

    <!-- Search Bars -->
    <form method="GET" class="search-container" id="searchForm">
        <input type="text" name="enroll" id="searchEnrollment" placeholder="Search by Enrollment" value="<?= htmlspecialchars($searchEnrollment) ?>" class="form-control" autocomplete="off">
        <input type="text" name="serial" id="searchSerial" placeholder="Search by Serial Number" value="<?= htmlspecialchars($searchSerial) ?>" class="form-control" autocomplete="off">
        <button type="submit" class="btn btn-primary">Search</button>
        <button type="button" id="clearSearch" class="btn btn-secondary">Clear</button>
    </form>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item" ><a class="nav-link active" data-bs-toggle="tab" href="#pending" style="color:#8B8000;">Pending</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#delivered" style="color:#8B0000;">Delivered</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#returned" style="color:#006400;">Returned</a></li>
    </ul>

    <div class="tab-content">

        <!-- Pending -->
        <div class="tab-pane fade show active" id="pending">
            <table id="pendingTable" class="table table-bordered table-striped">
                <thead class="table-warning">
                    <tr>
                        <th>Book</th>
                        <th>Serial Number</th>
                        <th>Student</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row=mysqli_fetch_assoc($pendingQuery)): ?>
                    <tr class="pending-row" id="row-<?= $row['borrow_id'] ?>">
                        <td><img src="../../uploads/<?= $row['bookphoto'] ?>" alt="Book Photo"> <?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['serialNumber']) ?></td>
                            <td>
                            <?php
                            $photo = $row['profilephoto'];
                            // If DB has only filename, uncomment next line:
                            // $photo = "../../uploads/" . $photo;

                            if (!empty($photo) && file_exists($photo)) {
                                echo '<img src="' . htmlspecialchars($photo) . '" class="profile-pic" alt="Student Photo">';
                            }
                            ?>
                            <?= htmlspecialchars($row['Enrollment']) ?>
                        </td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['return_date'] ?></td>
                        <td>
                            <button class="btn btn-warning deliver-btn" 
                                data-id="<?= $row['borrow_id'] ?>" 
                                data-book="<?= htmlspecialchars($row['title']) ?>" 
                                data-student="<?= htmlspecialchars($row['Enrollment']) ?>">Deliver</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Delivered -->
        <div class="tab-pane fade" id="delivered">
            <table id="deliveredTable" class="table table-bordered table-striped">
                <thead class="table-danger">
                    <tr>
                        <th>Book</th>
                        <th>Serial Number</th>
                        <th>Student</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Fines</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row=mysqli_fetch_assoc($deliveredQuery)): ?>
                    <tr class="delivered-row" id="row-<?= $row['borrow_id'] ?>">
                        <td><img src="../../uploads/<?= $row['bookphoto'] ?>" alt="Book Photo"> <?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['serialNumber']) ?></td>
                            <td>
                            <?php
                            $photo = $row['profilephoto'];
                            // If DB has only filename, uncomment next line:
                            // $photo = "../../uploads/" . $photo;

                            if (!empty($photo) && file_exists($photo)) {
                                echo '<img src="' . htmlspecialchars($photo) . '" class="profile-pic" alt="Student Photo">';
                            }
                            ?>
                            <?= htmlspecialchars($row['Enrollment']) ?>
                        </td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['return_date'] ?></td>
                        <td>Rs. <?= number_format($row['fines'] ?? 0, 2) ?></td>
                        <td><button class="btn btn-danger return-btn" data-id="<?= $row['borrow_id'] ?>">Return</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Returned -->
        <div class="tab-pane fade" id="returned">
            <table id="returnedTable" class="table table-bordered table-striped">
                <thead class="table-success">
                    <tr>
                        <th>Book</th>
                        <th>Serial Number</th>
                        <th>Student</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row=mysqli_fetch_assoc($returnedQuery)): ?>
                    <tr class="returned-row" id="row-<?= $row['borrow_id'] ?>">
                        <td><img src="../../uploads/<?= $row['bookphoto'] ?>" alt="Book Photo"> <?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['serialNumber']) ?></td>
                        <td>
                            <?php
                            $photo = $row['profilephoto'];
                            // If DB has only filename, uncomment next line:
                            // $photo = "../../uploads/" . $photo;

                            if (!empty($photo) && file_exists($photo)) {
                                echo '<img src="' . htmlspecialchars($photo) . '" class="profile-pic" alt="Student Photo">';
                            }
                            ?>
                            <?= htmlspecialchars($row['Enrollment']) ?>
                        </td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['return_date'] ?></td>
                        <td>Rs. <?= number_format($row['fines'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="deliverModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-warning"><h5>Confirm Delivery</h5></div>
        <div class="modal-body" id="deliverText"></div>
        <div class="modal-footer">
            <input type="hidden" id="deliverBorrowId">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-warning" id="confirmDeliver">Yes, Deliver</button>
        </div>
    </div></div>
</div>

<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-danger text-white"><h5>Confirm Return</h5></div>
        <div class="modal-body">
            Enter Fine Amount: <input type="number" id="fineAmount" class="form-control" value="0" step="0.01">
        </div>
        <div class="modal-footer">
            <input type="hidden" id="returnBorrowId">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-danger" id="confirmReturn">Return Book</button>
        </div>
    </div></div>
</div>



<script>
$(document).ready(function(){
    // Initialize DataTables for each table
    $('#pendingTable, #deliveredTable, #returnedTable').DataTable({
        "order": [], // Disable initial ordering to preserve server order
        "pageLength": 10,
        "lengthChange": false
    });

    var deliverModal = new bootstrap.Modal(document.getElementById('deliverModal'));
    var returnModal = new bootstrap.Modal(document.getElementById('returnModal'));

    // Deliver button click
    $('.deliver-btn').click(function(){
        let book = $(this).data('book');
        let student = $(this).data('student');
        $('#deliverText').html(`Deliver "<b>${book}</b>" to student <b>${student}</b>?`);
        $('#deliverBorrowId').val($(this).data('id'));
        deliverModal.show();
    });

    // Confirm Deliver
    $('#confirmDeliver').click(function(){
        let borrowId = $('#deliverBorrowId').val();
        $.post('staffdashboard_action.php', { action:'deliver', borrow_id: borrowId }, function(response){
            if(response.success){
                deliverModal.hide();
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload(); // Reload to update tables with latest data
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }, 'json');
    });

    // Return button click
    $('.return-btn').click(function(){
        $('#returnBorrowId').val($(this).data('id'));
        $('#fineAmount').val('0');
        returnModal.show();
    });

    // Confirm Return
    $('#confirmReturn').click(function(){
        let borrowId = $('#returnBorrowId').val();
        let fine = $('#fineAmount').val();
        $.post('staffdashboard_action.php', { action:'return', borrow_id: borrowId, fine: fine }, function(response){
            if(response.success){
                returnModal.hide();
                Swal.fire('Returned!', response.message, 'success').then(() => {
                    location.reload(); // Reload for latest data
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }, 'json');
    });

    // Clear search inputs
    $('#clearSearch').click(function(){
        $('#searchEnrollment').val('');
        $('#searchSerial').val('');
        $('#searchForm').submit();
    });
});
</script>
</body>
</html>
