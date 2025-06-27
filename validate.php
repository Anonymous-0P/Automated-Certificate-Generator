<?php
require 'db.php';

$valid = false;
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $stmt = $mysqli->prepare("SELECT * FROM certificates WHERE unique_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows) {
        $data = $result->fetch_assoc();
        $activities = json_decode($data['activities_json'], true);
        $total_marks = 0;
        $valid = true;

        // Base URL (update as needed)
        $base_url = "https://b046-2401-4900-62fb-1b36-f835-8fb3-e9e-a855.ngrok-free.app/certificate/";
        $share_url = $base_url . "share.php?code=" . urlencode($data['unique_code']);
        $pdf_path = __DIR__ . '/' . $data['pdf_path'];
        $preview_rel = "images/preview/{$data['unique_code']}.jpg";
        $preview_path = __DIR__ . '/' . $preview_rel;

        // Generate preview if missing
        if (!file_exists($preview_path)) {
            $gs = "\"C:\\Program Files\\gs\\gs10.02.0\\bin\\gswin64c.exe\"";
            $cmd = "$gs -dNOPAUSE -dBATCH -sDEVICE=jpeg -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"$preview_path\" \"$pdf_path\"";
            exec($cmd, $output, $ret);
            if ($ret !== 0 || !file_exists($preview_path)) {
                $preview_rel = "images/default_preview.jpg";
            }
        }

        $preview_image = $base_url . $preview_rel;
        $pdf_url = $base_url . $data['pdf_path'];

        $linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($share_url);
        $facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($share_url);
        $twitter = 'https://twitter.com/intent/tweet?url=' . urlencode($share_url) . '&text=' . urlencode("I just earned my certificate in " . $data['course_name'] . " from " . $data['organization_name'] . "! 🎓");
        $whatsapp = 'https://api.whatsapp.com/send?text=' . urlencode("Check out my certificate: " . $share_url);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $valid ? "{$data['full_name']}'s Certificate" : "Invalid Certificate Code" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fffaf5;
            padding: 40px;
            font-family: 'Times New Roman', serif;
        }
        .certificate-container {
            max-width: 900px;
            margin: auto;
            border: 2px solid #ccc;
            padding: 40px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            height: 120px;
            margin-bottom: 10px;
        }
        .header h2 {
            color: #198754;
            margin: 0;
            font-weight: bold;
        }
        .certificate-body {
            text-align: center;
        }
        .certificate-body h1 {
            color: #e67300;
            font-family: 'Great Vibes', cursive;
            font-size: 48px;
            margin-bottom: 10px;
        }
        .certificate-body h3 {
            color: #f26522;
            font-weight: bold;
        }
        .certificate-body p {
            font-size: 18px;
        }
        .table-container {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: auto;
        }
        table, th, td {
            border: 1px solid black;
            font-size: 16px;
        }
        th {
            background-color: #ffe6cc;
        }
        td, th {
            padding: 10px;
            text-align: left;
        }
        .total-row {
            font-weight: bold;
            background-color: #fff2e6;
        }
        img.preview {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
<?php if ($valid): ?>
    <div class="certificate-container">
        <div class="header">
            <img src="images/logo1.jpeg" alt="Logo">
           
        </div>

        <div class="certificate-body">
            <p><em>This is to certify that</em></p>
            <h1><?= htmlspecialchars($data['full_name']) ?></h1>
            <p>has successfully completed Internship in</p>
            <h3><?= htmlspecialchars($data['course_name']) ?></h3>
            <p>
                From <?= date('d/m/Y', strtotime($data['start_date'])) ?> To
                <?= date('d/m/Y', strtotime($data['end_date'])) ?>
            </p>

            <?php if (!empty($data['appreciation_msg'])): ?>
                <p style="font-style: italic; margin-top: 25px;">
                    <?= nl2br(htmlspecialchars($data['appreciation_msg'])) ?>
                </p>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Activity</th><th>Marks (Max. 20)</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?= htmlspecialchars($activity['activity']) ?></td>
                                <td><?= htmlspecialchars($activity['marks']) ?></td>
                            </tr>
                            <?php $total_marks += intval($activity['marks']); ?>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td>Total (Max. 100)</td>
                            <td><?= $total_marks ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($data['total_hours'])): ?>
                <p class="mt-4"><strong>Total Hours:</strong> <?= htmlspecialchars($data['total_hours']) ?></p>
            <?php endif; ?>

            <?php if (!empty($data['course_content'])): ?>
                <p class="mt-2"><strong>Course Content:</strong><br><?= nl2br(htmlspecialchars($data['course_content'])) ?></p>
            <?php endif; ?>

            <p class="mt-3"><strong>Certificate Code:</strong> <?= htmlspecialchars($data['unique_code']) ?></p>

            <?php if (!empty($data['issue_date']) && $data['issue_date'] !== '0000-00-00'): ?>
                <p><strong>Issue Date:</strong> <?= date('d/m/Y', strtotime($data['issue_date'])) ?></p>
            <?php endif; ?>


            <p><a href="<?= $pdf_url ?>" class="btn btn-primary" target="_blank">📄 Download PDF</a></p>

            <div class="mt-4 text-center">
                <h5>🔗 Share your Certificate:</h5>
                <a href="<?= $linkedin ?>" class="btn btn-outline-primary m-1" target="_blank">💼 LinkedIn</a>
                <a href="<?= $facebook ?>" class="btn btn-outline-info m-1" target="_blank">📘 Facebook</a>
                <a href="<?= $twitter ?>" class="btn btn-outline-dark m-1" target="_blank">🐦 Twitter (X)</a>
                <a href="<?= $whatsapp ?>" class="btn btn-outline-success m-1" target="_blank">📱 WhatsApp</a>
                <p class="text-muted mt-2">Instagram sharing: Download the preview and upload manually in the app.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <h2 class="text-center text-danger">❌ Invalid Certificate Code</h2>
<?php endif; ?>
</body>
</html>
