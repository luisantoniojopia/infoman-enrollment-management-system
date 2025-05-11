<?php 
include('header.php');
include('navigation.php');
include('db-connect.php');

// Initialize variables
$error = '';
$validation_errors = [];
$success = '';

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $course_units = trim($_POST['course_units']);

    // Validate inputs
    $valid = true;
    
    // Validate course code (letters and numbers only)
    if (!preg_match("/^[a-zA-Z0-9]+$/", $course_code)) {
        $validation_errors[] = "Course code must contain only letters and numbers.";
        $valid = false;
    }
    
    // Validate course name (letters, numbers and spaces)
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
        // Check for duplicate course code using prepared statement
        $stmt = $conn->prepare("SELECT * FROM courses_offered WHERE course_code = ?");
        $stmt->bind_param("s", $course_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Insert new course using prepared statement
            $stmt = $conn->prepare("INSERT INTO courses_offered (course_code, course_name, course_units) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $course_code, $course_name, $course_units);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Course added successfully!";
                header("Location: index.php?page=courses");
                exit;
            } else {
                $error = "Error adding course: " . $conn->error;
            }
        } else {
            $error = "Course code already exists!";
        }
    } else {
        $error = implode("<br>", $validation_errors);
    }
}
?>

<div class="content">
    <div class="form-header">
        <h2>Add New Course</h2>
        <a href="index.php?page=courses" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" id="courseForm">
            <div class="form-group">
                <label for="course_code">Course Code:</label>
                <input type="text" id="course_code" name="course_code" required 
                       pattern="[a-zA-Z0-9]+" title="Letters and numbers only"
                       placeholder="Enter course code (e.g., CS101)"
                       value="<?= isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="course_name">Course Name:</label>
                <input type="text" id="course_name" name="course_name" required 
                       pattern="[a-zA-Z0-9 ]+" title="Letters, numbers and spaces only"
                       placeholder="Enter course name"
                       value="<?= isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="course_units">Course Units:</label>
                <input type="number" id="course_units" name="course_units" required 
                       min="1" max="10" step="1" 
                       placeholder="Enter units (1-10)"
                       value="<?= isset($_POST['course_units']) ? htmlspecialchars($_POST['course_units']) : '' ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-green" onclick="return confirm('Are you sure you want to save a new course?')">Save Course</button>
                <button type="reset" class="btn btn-gray" onclick="return confirm('Are you sure you want to clear your entries?')">Clear Form</button>
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