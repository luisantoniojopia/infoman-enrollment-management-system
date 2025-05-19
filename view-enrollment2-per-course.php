<?php
include('header.php');
include('navigation.php');
include('db-connect.php');

// Debug: Check if ID exists
if (!isset($_GET['id'])) {
    die("Debug: No ID parameter received. URL should be view-enrollment.php?id=123");
}

$enrollment_id = intval($_GET['id']); // Convert to integer for safety

// Debug output (remove after testing)
echo "<!-- Debug: Received ID: ".$enrollment_id." -->";

$stmt = $conn->prepare("SELECT ce.*, sl.std_number, sl.std_last_name, sl.std_first_name,
                       co.course_code, co.course_name 
                       FROM courses_enrolled ce
                       JOIN students_list sl ON ce.std_number = sl.fld_indx_std
                       JOIN courses_offered co ON ce.course_code = co.fld_indx_courses
                       WHERE ce.fld_indx_enrolled = ?");
$stmt->bind_param("i", $enrollment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Enrollment not found for ID: ".$enrollment_id);
}

$enrollment = $result->fetch_assoc();
?>

<div class="content">
    <div class="form-header">
        <h2>View Enrollment Details</h2>
        <a href="view-enrollment2.php?id=<?php echo $enrollment_id; ?>" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <div class="view-details">
            <div class="detail-row">
                <span class="detail-label">Student Number:</span>
                <span class="detail-value"><?= $enrollment['std_number'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Student Name:</span>
                <span class="detail-value"><?= $enrollment['std_last_name'] ?>, <?= $enrollment['std_first_name'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course Code:</span>
                <span class="detail-value"><?= $enrollment['course_code'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course Name:</span>
                <span class="detail-value"><?= $enrollment['course_name'] ?></span>
            </div>
        </div>

        <div class="form-actions">
            <form method="get" action="edit-enrollment2.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $enrollment['fld_indx_enrolled'] ?>">
                <button type="submit" class="btn btn-green" 
                        onclick="return confirm('Are you sure you want to edit enrollment for <?= htmlspecialchars($enrollment['std_number']) ?>: <?= htmlspecialchars($enrollment['std_last_name']) ?>, <?= htmlspecialchars($enrollment['std_first_name']) ?> in <?= htmlspecialchars($enrollment['course_code']) ?> (ID: <?= $enrollment['fld_indx_enrolled'] ?>)?')">
                    Edit Enrollment
                </button>
            </form>
            <form method="post" action="delete-enrollment.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $enrollment['fld_indx_enrolled'] ?>">
                <button type="submit" class="btn btn-red" 
                        onclick="return confirm('Are you sure you want to PERMANENTLY DELETE enrollment for <?= htmlspecialchars($enrollment['std_number']) ?>: <?= htmlspecialchars($enrollment['std_last_name']) ?>, <?= htmlspecialchars($enrollment['std_first_name']) ?> in <?= htmlspecialchars($enrollment['course_code']) ?> (ID: <?= $enrollment['fld_indx_enrolled'] ?>)?\n\nThis action cannot be undone!')">
                    Delete Enrollment
                </button>
            </form>
        </div>
    </div>
</div>