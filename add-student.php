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
    $std_number = $_POST['std_number'];
    $std_last_name = $_POST['std_last_name'];
    $std_first_name = $_POST['std_first_name'];
    $std_middle_name = $_POST['std_middle_name'];
    $std_program = $_POST['std_program'];

    // Validate inputs
    $valid = true;
    
    // Validate student number (must be integer)
    if (!ctype_digit($std_number)) {
        $validation_errors[] = "Student number must contain only digits.";
        $valid = false;
    }
    
    // Validate names (must be letters and spaces only)
    if (!preg_match("/^[a-zA-Z ]*$/", $std_last_name)) {
        $validation_errors[] = "Last name must contain only letters and spaces.";
        $valid = false;
    }
    
    if (!preg_match("/^[a-zA-Z ]*$/", $std_first_name)) {
        $validation_errors[] = "First name must contain only letters and spaces.";
        $valid = false;
    }
    
    if (!empty($std_middle_name) && !preg_match("/^[a-zA-Z ]*$/", $std_middle_name)) {
        $validation_errors[] = "Middle name must contain only letters and spaces.";
        $valid = false;
    }

    if ($valid) {
        // Check for duplicate using prepared statement
        $stmt = $conn->prepare("SELECT * FROM students_list WHERE std_number = ?");
        $stmt->bind_param("s", $std_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Insert new student using prepared statement
            $stmt = $conn->prepare("INSERT INTO students_list (std_number, std_last_name, std_first_name, std_middle_name, std_program) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $std_number, $std_last_name, $std_first_name, $std_middle_name, $std_program);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Student added successfully!";
                header("Location: index.php?page=students");
                exit;
            } else {
                $error = "Error adding student: " . $conn->error;
            }
        } else {
            $error = "Student number already exists!";
        }
    } else {
        $error = implode("<br>", $validation_errors);
    }
}
?>

<div class="content">
    <div class="form-header">
        <h2>Add New Student</h2>
        <a href="index.php?page=students" class="btn btn-gray">Back</a>
    </div>

    <div class="form-container">
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" id="studentForm">
            <div class="form-group">
                <label for="std_number">Student Number:</label>
                <input type="text" id="std_number" name="std_number" required 
                       pattern="\d+" title="Student number must contain only digits." 
                       placeholder="Enter student number"
                       value="<?= isset($_POST['std_number']) ? htmlspecialchars($_POST['std_number']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="std_last_name">Last Name:</label>
                <input type="text" id="std_last_name" name="std_last_name" required 
                       pattern="[a-zA-Z ]+" title="Last name must contain only letters and spaces."
                       placeholder="Enter last name"
                       value="<?= isset($_POST['std_last_name']) ? htmlspecialchars($_POST['std_last_name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="std_first_name">First Name:</label>
                <input type="text" id="std_first_name" name="std_first_name" required 
                       pattern="[a-zA-Z ]+" title="First name must contain only letters and spaces."
                       placeholder="Enter first name"
                       value="<?= isset($_POST['std_first_name']) ? htmlspecialchars($_POST['std_first_name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="std_middle_name">Middle Name:</label>
                <input type="text" id="std_middle_name" name="std_middle_name" 
                       pattern="[a-zA-Z ]*" title="Middle name must contain only letters and spaces."
                       placeholder="Enter middle name (optional)"
                       value="<?= isset($_POST['std_middle_name']) ? htmlspecialchars($_POST['std_middle_name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="std_program">Program:</label>
                <select id="std_program" name="std_program" required>
                    <option value="">Select Program</option>
                    <?php
                    $programs = [
                        "BS Architecture",
                        "BS Computer Engineering",
                        "BS Computer Science",
                        "BS Electrical Engineering",
                        "BS Electronics Engineering",
                        "BS Entertainment and Multimedia Computing",
                        "BS Industrial Engineering",
                        "BS Information Technology",
                        "Associate in Computer Technology"
                    ];
                    foreach ($programs as $program) {
                        $selected = (isset($_POST['std_program']) && $_POST['std_program'] == $program) ? 'selected' : '';
                        echo "<option value=\"$program\" $selected>$program</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-green" onclick="return confirm('Are you sure you want to add a new student?')">Save Student</button>
                <button type="reset" class="btn btn-gray" onclick="return confirm('Are you sure you want to clear your entries?')">Clear Form</button>
            </div>
        </form>
    </div>
</div>

<script>
// Client-side validation
document.getElementById('studentForm').addEventListener('submit', function(e) {
    let valid = true;
    const errors = [];
    
    // Validate student number
    const stdNumber = document.getElementById('std_number');
    if (!/^\d+$/.test(stdNumber.value)) {
        errors.push("Student number must contain only numbers.");
        valid = false;
    }
    
    // Validate names
    const lastName = document.getElementById('std_last_name');
    if (!/^[a-zA-Z ]+$/.test(lastName.value)) {
        errors.push("Last name must contain only letters.");
        valid = false;
    }
    
    const firstName = document.getElementById('std_first_name');
    if (!/^[a-zA-Z ]+$/.test(firstName.value)) {
        errors.push("First name must contain only letters.");
        valid = false;
    }
    
    const middleName = document.getElementById('std_middle_name');
    if (middleName.value && !/^[a-zA-Z ]*$/.test(middleName.value)) {
        errors.push("Middle name must contain only letters.");
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
        alert(errors.join("\n"));
    }
});
</script>