<?php
include('db-connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;

    if ($enrollment_id && $student_id) {
        $stmt = $conn->prepare("DELETE FROM courses_enrolled WHERE fld_indx_enrolled = ?");
        $stmt->bind_param("i", $enrollment_id);
        $stmt->execute();
    }
}

// Redirect back to the per-course view for the same student
header("Location: view-enrollment2.php?id=" . urlencode($student_id));
exit;
?>
