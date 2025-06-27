<?php
require 'db.php';
require 'lib/fpdf.php';
require 'lib/phpqrcode/qrlib.php';
session_start();
require 'csrf.php';

if (
    !isset($_SESSION['is_admin']) ||
    $_SESSION['is_admin'] !== true ||
    $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] ||
    $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']
) {
    die("Access denied");
}

class CertificatePDF extends FPDF
{
    public $unique_code = '';
    public $issue_date = '';

    function Header()
    {
        $this->Image('images/template12.jpg', 0, 0, 210, 297);
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Issue Date: ' . $this->issue_date, 0, 1, 'C');
        $this->Cell(0, 5, 'Certificate Code: ' . $this->unique_code, 0, 1, 'C');
        $this->Cell(0, 5, 'Verify at:https://b046-2401-4900-62fb-1b36-f835-8fb3-e9e-a855.ngrok-free.app/certificate/validate.php?code=' . $this->unique_code, 0, 0, 'C');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $reg_number = $_POST['reg_number'];
    $organization_name = $_POST['organization_name'];
    $course_name = $_POST['course_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $issue_date_input = $_POST['issue_date'];
    $total_hours = (int) $_POST['total_hours'];
    $course_content = $_POST['course_content'] ?? '';
    $activities_raw = $_POST['activities'];
    $appreciation_msg = trim($_POST['appreciation_message'] ?? '');
    $activities = json_decode($activities_raw, true);

    if (!is_array($activities)) {
        die("❌ Invalid JSON format for activities.");
    }

    $activities_json = json_encode($activities);
    $unique_code = strtoupper(uniqid('CERT'));
    $qr_path = "qr_codes/{$unique_code}.png";
    $pdf_path = "certificates/{$unique_code}.pdf";
    $formatted_issue_date = date('d/m/Y', strtotime($issue_date_input));

    QRcode::png("https://b046-2401-4900-62fb-1b36-f835-8fb3-e9e-a855.ngrok-free.app/certificate/validate.php?code=$unique_code", $qr_path);

    $pdf = new CertificatePDF('P', 'mm', 'A4');
    $pdf->AddFont('GreatVibes', '', 'GreatVibes-Regular.php');
    $pdf->AddFont('Cinzel', '', 'CinzelDecorative-Bold.php'); // ✅ Cinzel font added
    $pdf->unique_code = $unique_code;
    $pdf->issue_date = $formatted_issue_date;
    $pdf->AddPage();

    // Certificate Intro
    $pdf->SetFont('Times', 'I', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 32, '', 0, 1);
    $pdf->Cell(0, 10, 'This is to certify that', 0, 1, 'C');

    // Name in Dark Blue
    $pdf->SetFont('GreatVibes', '', 36);
    $pdf->SetTextColor(255, 111, 0);
    $pdf->Cell(0, 20, $full_name, 0, 1, 'C');

    // Course Message
    $pdf->SetFont('Times', '', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, "has successfully completed Internship in", 0, 1, 'C');

    // Course Name in Dark Green
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(255, 111, 0);
    $pdf->Cell(0, 10, $course_name, 0, 1, 'C');

    // Optional Use of Cinzel (uncomment if needed)
    // $pdf->SetFont('Cinzel', '', 18);
    // $pdf->Cell(0, 10, "With Distinction", 0, 1, 'C');

    // Reset color
    $pdf->SetTextColor(0, 0, 0);

    // Dates
    $formatted_start = date('d/m/Y', strtotime($start_date));
    $formatted_end = date('d/m/Y', strtotime($end_date));
    $pdf->SetFont('Times', '', 14);
    $pdf->Cell(0, 10, "From $formatted_start To $formatted_end", 0, 1, 'C');
    $pdf->Ln(5);

    // Appreciation Message
    if (!empty($appreciation_msg)) {
        $pdf->SetFont('Arial', 'I', 13);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 8, $appreciation_msg, 0, 'C');
        $pdf->Ln(5);
    }

    // Course Content
    if (!empty($course_content)) {
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 8, "Course Content:\n$course_content", 0, 'L');
        $pdf->Ln(5);
    }

    // Activities Table
    $pdf->SetFont('Arial', '', 12);
    $activity_col_width = $pdf->GetStringWidth("Activity") + 10;
    $marks_col_width = $pdf->GetStringWidth("Marks (Max. 20)") + 10;

    foreach ($activities as $act) {
        if (!empty($act['activity'])) {
            $activity_col_width = max($activity_col_width, $pdf->GetStringWidth($act['activity']) + 10);
        }
        if (isset($act['marks'])) {
            $marks_col_width = max($marks_col_width, $pdf->GetStringWidth((string) $act['marks']) + 10);
        }
    }

