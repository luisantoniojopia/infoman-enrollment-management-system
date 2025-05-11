<?php
include('db-connect.php');

// Initialize search term
$searchTerm = $_GET['search'] ?? '';

// Prepare SQL query based on search
// Initialize search term
$searchTerm = $_GET['search'] ?? '';

// Prepare SQL query based on search
if (isset($_GET['search'])) {
    if (is_numeric($searchTerm) && (int)$searchTerm >= 1 && (int)$searchTerm <= 5) {
        // Valid unit number, include in search
        $stmt = $conn->prepare("SELECT fld_indx_courses, course_code, course_name, course_units 
                                FROM courses_offered
                                WHERE course_code LIKE ? 
                                   OR course_name LIKE ?
                                   OR course_units = ?");
        $searchTermWildcard = "%" . $searchTerm . "%";
        $stmt->bind_param("ssi", $searchTermWildcard, $searchTermWildcard, $searchTerm);
    } else {
        // Invalid unit number (like 0), exclude unit search
        $stmt = $conn->prepare("SELECT fld_indx_courses, course_code, course_name, course_units 
                                FROM courses_offered
                                WHERE course_code LIKE ? 
                                   OR course_name LIKE ?");
        $searchTermWildcard = "%" . $searchTerm . "%";
        $stmt->bind_param("ss", $searchTermWildcard, $searchTermWildcard);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // No search performed — show all
    $result = $conn->query("SELECT fld_indx_courses, course_code, course_name, course_units FROM courses_offered");
}

?>


<h2>Course List</h2>

<!-- Add Course Button -->
<div class="header">

    <div class="search-container">
        <!-- Search Form -->
        <form method="get">
            <input type="hidden" name="page" value="courses"> <!-- Keep page=courses when searching -->
            <input type="text" name="search" placeholder="Search course code, or course name..." value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit" class="btn btn-gray">Search</button>
        </form>
    </div>

    <div class="add-student-container"> <!-- change this css to add-course-container -->
        <a href="add-course.php"><button class="btn btn-green">Add Course</button></a>
    </div>
</div>

<!-- Course List Table -->
<table>
    <tr>
        <th>ID</th>
        <th>Course Code</th>
        <th>Course Name</th>
        <th>Units</th>
        <th>Action</th>
    </tr>

    <?php if ($result->num_rows == 0): ?>
        <tr><td colspan="5" style="text-align: center;">No courses found</td></tr>
    <?php else: ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td class="tb-data-number"><?= $row['fld_indx_courses'] ?></td>
                <td><?= $row['course_code'] ?></td>
                <td><?= $row['course_name'] ?></td>
                <td class="tb-data-number-units"><?= $row['course_units'] ?></td>
                <td class="action-cell">
                    <a href="view-course.php?id=<?= $row['fld_indx_courses'] ?>"><button class="btn btn-gray">View</button></a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
