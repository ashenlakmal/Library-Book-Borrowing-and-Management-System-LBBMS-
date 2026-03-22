<?php
session_start();
require_once("../../other/php/connect.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    echo "<script>alert('Please log in as Staff.'); window.location.href='../../other/login.html';</script>";
    exit;
}

// Handle Delete Request (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteBook'])) {
    $id = mysqli_real_escape_string($connect, $_POST['deleteBook']);

    // Check ownership
    $check = mysqli_query($connect, "SELECT * FROM book WHERE serialNumber='$id' AND uploadedUser='{$_SESSION['user']}'");
    if ($check && mysqli_num_rows($check) > 0) {

        // Check ACTIVE or DELIVERED in book_record
        $borrowCheck = mysqli_query($connect, "SELECT state FROM book_record WHERE book_id='$id' AND state IN ('ACTIVE', 'DELIVERED')");
        if ($borrowCheck && mysqli_num_rows($borrowCheck) > 0) {
            $borrowState = mysqli_fetch_assoc($borrowCheck)['state'];
            if ($borrowState === 'ACTIVE') {
                echo json_encode(['success' => false, 'message' => 'Cannot delete: A student has borrowed this book.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cannot delete: Please ensure the book is returned before deleting.']);
            }
            exit;
        }

        // Check ACTIVE in reserve_record
        $reserveCheck = mysqli_query($connect, "SELECT * FROM reserve_record WHERE book_id='$id' AND state='ACTIVE'");
        if ($reserveCheck && mysqli_num_rows($reserveCheck) > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete: A student has reserved this book.']);
            exit;
        }

        // All clear - delete
        mysqli_query($connect, "DELETE FROM book WHERE serialNumber='$id'");
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully!']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found or unauthorized']);
    }
    exit;
}


