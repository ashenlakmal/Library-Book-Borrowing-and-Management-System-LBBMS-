<?php
session_start();
require_once("../../other/php/connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$enrollment = $_SESSION['user'];

function calculateFine($borrowDate, $returnDate) {
    $today = new DateTime();
    $dueDate = new DateTime($returnDate);

    if ($today <= $dueDate) {
        return 0; // No fine if not late
    }

    $diff = $dueDate->diff($today);
    $daysLate = $diff->days;

    // Breakdown late days into months, weeks, and days
    $months = floor($daysLate / 30);
    $daysLate %= 30;

    $weeks = floor($daysLate / 7);
    $days = $daysLate % 7;

    $fine = ($months * 50) + ($weeks * 10) + ($days * 5);

    return $fine;
}

$query = mysqli_query($connect, "
    SELECT borrow_id, borrow_date, return_date 
    FROM book_record 
    WHERE student_id = '$enrollment' AND state IN ('ACTIVE', 'DELIVERED')
");

$totalFine = 0;
$books = [];

while ($row = mysqli_fetch_assoc($query)) {
    $fine = calculateFine($row['borrow_date'], $row['return_date']);
    $totalFine += $fine;

    // Update the fine in the database live
    $borrow_id = $row['borrow_id'];
    $updateQuery = mysqli_query($connect, "
        UPDATE book_record 
        SET fines = $fine 
        WHERE borrow_id = '$borrow_id'
    ");
    if (!$updateQuery) {
        // Optional: Log error or handle it
        error_log("Failed to update fine for borrow_id $borrow_id: " . mysqli_error($connect));
    }

    $books[] = [
        'borrow_id' => $borrow_id,
        'fine' => $fine
    ];
}

echo json_encode([
    'totalFine' => $totalFine,
    'books' => $books
]);
