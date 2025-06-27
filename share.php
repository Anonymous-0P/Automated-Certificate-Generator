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
        $valid = true;

        // ✅ Replace with your actual public URL (ngrok or domain)
        $base_url = "https://b046-2401-4900-62fb-1b36-f835-8fb3-e9e-a855.ngrok-free.app/certificate/";
        $share_url = $base_url . "share.php?code=" . urlencode($data['unique_code']);
        $pdf_path = __DIR__ . '/' . $data['pdf_path'];
        $preview_image = $base_url . "images/logo.jpeg";  // ✅ Use logo as preview image
        $pdf_url = $base_url . $data['pdf_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $valid ? "{$data['full_name']}'s Certificate" : "Invalid Certificate Code" ?></title>

    <?php if ($valid): ?>
    <meta property="og:title" content="Certificate of <?= htmlspecialchars($data['full_name']) ?>" />
    <meta property="og:description" content="<?= htmlspecialchars($data['full_name']) ?> successfully completed <?= htmlspecialchars($data['course_name']) ?> at <?= htmlspecialchars($data['organization_name']) ?>." />
    <meta property="og:image" content="<?= $preview_image ?>" />
    <meta property="og:url" content="<?= $share_url ?>" />
    <meta property="og:type" content="article" />
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 40px; }
        .card { max-width: 700px; margin: auto; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 12px; }
        img.preview { width: 100%; border-radius: 10px; margin-bottom: 20px; }
        .btn { margin: 5px; }
    </style>
</head>
<body>
<?php if ($valid): ?>
    <div class="card p-4 text-center">
        <h2 class="text-success mb-3">🎓 <?= htmlspecialchars($data['full_name']) ?>'s Certificate</h2>
        <p><strong>Course:</strong> <?= htmlspecialchars($data['course_name']) ?></p>
        <p><strong>Organization:</strong> <?= htmlspecialchars($data['organization_name']) ?></p>
        <img src="<?= $preview_image ?>" alt="Certificate Preview" class="preview">
        <div>
            <a href="<?= $pdf_url ?>" class="btn btn-primary" target="_blank">📄 Download PDF</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($share_url) ?>" class="btn btn-info" target="_blank">💼 LinkedIn</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($share_url) ?>" class="btn btn-primary" target="_blank">📘 Facebook</a>
            <a href="https://wa.me/?text=<?= urlencode('Check out my certificate: ' . $share_url) ?>" class="btn btn-success" target="_blank">📱 WhatsApp</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($share_url) ?>&text=<?= urlencode('I just earned my certificate in ' . $data['course_name'] . ' from ' . $data['organization_name'] . '! 🎓') ?>" class="btn btn-dark" target="_blank">🐦 Twitter (X)</a>
        </div>
        <small class="text-muted">Instagram sharing: Download the image and upload manually in the app.</small>
    </div>
<?php else: ?>
    <h3 class="text-center text-danger">❌ Invalid Certificate Code</h3>
<?php endif; ?>
</body>
</html>
