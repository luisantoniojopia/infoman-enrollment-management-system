<?php 
include('header.php');
include('navigation.php');
include('db-connect.php');

// Initialize variables
$success = '';
$error = '';

// Fetch student data
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php?page=students");
    exit;
}

// Retrieve data from database
$result = $conn->query("SELECT * FROM students_list WHERE fld_indx_std = $id");

if ($result->num_rows == 0) {
    $error = "Student not found!";
}

// Once found, save it in an array
$student = $result->fetch_assoc();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $std_number = $_POST['std_number'];
    $std_last_name = $_POST['std_last_name'];
    $std_first_name = $_POST['std_first_name'];
    $std_middle_name = $_POST['std_middle_name'];
    $std_program = $_POST['std_program'];
    
    // Check if any values have changed
    $unchanged = true;
    if ($student['std_number'] != $std_number ||
        $student['std_last_name'] != $std_last_name ||
        $student['std_first_name'] != $std_first_name ||
        $student['std_middle_name'] != $std_middle_name ||
        $student['std_program'] != $std_program) {
        $unchanged = false;
    }
    
    if ($unchanged) {
        $error = "No changes were made to the student record.";
    } else {
        $valid = true;
        $validation_errors = [];
    
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
            // Check if the new student number already exists (excluding the current student)
            $stmt = $conn->prepare("SELECT * FROM students_list WHERE std_number = ? AND fld_indx_std != ?");
            $stmt->bind_param("si", $std_number, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                // Update student record
                $stmt = $conn->prepare("UPDATE students_list SET 
                    std_number = ?, 
                    std_last_name = ?, 
                    std_first_name = ?, 
                    std_middle_name = ?, 
                    std_program = ? 
                    WHERE fld_indx_std = ?");
                $stmt->bind_param("sssssi", $std_number, $std_last_name, $std_first_name, $std_middle_name, $std_program, $id);

                if ($stmt->execute()) {
                    $success = "Student record updated successfully!";
                    // Refresh the student data
                    $student['std_number'] = $std_number;
                    $student['std_last_name'] = $std_last_name;
                    $student['std_first_name'] = $std_first_name;
                    $student['std_middle_name'] = $std_middle_name;
                    $student['std_program'] = $std_program;
                } else {
                    $error = "Error updating record: " . $conn->error;
                }
            } else {
                $error = "Student number already exists!";
            }
        } else {
            $error = implode("<br>", $validation_errors);
        }
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    // First delete all enrollments for this student
    $deleteEnrollments = $conn->prepare("DELETE FROM courses_enrolled WHERE std_number = ?");
    $deleteEnrollments->bind_param("i", $id);
    $deleteEnrollments->execute();
    
    // Then delete the student
    $stmt = $conn->prepare("DELETE FROM students_list WHERE fld_indx_std = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Student record and all related enrollments deleted successfully!";
        header("Location: index.php?page=students");
        exit;
    } else {
        $error = "Error deleting record: " . $conn->error;
    }
}
?>

<div class="content">
    <div class="form-header">
        <h2>View or Edit Student</h2>
        <a href="index.php?page=students" class="btn btn-gray">Back</a>
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

        <form method="post" id="studentForm">
            <div class="form-group">
                <label>Student Number:</label>
                <input type="text" name="std_number" value="<?= htmlspecialchars($student['std_number']) ?>" required 
                       pattern="\d+" title="Student number must contain only numbers">
            </div>
            
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="std_last_name" value="<?= htmlspecialchars($student['std_last_name']) ?>" required
                       pattern="[a-zA-Z ]+" title="Last name must contain only letters">
            </div>
            
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="std_first_name" value="<?= htmlspecialchars($student['std_first_name']) ?>" required
                       pattern="[a-zA-Z ]+" title="First name must contain only letters">
            </div>
            
            <div class="form-group">
                <label>Middle Name:</label>
                <input type="text" name="std_middle_name" value="<?= htmlspecialchars($student['std_middle_name']) ?>"
                       pattern="[a-zA-Z ]*" title="Middle name must contain only letters">
            </div>
            
            <div class="form-group">
                <label>Program:</label>
                <select name="std_program" required>
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
                        $selected = ($student['std_program'] == $program) ? 'selected' : '';
                        echo "<option value=\"$program\" $selected>$program</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="update" class="btn btn-green" 
        onclick="return confirm('Are you sure you want to update <?= htmlspecialchars($student['std_first_name']) ?> <?= htmlspecialchars($student['std_last_name']) ?> (ID: <?= $student['fld_indx_std'] ?>) record?')">Update Student</button>
                <button type="submit" name="delete" class="btn btn-red" 
        onclick="return confirm('Are you sure you want to PERMANENTLY DELETE <?= htmlspecialchars($student['std_first_name']) ?> <?= htmlspecialchars($student['std_last_name']) ?> (ID: <?= $student['fld_indx_std'] ?>)? This action cannot be undone!')">Delete Student</button>
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
    const stdNumber = document.querySelector('input[name="std_number"]');
    if (!/^\d+$/.test(stdNumber.value)) {
        errors.push("Student number must contain only numbers.");
        valid = false;
    }
    
    // Validate names
    const lastName = document.querySelector('input[name="std_last_name"]');
    if (!/^[a-zA-Z ]+$/.test(lastName.value)) {
        errors.push("Last name must contain only letters.");
        valid = false;
    }
    
    const firstName = document.querySelector('input[name="std_first_name"]');
    if (!/^[a-zA-Z ]+$/.test(firstName.value)) {
        errors.push("First name must contain only letters.");
        valid = false;
    }
    
    const middleName = document.querySelector('input[name="std_middle_name"]');
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