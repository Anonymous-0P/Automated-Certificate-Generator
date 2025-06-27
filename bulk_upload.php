<?php
session_start();
require 'db.php';
require 'lib/phpqrcode/qrlib.php';
require 'lib/fpdf.php';

// 🔐 Auto logout after 10 minutes of inactivity
$timeout_duration = 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?logout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// 🔐 Admin access check
if (
    !isset($_SESSION['is_admin']) ||
    $_SESSION['is_admin'] !== true ||
    $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] ||
    $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']
) {
    header("Location: admin_login.php");
    exit();
}

// 🔐 Manual logout
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?logout=1");
    exit();
}

function is_valid_json($string)
{
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
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
        $this->Cell(0, 5, 'Verify at: https://0df9-117-254-59-236.ngrok-free.app/certificate/validate.php?code=' . $this->unique_code, 0, 0, 'C');
    }
}

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bulk_file'])) {
    $fileContent = file_get_contents($_FILES['bulk_file']['tmp_name']);

    if (!is_valid_json($fileContent)) {
        die("<div class='alert alert-danger'>❌ Uploaded file is not valid JSON.</div>");
    }

    $data = json_decode($fileContent, true);
    $total = count($data);
    $count = 0;

    foreach ($data as $record) {
        $full_name = $record['full_name'] ?? '';
        $reg_number = $record['reg_number'] ?? '';
        $organization_name = $record['organization_name'] ?? '';
        $course_name = $record['course_name'] ?? '';
        $start_date = $record['start_date'] ?? '';
        $end_date = $record['end_date'] ?? '';
        $issue_date_input = $record['issue_date'] ?? '';
        $total_hours = (int) ($record['total_hours'] ?? 0);
        $course_content = $record['course_content'] ?? '';
        $appreciation_msg = trim($record['appreciation_message'] ?? '');
        $activities = $record['activities_json'] ?? [];

        if (!is_array($activities)) {
            $results[] = [
                'reg_number' => $reg_number,
                'status' => '❌ Invalid JSON for activities'
            ];
            continue;
        }

        $activities_json = json_encode($activities);
        $unique_code = strtoupper(uniqid('CERT'));
        $qr_path = "qr_codes/{$unique_code}.png";
        $pdf_path = "certificates/{$unique_code}.pdf";
        $formatted_issue_date = date('d/m/Y', strtotime($issue_date_input));
        $validate_url = "https://0df9-117-254-59-236.ngrok-free.app/certificate/validate.php?code=$unique_code";

        QRcode::png($validate_url, $qr_path);

        $pdf = new CertificatePDF('P', 'mm', 'A4');
        $pdf->AddFont('GreatVibes', '', 'GreatVibes-Regular.php');
        $pdf->AddFont('Cinzel', '', 'CinzelDecorative-Bold.php');
        $pdf->unique_code = $unique_code;
        $pdf->issue_date = $formatted_issue_date;
        $pdf->AddPage();

        $pdf->SetFont('Times', 'I', 16);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 32, '', 0, 1);
        $pdf->Cell(0, 10, 'This is to certify that', 0, 1, 'C');

        $pdf->SetFont('GreatVibes', '', 36);
        $pdf->SetTextColor(255, 111, 0);
        $pdf->Cell(0, 20, $full_name, 0, 1, 'C');

        $pdf->SetFont('Times', '', 16);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, "has successfully completed Internship in", 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(255, 111, 0);
        $pdf->Cell(0, 10, $course_name, 0, 1, 'C');

        $formatted_start = date('d/m/Y', strtotime($start_date));
        $formatted_end = date('d/m/Y', strtotime($end_date));
        $pdf->SetFont('Times', '', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, "From $formatted_start To $formatted_end", 0, 1, 'C');
        $pdf->Ln(5);

        if (!empty($appreciation_msg)) {
            $pdf->SetFont('Arial', 'I', 13);
            $pdf->MultiCell(0, 8, $appreciation_msg, 0, 'C');
            $pdf->Ln(5);
        }

        if (!empty($course_content)) {
            $pdf->SetFont('Arial', '', 12);
            $pdf->MultiCell(0, 8, "Course Content:\n$course_content", 0, 'L');
            $pdf->Ln(5);
        }

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
            if (empty($act['activity']) || !isset($act['marks']))
                continue;
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

        if ($total_marks >= 90)
            $grade = 'O';
        elseif ($total_marks >= 80)
            $grade = 'A';
        elseif ($total_marks >= 70)
            $grade = 'B';
        elseif ($total_marks >= 60)
            $grade = 'C';
        elseif ($total_marks >= 50)
            $grade = 'D';
        else
            $grade = 'F';

        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(0, 0, 139);
        $pdf->SetXY(11, 65);
        $pdf->Cell(30, 10, $grade, 0, 1, 'C');

        $qr_width = 20;
        $qr_x = $pdf->GetPageWidth() - $qr_width - 8;
        $qr_y = $pdf->GetPageHeight() - $qr_width - 8;
        $pdf->Image($qr_path, $qr_x, $qr_y, $qr_width, $qr_width);

        $pdf->Output('F', $pdf_path);

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

        if ($stmt->execute()) {
            $results[] = [
                'reg_number' => $reg_number,
                'unique_code' => $unique_code,
                'pdf' => $pdf_path,
                'status' => '✅ Success'
            ];
        } else {
            $results[] = [
                'reg_number' => $reg_number,
                'status' => '❌ DB Error: ' . htmlspecialchars($stmt->error)
            ];
        }

        $count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Certificate Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f7f9fc;
            padding: 40px;
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<div class="container">
   <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">📥 Bulk Certificate Generator</h3>
    <div class="d-flex gap-2">
        <a href="form.php" class="btn btn-outline-secondary">Single Certificate</a>
        <a href="bulk_upload.php" class="btn btn-outline-primary">Bulk Upload</a>
        <form method="GET" action="bulk_upload.php" style="margin:0;">
            <button name="logout" value="1" class="btn btn-danger">🚪 Logout</button>
        </form>
    </div>