// Handle Update Request (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateBook'])) {
    $serial = mysqli_real_escape_string($connect, $_POST['serialNumber']);
    $title = mysqli_real_escape_string($connect, $_POST['title']);
    $author = mysqli_real_escape_string($connect, $_POST['author']);
    $category = mysqli_real_escape_string($connect, $_POST['category']);
    $availability = mysqli_real_escape_string($connect, $_POST['availability']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);

    // Verify ownership & get current photo
    $result = mysqli_query($connect, "SELECT bookphoto FROM book WHERE serialNumber='$serial' AND uploadedUser='{$_SESSION['user']}'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found or unauthorized']);
        exit;
    }
    $row = mysqli_fetch_assoc($result);
    $photo = $row['bookphoto'];

    // Handle photo upload if new photo provided
    if (!empty($_FILES['bookphoto']['name'])) {
        $allowedTypes = ['image/jpeg','image/jpg','image/png','image/gif'];
        if (in_array($_FILES['bookphoto']['type'], $allowedTypes)) {
            $fileName = time() . "_" . basename($_FILES['bookphoto']['name']);
            $targetFile = "../../uploads/" . $fileName;
            if (move_uploaded_file($_FILES['bookphoto']['tmp_name'], $targetFile)) {
                // Optionally delete old photo file if needed
                if ($photo && file_exists("../../uploads/$photo")) {
                    @unlink("../../uploads/$photo");
                }
                $photo = $fileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
            exit;
        }
    }

    // Update the book info
    $update = mysqli_query($connect, "UPDATE book SET 
        title='$title', 
        author='$author', 
        category='$category', 
        availability='$availability', 
        description='$description', 
        bookphoto='$photo' 
        WHERE serialNumber='$serial' AND uploadedUser='{$_SESSION['user']}'");

    if ($update) {
        echo json_encode(['success' => true, 'message' => 'Book updated successfully!', 'photo' => $photo]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: '.mysqli_error($connect)]);
    }
    exit;
}

// Fetch books uploaded by the logged-in user
$query = mysqli_query($connect, "SELECT * FROM book WHERE uploadedUser='{$_SESSION['user']}' ORDER BY serialNumber DESC");
if (!$query) {
    die("Query Failed: " . mysqli_error($connect));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Your Books</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<style>
body {
    background:#f8f9fa;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.navbar {
    background:#007BFF;
}
.navbar-brand {
    color:white !important;
    font-weight:bold;
}
.table img {
    width:50px;
    height:50px;
    object-fit:cover;
    border-radius:5px;
}
.search-bar {
    margin-bottom:20px;
}
</style>
</head>
<body>
<nav class="navbar mb-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand">Manage Your Books</a>
    </div>
</nav>
<!-- Back & Dashboard Buttons -->
<div class="container my-4">
  <div class="d-flex justify-content-between">
    <a href="../index.php" class="btn btn-outline-primary">
      ← Back to Home
    </a>
    <a href="staffdashboard.php" class="btn btn-outline-success">
      Go to Book Borrowing Management →
    </a>
  </div>
</div>

<div class="container">
    <h4>Your Uploaded Books</h4>
    <div class="search-bar">
        <input type="text" id="bookSearch" class="form-control" placeholder="Search by Title, Author, or Category" />
    </div>

    <table id="booksTable" class="table table-bordered table-striped">
        <thead class="table-info">
            <tr>
                <th>Cover</th>
                <th>Serial Number</th>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Availability</th>
                <th>Description</th>
                <th style="min-width:120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
            <tr id="book-<?= htmlspecialchars($row['serialNumber']) ?>">
                <td><img src="../../uploads/<?= htmlspecialchars($row['bookphoto']) ?>" alt="cover" /></td>
                <td><?= htmlspecialchars($row['serialNumber']) ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['author']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= htmlspecialchars($row['availability']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>
                    <button class="btn btn-warning btn-sm edit-btn"
                        data-id="<?= htmlspecialchars($row['serialNumber']) ?>"
                        data-title="<?= htmlspecialchars($row['title']) ?>"
                        data-author="<?= htmlspecialchars($row['author']) ?>"
                        data-category="<?= htmlspecialchars($row['category']) ?>"
                        data-availability="<?= htmlspecialchars($row['availability']) ?>"
                        data-description="<?= htmlspecialchars($row['description']) ?>"
                        data-photo="<?= htmlspecialchars($row['bookphoto']) ?>">
                        Edit
                    </button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($row['serialNumber']) ?>">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form enctype="multipart/form-data" id="editForm" class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="editModalLabel">Edit Book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="serialNumber" id="editSerial" />
        <input type="hidden" name="updateBook" value="1" />
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="editTitle">Title</label>
                <input type="text" name="title" id="editTitle" class="form-control" required />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="editAuthor">Author</label>
                <input type="text" name="author" id="editAuthor" class="form-control" required />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="editCategory">Category</label>
                <input type="text" name="category" id="editCategory" class="form-control" required />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="editAvailability">Availability</label>
                <input type="text" name="availability" id="editAvailability" class="form-control" required />
            </div>
            <div class="col-12">
                <label class="form-label" for="editDescription">Description</label>
                <textarea name="description" id="editDescription" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Current Image</label><br />
                <img id="editPhoto" src="" alt="Book Cover" width="100" style="border:1px solid #ddd; border-radius:5px;" />
                <input type="file" name="bookphoto" class="form-control mt-2" accept="image/*" />
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-info">Update Book</button>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
    var table = $('#booksTable').DataTable({
        "order": [[1, "desc"]],
        "columnDefs": [{
            "targets": [0, 7], // Disable ordering on image and action columns
            "orderable": false
        }]
    });

    // Search filter
    $('#bookSearch').on('keyup', function () {
        table.search(this.value).draw();
    });

    // Delete book with confirmation
    $('#booksTable').on('click', '.delete-btn', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete this book?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('managebooks.php', { deleteBook: id }, function (res) {
                    if (res.success) {
                        $('#book-' + id).fadeOut(800, function () {
                            table.row($(this)).remove().draw();
                        });
                        Swal.fire('Deleted!', res.message, 'success');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });

    // Open edit modal and populate form fields
    $('#booksTable').on('click', '.edit-btn', function () {
        var btn = $(this);
        $('#editSerial').val(btn.data('id'));
        $('#editTitle').val(btn.data('title'));
        $('#editAuthor').val(btn.data('author'));
        $('#editCategory').val(btn.data('category'));
        $('#editAvailability').val(btn.data('availability'));
        $('#editDescription').val(btn.data('description'));
        var photoPath = '../../uploads/' + btn.data('photo');
        $('#editPhoto').attr('src', photoPath);
        var modal = new bootstrap.Modal(document.getElementById('editModal'));
        modal.show();
    });

    // Handle edit form submission with AJAX
    $('#editForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'managebooks.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    Swal.fire('Updated!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'An error occurred while updating the book.', 'error');
            }
        });
    });
});
</script>
</body>
</html>