    $table_width = $activity_col_width + $marks_col_width;
    $start_x = ($pdf->GetPageWidth() - $table_width) / 2;

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetX($start_x);
    $pdf->Cell($activity_col_width, 10, 'Activity', 1);
    $pdf->Cell($marks_col_width, 10, 'Marks (Max. 20)', 1, 0, 'C');
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 12);
    $total_marks = 0;
    foreach ($activities as $act) {
        if (empty($act['activity']) || !isset($act['marks'])) continue;
        $pdf->SetX($start_x);
        $pdf->Cell($activity_col_width, 10, $act['activity'], 1);
        $pdf->Cell($marks_col_width, 10, (int) $act['marks'], 1, 0, 'C');
        $pdf->Ln();
        $total_marks += (int) $act['marks'];
    }

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetX($start_x);
    $pdf->Cell($activity_col_width, 10, 'Total (Max. 100)', 1);
    $pdf->Cell($marks_col_width, 10, $total_marks, 1, 0, 'C');
    $pdf->Ln(10);

    // Grade
    if ($total_marks >= 90) $grade = 'O';
    elseif ($total_marks >= 80) $grade = 'A';
    elseif ($total_marks >= 70) $grade = 'B';
    elseif ($total_marks >= 60) $grade = 'C';
    elseif ($total_marks >= 50) $grade = 'D';
    else $grade = 'F';

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(0, 0, 139);
    $pdf->SetXY(11, 65);
    $pdf->Cell(30, 10, $grade, 0, 1, 'C');

    // QR Code
    $qr_width = 20;
    $qr_x = $pdf->GetPageWidth() - $qr_width - 8;
    $qr_y = $pdf->GetPageHeight() - $qr_width - 8;
    $pdf->Image($qr_path, $qr_x, $qr_y, $qr_width, $qr_width);

    $pdf->Output('F', $pdf_path);

    // Ghostscript JPG Preview
    $preview_dir = __DIR__ . "/images/preview/";
    if (!is_dir($preview_dir))
        mkdir($preview_dir, 0777, true);
    $preview_path = $preview_dir . "{$unique_code}.jpg";
    $gs_command = "\"C:\\Program Files\\gs\\gs10.03.0\\bin\\gswin64c.exe\" -dNOPAUSE -dBATCH -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -r150 -sOutputFile=\"{$preview_path}\" \"{$pdf_path}\"";
    exec($gs_command, $output, $return_var);

    $stmt = $mysqli->prepare("INSERT INTO certificates (
        full_name, reg_number, organization_name, course_name, issue_date,
        start_date, end_date, total_hours, grade, course_content,
        activities_json, unique_code, qr_code_path, pdf_path
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssssisssss",
        $full_name,
        $reg_number,
        $organization_name,
        $course_name,
        $formatted_issue_date,
        $start_date,
        $end_date,
        $total_hours,
        $grade,
        $course_content,
        $activities_json,
        $unique_code,
        $qr_path,
        $pdf_path
    );

    if (!$stmt->execute()) {
        die("❌ Database Insert Error: " . $stmt->error);
    }

    echo "<!DOCTYPE html><html lang='en'><head>
    <meta charset='UTF-8'><title>Certificate Generated</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>body{background:#f8f9fa;padding:40px}.card{max-width:600px;margin:auto;box-shadow:0 0 15px rgba(0,0,0,0.1);border-radius:12px}.btn{margin:5px}</style>
    </head><body><div class='card p-4 text-center'>
    <h2 class='text-success'>🎉 Certificate Successfully Generated</h2>
    <p class='lead'>Certificate for <strong>$full_name</strong> has been generated.</p>
    <p><strong>Course:</strong> $course_name</p>
    <p><strong>Issue Date:</strong> $formatted_issue_date</p>
    <p><strong>Unique Code:</strong> $unique_code</p>
    <div class='mt-3'>
    <a href='$pdf_path' class='btn btn-primary' target='_blank'>Download PDF</a>
    <a href='validate.php?code=$unique_code' class='btn btn-success' target='_blank'>Validate</a>
    <a href='share.php?code=$unique_code' class='btn btn-info' target='_blank'>Share</a>
    </div></div></body></html>";
} else {
    echo "<html><head><title>Error</title></head>
    <body style='font-family:sans-serif; text-align:center; padding:40px;'>
    <h2 style='color:red;'>❌ Invalid Request</h2>
    <p>This page only accepts POST requests.</p>
    </body></html>";
}
?>
