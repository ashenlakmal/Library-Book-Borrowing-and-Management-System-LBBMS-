<?php
require_once("../../other/php/connect.php");

$keyword = $_POST['keyword'] ?? '';
$type = $_POST['type'] ?? 'title';
$keyword = mysqli_real_escape_string($connect, $keyword);

$output = "";

if ($keyword !== '') {
    if ($type === 'author') {
        $query = "SELECT book.*, 
            CASE 
                WHEN EXISTS (SELECT 1 FROM reserve_record WHERE book_id = book.serialNumber AND state='ACTIVE') 
                THEN 'Currently Unavailable' 
                ELSE book.availability 
            END AS availability_status
        FROM book WHERE author LIKE '%$keyword%' LIMIT 10";
    } else {
        $query = "SELECT book.*, 
            CASE 
                WHEN EXISTS (SELECT 1 FROM reserve_record WHERE book_id = book.serialNumber AND state='ACTIVE') 
                THEN 'Currently Unavailable' 
                ELSE book.availability 
            END AS availability_status
        FROM book WHERE title LIKE '%$keyword%' LIMIT 10";
    }

    $result = mysqli_query($connect, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status = strtolower($row['availability_status']);
            $circleColor = $status === 'available' ? 'green' : ($status === 'not available' ? 'orange' : 'red');

            $output .= '
                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                   data-bs-toggle="modal" data-bs-target="#bookModal' . $row['serialNumber'] . '">
                  <div class="d-flex align-items-center">
                    <img src="../../uploads/' . htmlspecialchars($row['bookphoto']) . '" 
                         style="height:40px;width:40px;object-fit:cover;margin-right:10px;border-radius:5px;">
                    <span>' . htmlspecialchars($row['title']) . ' - ' . htmlspecialchars($row['author']) . '</span>
                  </div>
                  <div class="status-circle" style="background:' . $circleColor . ';"></div>
                </a>
            ';
        }
    } else {
        $output = '<div class="list-group-item text-center text-muted">No results found</div>';
    }
}

echo $output;
?>
