<?php
include('db-connect.php');

// Search functionality
$searchTerm = $_GET['search'] ?? '';

// Base query with all necessary fields
$query = "SELECT 
            cl.fld_index_class_list, 
            sl.std_number, 
            sl.std_last_name, 
            sl.std_first_name,
            co.course_code, 
            co.course_name
          FROM class_list cl
          INNER JOIN students_list sl ON cl.std_number = sl.fld_indx_std
          INNER JOIN courses_offered co ON cl.course_code = co.fld_indx_courses";

// Add search condition if exists
if (!empty($searchTerm)) {
    $query .= " WHERE (sl.std_number LIKE ? 
               OR sl.std_last_name LIKE ? 
               OR sl.std_first_name LIKE ? 
               OR co.course_code LIKE ? 
               OR co.course_name LIKE ?)";
    $searchTerm = "%$searchTerm%";
}

// Add sorting
$query .= " ORDER BY cl.fld_index_class_list ASC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

if (!empty($searchTerm)) {
    $stmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
?>

    <h2>Class List</h2>
    
    <div class="header">
        <div class="search-container">
            <form method="get">
                <input type="hidden" name="page" value="classes">
                <input type="text" name="search" placeholder="Search by student or course..." 
                       value="<?= htmlspecialchars(str_replace('%', '', $searchTerm ?? '')) ?>">
                <button type="submit" class="btn btn-gray">Search</button>
            </form>
        </div>
    </div>

<table>
    <tr>
        <th>ID</th>
        <th>Student Number</th>
        <th>Student Name</th>
        <th>Course</th>
        <th>Action</th>
    </tr>

    <?php if ($result->num_rows == 0): ?>
        <tr><td colspan="5" style="text-align: center;">No class records found</td></tr>
    <?php else: ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td class="tb-data-number"><?= $row['fld_index_class_list'] ?></td>
                <td><?= htmlspecialchars($row['std_number']) ?></td>
                <td><?= htmlspecialchars($row['std_last_name']) ?>, <?= htmlspecialchars($row['std_first_name']) ?></td>
                <td><?= htmlspecialchars($row['course_code']) ?> - <?= htmlspecialchars($row['course_name']) ?></td>
                <td class="action-cell">
                    <a href="view-class.php?id=<?= $row['fld_index_class_list'] ?>" class="btn btn-gray">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>