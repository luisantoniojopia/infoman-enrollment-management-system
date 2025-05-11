<?php
include('header.php');
include('navigation.php');
include('db-connect.php');

// 1. Get enrollment ID
$enrollment_id = $_GET['id'] ?? null;
if (!$enrollment_id) die("Enrollment ID not specified");

// 2. Fetch current enrollment data with names for display
$stmt_display = $conn->prepare("
    SELECT ce.*, sl.std_number, sl.std_last_name, sl.std_first_name,
           co.course_code, co.course_name 
    FROM courses_enrolled ce
    JOIN students_list sl ON ce.std_number = sl.fld_indx_std
    JOIN courses_offered co ON ce.course_code = co.fld_indx_courses
    WHERE ce.fld_indx_enrolled = ?
");
$stmt_display->bind_param("i", $enrollment_id);
$stmt_display->execute();
$display_data = $stmt_display->get_result()->fetch_assoc();

// 3. Fetch basic enrollment data for dropdown logic
$stmt_logic = $conn->prepare("SELECT * FROM courses_enrolled WHERE fld_indx_enrolled = ?");
$stmt_logic->bind_param("i", $enrollment_id);
$stmt_logic->execute();
$logic_data = $stmt_logic->get_result()->fetch_assoc();

// 4. Get available courses (only those not enrolled by this student)
$available_courses = $conn->prepare("
    SELECT co.fld_indx_courses, co.course_code, co.course_name 
    FROM courses_offered co
    WHERE co.fld_indx_courses NOT IN (
        SELECT course_code FROM courses_enrolled 
        WHERE std_number = ? AND fld_indx_enrolled != ?
    )
    ORDER BY co.course_code
");
$available_courses->bind_param("ii", $logic_data['std_number'], $enrollment_id);
$available_courses->execute();
$courses_result = $available_courses->get_result();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_course_id = $_POST['course_id'];
    $update = $conn->prepare("UPDATE courses_enrolled SET course_code = ? WHERE fld_indx_enrolled = ?");
    $update->bind_param("ii", $new_course_id, $enrollment_id);
    
    if ($update->execute()) {
        header("Location: view-enrollment.php?id=" . $enrollment_id);
        exit;
    } else {
        $error = "Error updating enrollment: " . $conn->error;
    }
}
?>

<!-- HTML Form -->
<div class="content">
    <div class="form-header">
        <h2>Edit Course Enrollment</h2>
        <a href="view-enrollment.php?id=<?= $enrollment_id ?>" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <?php if (isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <!-- Student Info (Display Only) -->
            <div class="form-group">
                <label>Student:</label>
                <input type="text" class="form-control" readonly
                    value="<?= htmlspecialchars($display_data['std_number']) ?> - <?= htmlspecialchars($display_data['std_last_name']) ?>, <?= htmlspecialchars($display_data['std_first_name']) ?>">
            </div>

            <!-- Current Course (Display Only) -->
            <div class="form-group">
                <label>Current Course:</label>
                <input type="text" class="form-control" readonly
                    value="<?= htmlspecialchars($display_data['course_code']) ?> - <?= htmlspecialchars($display_data['course_name']) ?>">
            </div>

            <!-- Course Dropdown (Only shows NOT enrolled courses) -->
            <div class="form-group">
                <label for="course_id">Change to Course:</label>
                <select id="course_id" name="course_id" required class="form-control">
                    <option value="">-- Select New Course --</option>
                    <?php while($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?= $course['fld_indx_courses'] ?>">
                            <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-green" 
                        onclick="return confirm('Confirm Enrollment Update:\n\nStudent: <?= htmlspecialchars($display_data['std_number']) ?> - <?= htmlspecialchars($display_data['std_last_name']) ?>, <?= htmlspecialchars($display_data['std_first_name']) ?>\nCurrent Course: <?= htmlspecialchars($display_data['course_code']) ?> - <?= htmlspecialchars($display_data['course_name']) ?>\n\nAre you sure you want to update this enrollment?')">
                    Update Enrollment
                </button>
                
                <button type="button" class="btn btn-gray" 
                        onclick="if(confirm('Cancel Enrollment Update?\n\nStudent: <?= htmlspecialchars($display_data['std_number']) ?> - <?= htmlspecialchars($display_data['std_last_name']) ?>, <?= htmlspecialchars($display_data['std_first_name']) ?>\nCurrent Course: <?= htmlspecialchars($display_data['course_code']) ?> - <?= htmlspecialchars($display_data['course_name']) ?>\n\nAll changes will be lost.')) { window.location.href='view-enrollment.php?id=<?= $enrollment_id ?>'; }">
                    Cancel Update
                </button>
            </div>
        </form>
    </div>
</div>