<?php
include('db-connect.php');

// Initialize search term
$searchTerm = $_GET['search'] ?? '';

// Prepare SQL query based on search
if (!empty($searchTerm)) {
    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT fld_indx_std, std_number, std_last_name, std_first_name, std_middle_name, std_program 
                            FROM students_list 
                            WHERE std_number LIKE ? 
                               OR std_last_name LIKE ? 
                               OR std_first_name LIKE ? 
                               OR std_middle_name LIKE ? 
                               OR std_program LIKE ?");
    $searchTermWildcard = "%" . $searchTerm . "%";
    $stmt->bind_param("sssss", $searchTermWildcard, $searchTermWildcard, $searchTermWildcard, $searchTermWildcard, $searchTermWildcard);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT fld_indx_std, std_number, std_last_name, std_first_name, std_middle_name, std_program FROM students_list");
}
?>

<h2>Student Record</h2>

<!-- Add Student Button -->
<div class="header">
    <div class="search-container">
        <!-- Search Form -->
        <form method="get">
            <input type="hidden" name="page" value="students"> <!-- Keep page=students when searching -->
            <input type="text" name="search" placeholder="Search student number, name, or program..." value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit" class="btn btn-gray">Search</button>
        </form>
    </div>

    <div class="add-student-container">
        <!-- Add Student Button -->
        <a href="add-student.php"><button class="btn btn-green">Add Student</button></a>
    </div>
</div>

<!-- Student List Table -->
<table>
    <tr>
        <th>ID</th>
        <th>Student Number</th>
        <th>Last Name</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Program</th>
        <th>Action</th>
    </tr>

    <?php if ($result->num_rows == 0): ?>
        <tr><td colspan="7" style="text-align: center;">No students found</td></tr>
    <?php else: ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td class="tb-data-number"><?= $row['fld_indx_std'] ?></td>
                <td><?= $row['std_number'] ?></td>
                <td><?= $row['std_last_name'] ?></td>
                <td><?= $row['std_first_name'] ?></td>
                <td><?= $row['std_middle_name'] ?></td>
                <td><?= $row['std_program'] ?></td>
                <td class="action-cell">
                    <a href="view-student.php?id=<?= $row['fld_indx_std'] ?>"><button class="btn btn-gray">View</button></a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>