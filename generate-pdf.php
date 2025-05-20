<?php
require_once('tcpdf/tcpdf.php');
include('db-connect.php');

// Get search term if exists
$searchTerm = $_GET['search'] ?? '';

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Your System');
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Enrollment List');
$pdf->SetSubject('Student Enrollments');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'Enrollment List', 0, 1, 'C');

// Add date
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');

// Create query (same as in enrollment2-list.php)
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
          LEFT JOIN courses_enrolled ce ON sl.fld_indx_std = ce.std_number";

if (!empty($searchTerm)) {
    $query .= " WHERE (sl.std_number LIKE ? 
               OR sl.std_last_name LIKE ? 
               OR sl.std_first_name LIKE ?)";
    $searchTerm = "%$searchTerm%";
}

$query .= " GROUP BY sl.fld_indx_std ORDER BY sl.fld_indx_std";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($searchTerm)) {
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();

// Create table header
$pdf->SetFont('helvetica', 'B', 12);
$header = array('ID', 'Student Number', 'Student Name', 'Courses Enrolled', 'Status');
$w = array(15, 30, 60, 30, 30);
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
}
$pdf->Ln();

// Add table data
$pdf->SetFont('helvetica', '', 10);
while($row = $result->fetch_assoc()) {
    $pdf->Cell($w[0], 6, $row['student_id'], 'LR', 0, 'C');
    $pdf->Cell($w[1], 6, $row['std_number'], 'LR', 0, 'C');
    $pdf->Cell($w[2], 6, $row['std_last_name'].', '.$row['std_first_name'], 'LR', 0, 'L');
    $pdf->Cell($w[3], 6, $row['courses_enrolled'], 'LR', 0, 'C');
    $pdf->Cell($w[4], 6, $row['status'], 'LR', 0, 'C');
    $pdf->Ln();
}

// Closing line
$pdf->Cell(array_sum($w), 0, '', 'T');

// Output PDF
$pdf->Output('enrollment_list.pdf', 'I');