<?php
include('db-connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM courses_enrolled WHERE fld_indx_enrolled = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

header("Location: index.php?page=enrollments");
exit;
?>