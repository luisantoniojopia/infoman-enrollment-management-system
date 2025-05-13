<?php 
include('header.php');
include('navigation.php');
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Simple Page Routing
if (isset($_GET['page'])) {
    echo '<div class="content">';
    switch($_GET['page']) {
        case 'students':
            include 'student-list.php';
            break;
        case 'courses':
            include 'course-list.php';
            break;
        case 'enrollments':
            include 'enrollment-list.php';
            break;
        case 'enrollments2':
            include 'enrollment2-list.php';
            break;
        case 'classes':
            include 'class-list.php';
            break;
            // Add more pages as needed
        default:
            echo "<p>Welcome to the Enrollment Management System. Use the sidebar to navigate.</p>";
    }
    echo '</div>';
} else {
    echo '<div class="content">
        <p>Welcome to the Enrollment Management System. Use the sidebar to navigate.</p>
    </div>';
}
?>