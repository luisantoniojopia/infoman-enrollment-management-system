<?php
include('header.php');
include('navigation.php');
include('db-connect.php');

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $std_number = $_POST['std_number'];
    $course_code = $_POST['course_code'];
    
    // Check if enrollment already exists
    $check = $conn->prepare("SELECT * FROM courses_enrolled WHERE std_number = ? AND course_code = ?");
    $check->bind_param("ii", $std_number, $course_code);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "This student is already enrolled in this course!";
    } else {
        // Insert new enrollment
        $stmt = $conn->prepare("INSERT INTO courses_enrolled (std_number, course_code) VALUES (?, ?)");
        $stmt->bind_param("ii", $std_number, $course_code);
        
        if ($stmt->execute()) {
            // Get the last inserted ID
            $new_id = $conn->insert_id;
            $success = "Enrollment added successfully!";
            
            // Redirect to view page with the new entry
            header("Location: index.php?page=enrollments");
            exit();
        } else {
            $error = "Error adding enrollment: " . $conn->error;
        }
    }
}

// Get students who haven't enrolled in all courses
$students = $conn->query("
    SELECT sl.fld_indx_std, sl.std_number, CONCAT(sl.std_last_name, ', ', sl.std_first_name) AS student_name 
    FROM students_list sl
    WHERE sl.fld_indx_std NOT IN (
        SELECT ce.std_number 
        FROM courses_enrolled ce
        GROUP BY ce.std_number
        HAVING COUNT(ce.course_code) = (SELECT COUNT(*) FROM courses_offered)
    )
    ORDER BY sl.std_last_name
");

// Get all courses
$all_courses = $conn->query("SELECT fld_indx_courses, course_code, course_name FROM courses_offered ORDER BY course_code");

// Function to get available courses for a student
function getAvailableCourses($conn, $std_number) {
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
    return $stmt->get_result();
}
?>

<div class="content">
    <div class="form-header">
        <h2>Add New Enrollment</h2>
        <a href="index.php?page=enrollments" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <?php if ($error): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>

        <form method="post" id="enrollmentForm">
            <div class="form-group">
                <label for="std_number">Student:</label>
                <select id="std_number" name="std_number" required onchange="updateCourseDropdown()">
                    <option value="">Select Student</option>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <option value="<?= $student['fld_indx_std'] ?>" <?= isset($_POST['std_number']) && $_POST['std_number'] == $student['fld_indx_std'] ? 'selected' : '' ?>>
                            <?= $student['std_number'] ?> - <?= $student['student_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="course_code">Course:</label>
                <select id="course_code" name="course_code" required>
                    <option value="">Select Course</option>
                    <?php 
                    if (isset($_POST['std_number'])) {
                        $available_courses = getAvailableCourses($conn, $_POST['std_number']);
                        while($course = $available_courses->fetch_assoc()): ?>
                            <option value="<?= $course['fld_indx_courses'] ?>" <?= isset($_POST['course_code']) && $_POST['course_code'] == $course['fld_indx_courses'] ? 'selected' : '' ?>>
                                <?= $course['course_code'] ?> - <?= $course['course_name'] ?>
                            </option>
                        <?php endwhile;
                    } else {
                        while($course = $all_courses->fetch_assoc()): ?>
                            <option value="<?= $course['fld_indx_courses'] ?>">
                                <?= $course['course_code'] ?> - <?= $course['course_name'] ?>
                            </option>
                        <?php endwhile;
                    }
                    ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-green">Save Enrollment</button>
                <button type="reset" class="btn btn-gray">Clear Form</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateCourseDropdown() {
    const studentId = document.getElementById('std_number').value;
    if (!studentId) return;
    
    fetch('get-available-courses.php?std_number=' + studentId)
        .then(response => response.json())
        .then(courses => {
            const courseSelect = document.getElementById('course_code');
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            
            courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.fld_indx_courses;
                option.textContent = `${course.course_code} - ${course.course_name}`;
                courseSelect.appendChild(option);
            });
        });
}
</script>