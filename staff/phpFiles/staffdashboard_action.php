<?php
session_start();
require_once("../../other/php/connect.php");
header('Content-Type: application/json');

if (!isset($_POST['action']) || !isset($_POST['borrow_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid Request']);
    exit;
}

$action = $_POST['action'];
$borrowId = intval($_POST['borrow_id']);

if ($action === 'deliver') {
    $update = mysqli_query($connect, "UPDATE book_record SET state='DELIVERED' WHERE borrow_id='$borrowId'");
    if ($update) {
        mysqli_query($connect, "INSERT INTO delivered (borrow_id, delivered_date) VALUES ('$borrowId', NOW())");
        echo json_encode(['success'=>true, 'message'=>'Book marked as Delivered!']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Failed to update record']);
    }
    exit;
}

if ($action === 'return') {
    $fine = isset($_POST['fine']) ? floatval($_POST['fine']) : 0;
    $update = mysqli_query($connect, "UPDATE book_record SET state='RETURNED', fines='$fine', return_date=NOW() WHERE borrow_id='$borrowId'");
    if ($update) {
        mysqli_query($connect, "INSERT INTO returned (borrow_id, returned_date) VALUES ('$borrowId', NOW())");

        $bookInfo = mysqli_fetch_assoc(mysqli_query($connect, "SELECT book_id FROM book_record WHERE borrow_id='$borrowId'"));
        $bookId = $bookInfo['book_id'];

        $reserveCheck = mysqli_query($connect, "SELECT * FROM reserve_record WHERE book_id='$bookId' AND state='ACTIVE'");
        if (mysqli_num_rows($reserveCheck) == 0) {
            mysqli_query($connect, "UPDATE book SET availability='Available' WHERE serialNumber='$bookId'");
        }
        echo json_encode(['success'=>true, 'message'=>'Book Returned Successfully!']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Failed to update record']);
    }
    exit;
}

echo json_encode(['success'=>false, 'message'=>'Unknown Action']);
