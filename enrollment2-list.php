<?php
include('db-connect.php');

// Search functionality
$searchTerm = $_GET['search'] ?? '';

// Modified query to get student enrollment counts and status
$query = "SELECT 
            sl.fld_indx_std AS student_id,
            sl.std_number,
            sl.std_last_name,
            sl.std_first_name,
            COUNT(ce.fld_indx_enrolled) AS courses_enrolled,
            CASE 
                WHEN COUNT(ce.fld_indx_enrolled) = 9 THEN 'Full Load'
                WHEN COUNT(ce.fld_indx_enrolled) > 0 THEN 'Partial Load'
                ELSE 'Not Enrolled'
            END AS status
          FROM students_list sl
          LEFT JOIN courses_enrolled ce ON sl.fld_indx_std = ce.std_number
          GROUP BY sl.fld_indx_std";

// Add search condition if exists
if (!empty($searchTerm)) {
    $query = "SELECT 
                sl.fld_indx_std AS student_id,
                sl.std_number,
                sl.std_last_name,
                sl.std_first_name,
                COUNT(ce.fld_indx_enrolled) AS courses_enrolled,
                CASE 
                    WHEN COUNT(ce.fld_indx_enrolled) = 9 THEN 'Full Load'
                    WHEN COUNT(ce.fld_indx_enrolled) > 0 THEN 'Partial Load'
                    ELSE 'Not Enrolled'
                END AS status
              FROM students_list sl
              LEFT JOIN courses_enrolled ce ON sl.fld_indx_std = ce.std_number
              WHERE (sl.std_number LIKE ? 
                     OR sl.std_last_name LIKE ? 
                     OR sl.std_first_name LIKE ?)
              GROUP BY sl.fld_indx_std";
    $searchTerm = "%$searchTerm%";
}

// Add sorting
$query .= " ORDER BY sl.fld_indx_std";

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

if (!empty($searchTerm)) {
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<h2>Enrollment List</h2>

<div class="header">
    <div class="search-container">
        <form method="get">
            <input type="hidden" name="page" value="enrollments">
            <input type="text" name="search" placeholder="Search by student..." 
                   value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit" class="btn btn-gray">Search</button>
        </form>
    </div>
    <div class="add-student-container">
        <a href="add-enrollment2.php"><button class="btn btn-green">Add Enrollment</button></a>
    </div>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Student Number</th>
        <th>Student Name</th>
        <th>Courses Enrolled</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if ($result->num_rows == 0): ?>
        <tr><td colspan="6" style="text-align: center;">No students found</td></tr>
    <?php else: ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td class="tb-data-number"><?= $row['student_id'] ?></td>
                <td><?= htmlspecialchars($row['std_number']) ?></td>
                <td><?= htmlspecialchars($row['std_last_name']) ?>, <?= htmlspecialchars($row['std_first_name']) ?></td>
                <td class="tb-data-number-num-courses"><?= $row['courses_enrolled'] ?></td>
                <td class="tb-data-status"><?= $row['status'] ?></td>
                <td class="action-cell">
                    <a href="view-enrollment2.php?id=<?= $row['student_id'] ?>" class="btn btn-gray">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>