<?php
$fontFile = __DIR__ . '/lib/fpdf/font/GreatVibes-Regular.php';
$zFile = __DIR__ . '/lib/fpdf/font/GreatVibes-Regular.z';

echo "Font PHP Exists: " . (file_exists($fontFile) ? 'Yes' : 'No') . "\n";
echo "Font Z Exists: " . (file_exists($zFile) ? 'Yes' : 'No') . "\n";
?>