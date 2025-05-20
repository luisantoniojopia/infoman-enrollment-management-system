<?php
require_once('libs/tcpdf/tcpdf.php');
include('db-connect.php');

// Get student ID
$student_id = $_GET['id'] ?? 0;
$student_id = intval($student_id);

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Enrollment System');
$pdf->SetAuthor('Enrollment Manager');
$pdf->SetTitle('Student Course Enrollment');
$pdf->SetSubject('Student Enrollment Details');

// Add a page
$pdf->AddPage();

// Set font for title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'Student Course Enrollment', 0, 1, 'C');

// Get student information
$student_stmt = $conn->prepare("SELECT std_number, std_last_name, std_first_name 
                              FROM students_list 
                              WHERE fld_indx_std = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows == 0) {
    die("Student not found");
}

$student = $student_result->fetch_assoc();

// Add student info
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Student: '.$student['std_number'].' - '.$student['std_last_name'].', '.$student['std_first_name'], 0, 1, 'L');
$pdf->Cell(0, 10, 'Generated on: '.date('Y-m-d H:i:s'), 0, 1, 'R');
$pdf->Ln(5);

// Get enrolled courses
$courses_stmt = $conn->prepare("SELECT ce.fld_indx_enrolled AS enrollment_id,
                               co.course_code, 
                               co.course_name,
                               co.course_units
                               FROM courses_enrolled ce
                               JOIN courses_offered co ON ce.course_code = co.fld_indx_courses
                               WHERE ce.std_number = ?");
$courses_stmt->bind_param("i", $student_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

// Create table header
$pdf->SetFont('helvetica', 'B', 12);
$header = array('Enrollment ID', 'Course Code', 'Course Name', 'Units');
$w = array(25, 30, 100, 20); // Adjusted widths for course details

for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
}
$pdf->Ln();

// Add table data
$pdf->SetFont('helvetica', '', 10);
if ($courses_result->num_rows == 0) {
    $pdf->Cell(array_sum($w), 6, 'No courses enrolled', 1, 0, 'C');
    $pdf->Ln();
} else {
    while($course = $courses_result->fetch_assoc()) {
        $pdf->Cell($w[0], 6, $course['enrollment_id'], 'LR', 0, 'C');
        $pdf->Cell($w[1], 6, $course['course_code'], 'LR', 0, 'C');
        $pdf->Cell($w[2], 6, $course['course_name'], 'LR', 0, 'L');
        $pdf->Cell($w[3], 6, $course['course_units'], 'LR', 0, 'C');
        $pdf->Ln();
    }
}

// Closing line
$pdf->Cell(array_sum($w), 0, '', 'T');

// Add summary
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(30, 10, 'Total Courses:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, $courses_result->num_rows, 0, 1, 'L');

// Output PDF
$pdf->Output('student_courses_'.$student['std_number'].'.pdf', 'I');