</div>

    <?php if (!empty($results)): ?>
        <div class="progress mb-4">
            <div class="progress-bar" style="width:100%">Upload Complete</div>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Reg Number</th>
                    <th>Unique Code</th>
                    <th>Status</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['reg_number']) ?></td>
                        <td><?= $r['unique_code'] ?? '-' ?></td>
                        <td><?= $r['status'] ?></td>
                        <td>
                            <?php if (isset($r['pdf'])): ?>
                                <a href="<?= htmlspecialchars($r['pdf']) ?>" target="_blank" class="btn btn-sm btn-success">View PDF</a>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="bulk_file" class="form-label">Upload Certificate Data (.json)</label>
                <input type="file" name="bulk_file" id="bulk_file" class="form-control" accept=".json" required>
            </div>
            <div class="progress mb-3" style="height: 25px;">
                <div class="progress-bar" id="uploadProgressBar" style="width: 0%">0%</div>
            </div>
            <button type="submit" class="btn btn-primary w-100">🚀 Generate Certificates</button>
        </form>
        <div class="text-center mt-3">
            <a href="form.php" class="btn btn-outline-secondary w-100">Switch to Single Generation</a>
        </div>

        <script>
            const form = document.querySelector("form");
            const progressBar = document.getElementById("uploadProgressBar");

            form.addEventListener("submit", function (e) {
                e.preventDefault();
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "");
                xhr.upload.addEventListener("progress", function (e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percent + "%";
                        progressBar.textContent = percent + "%";
                    }
                });
                xhr.onload = function () {
                    document.open();
                    document.write(xhr.responseText);
                    document.close();
                };
                const formData = new FormData(form);
                xhr.send(formData);
            });
        </script>
    <?php endif; ?>
</div>
</body>
</html>
