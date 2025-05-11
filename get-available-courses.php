<?php
include('db-connect.php');

header('Content-Type: application/json');

if (isset($_GET['std_number'])) {
    $std_number = (int)$_GET['std_number'];
    
    $stmt = $conn->prepare("
        SELECT co.fld_indx_courses, co.course_code, co.course_name 
        FROM courses_offered co
        WHERE co.fld_indx_courses NOT IN (
            SELECT course_code FROM courses_enrolled WHERE std_number = ?
        )
        ORDER BY co.course_code
    ");
    $stmt->bind_param("i", $std_number);
    $stmt->execute();
    
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}
?>