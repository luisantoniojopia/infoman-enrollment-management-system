<?php
include('header.php');
include('navigation.php');
include('db-connect.php');

if (!isset($_GET['id'])) {
    die("No ID parameter received. URL should be view-enrollment2.php?id=123");
}

$student_id = intval($_GET['id']);

// Get student basic info
$student_stmt = $conn->prepare("SELECT std_number, std_last_name, std_first_name 
                              FROM students_list 
                              WHERE fld_indx_std = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows == 0) {
    die("Student not found for ID: ".$student_id);
}

$student = $student_result->fetch_assoc();

// Get all enrolled courses for this student
$courses_stmt = $conn->prepare("SELECT ce.fld_indx_enrolled AS enrollment_id,
                               co.course_code, 
                               co.course_name,
                               co.course_units
                               FROM courses_enrolled ce
                               JOIN courses_offered co ON ce.course_code = co.fld_indx_courses
                               WHERE ce.std_number = ?");
$courses_stmt->bind_param("i", $student_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
?>

<div class="content">
    <div class="form-header">
        <h2>Student Enrollment</h2>
        <a href="index.php?page=enrollments2" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <h3>Enrolled Courses</h3><br>
        <div class="view-details">
            <div class="detail-row">
                <span class="detail-label">Student Number:</span>
                <span class="detail-value"><?= htmlspecialchars($student['std_number']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Student Name:</span>
                <span class="detail-value"><?= htmlspecialchars($student['std_last_name']) ?>, <?= htmlspecialchars($student['std_first_name']) ?></span>
            </div>
        </div>

        <div class="enrolled-courses">
            <table class="courses-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Units</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses_result->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No courses enrolled</td>
                        </tr>
                    <?php else: ?>
                        <?php while($course = $courses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $course['enrollment_id'] ?></td>
                                <td><?= htmlspecialchars($course['course_code']) ?></td>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td><?= $course['course_units'] ?></td>
                                <td>
                                    <a href="view-enrollment2-per-course.php?id=<?= $course['enrollment_id'] ?>" class="btn btn-gray">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <a href="add-enrollment2.php?student_id=<?= $student_id ?>" class="btn btn-green">Add Course</a>
        </div>
    </div>
</div>