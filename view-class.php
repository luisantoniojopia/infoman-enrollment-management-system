<?php
include('header.php');
include('navigation.php');
include('db-connect.php');

if (!isset($_GET['id'])) {
    die("No ID parameter received.");
}

$class_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT cl.*, sl.std_number, sl.std_last_name, sl.std_first_name,
                       co.course_code, co.course_name 
                       FROM class_list cl
                       JOIN students_list sl ON cl.std_number = sl.fld_indx_std
                       JOIN courses_offered co ON cl.course_code = co.fld_indx_courses
                       WHERE cl.fld_index_class_list = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Class record not found for ID: ".$class_id);
}

$class = $result->fetch_assoc();
?>

<div class="content">
    <div class="form-header">
        <h2>View Class Details</h2>
        <a href="index.php?page=classes" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <div class="view-details">
            <div class="detail-row">
                <span class="detail-label">Student Number:</span>
                <span class="detail-value"><?= $class['std_number'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Student Name:</span>
                <span class="detail-value"><?= $class['std_last_name'] ?>, <?= $class['std_first_name'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course Code:</span>
                <span class="detail-value"><?= $class['course_code'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course Name:</span>
                <span class="detail-value"><?= $class['course_name'] ?></span>
            </div>
        </div>
    </div>
</div>