<?php 
include('header.php');
include('navigation.php');
include('db-connect.php');

// Initialize variables
$success = '';
$error = '';

// Fetch course data
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php?page=courses");
    exit;
}

$result = $conn->query("SELECT * FROM courses_offered WHERE fld_indx_courses = $id");

if ($result->num_rows == 0) {
    $error = "Course not found!";
}

$course = $result->fetch_assoc();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $course_units = trim($_POST['course_units']);
    
    // Check if any values have changed
    $unchanged = true;
    if ($course['course_code'] != $course_code ||
        $course['course_name'] != $course_name ||
        $course['course_units'] != $course_units) {
        $unchanged = false;
    }
    
    if ($unchanged) {
        $error = "No changes were made to the course record.";
    } else {
        // Validation
        $valid = true;
        $validation_errors = [];
        
        // Validate course code (letters and numbers only)
        if (!preg_match("/^[a-zA-Z0-9]+$/", $course_code)) {
            $validation_errors[] = "Course code must contain only letters and numbers.";
            $valid = false;
        }
        
        // Validate course name (letters, spaces, and numbers)
        if (!preg_match("/^[a-zA-Z0-9 ]+$/", $course_name)) {
            $validation_errors[] = "Course name must contain only letters, numbers and spaces.";
            $valid = false;
        }
        
        // Validate course units (1-10)
        if (!is_numeric($course_units) || $course_units < 1 || $course_units > 10) {
            $validation_errors[] = "Course units must be between 1 and 10.";
            $valid = false;
        }
        
        if ($valid) {
            // Check if the new course code already exists (excluding the current course)
            $stmt = $conn->prepare("SELECT * FROM courses_offered WHERE course_code = ? AND fld_indx_courses != ?");
            $stmt->bind_param("si", $course_code, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                // Update course record
                $stmt = $conn->prepare("UPDATE courses_offered SET 
                    course_code = ?, 
                    course_name = ?, 
                    course_units = ? 
                    WHERE fld_indx_courses = ?");
                $stmt->bind_param("ssii", $course_code, $course_name, $course_units, $id);

                if ($stmt->execute()) {
                    $success = "Course record updated successfully!";
                    // Refresh the course data
                    $course['course_code'] = $course_code;
                    $course['course_name'] = $course_name;
                    $course['course_units'] = $course_units;
                } else {
                    $error = "Error updating record: " . $conn->error;
                }
            } else {
                $error = "Course code already exists!";
            }
        } else {
            $error = implode("<br>", $validation_errors);
        }
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    // First delete all enrollments for this course
    $deleteEnrollments = $conn->prepare("DELETE FROM courses_enrolled WHERE course_code = ?");
    $deleteEnrollments->bind_param("i", $id);
    $deleteEnrollments->execute();
    
    // Then delete the course
    $stmt = $conn->prepare("DELETE FROM courses_offered WHERE fld_indx_courses = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Course record and all related enrollments deleted successfully!";
        header("Location: index.php?page=courses");
        exit;
    } else {
        $error = "Error deleting record: " . $conn->error;
    }
}
?>

<div class="content">
    <div class="form-header">
        <h2>View or Edit Course</h2>
        <a href="index.php?page=courses" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" id="courseForm">
            <div class="form-group">
                <label for="course_code">Course Code:</label>
                <input type="text" id="course_code" name="course_code" 
                       value="<?= htmlspecialchars($course['course_code']) ?>" required
                       pattern="[a-zA-Z0-9]+" title="Letters and numbers only"
                       placeholder="Enter course code (e.g., CS101)">
            </div>

            <div class="form-group">
                <label for="course_name">Course Name:</label>
                <input type="text" id="course_name" name="course_name" 
                       value="<?= htmlspecialchars($course['course_name']) ?>" required
                       pattern="[a-zA-Z0-9 ]+" title="Letters, numbers and spaces only"
                       placeholder="Enter course name">
            </div>

            <div class="form-group">
                <label for="course_units">Course Units:</label>
                <input type="number" id="course_units" name="course_units" 
                       value="<?= htmlspecialchars($course['course_units']) ?>" required
                       min="1" max="10" step="1" placeholder="Enter units (1-10)">
            </div>

            <div class="form-actions">
                <button type="submit" name="update" class="btn btn-green" 
        onclick="return confirm('Are you sure you want to update <?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['course_name']) ?> (ID: <?= $course['fld_indx_courses'] ?>) course?')">Update Course</button>
                <button type="submit" name="delete" class="btn btn-red" 
        onclick="return confirm('Are you sure you want to PERMANENTLY DELETE <?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['course_name']) ?> (ID: <?= $course['fld_indx_courses'] ?>)?\n\nThis will remove ALL related enrollments and cannot be undone!')">
                    Delete Course
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Client-side validation
document.getElementById('courseForm').addEventListener('submit', function(e) {
    let valid = true;
    const errors = [];
    
    // Validate course code
    const courseCode = document.getElementById('course_code');
    if (!/^[a-zA-Z0-9]+$/.test(courseCode.value)) {
        errors.push("Course code must contain only letters and numbers.");
        valid = false;
    }
    
    // Validate course name
    const courseName = document.getElementById('course_name');
    if (!/^[a-zA-Z0-9 ]+$/.test(courseName.value)) {
        errors.push("Course name must contain only letters, numbers and spaces.");
        valid = false;
    }
    
    // Validate course units
    const courseUnits = document.getElementById('course_units');
    if (courseUnits.value < 1 || courseUnits.value > 10) {
        errors.push("Course units must be between 1 and 10.");
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
        alert(errors.join("\n"));
    }
});
</script>