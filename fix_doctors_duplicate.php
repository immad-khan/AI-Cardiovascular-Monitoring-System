<?php
$content = file_get_contents('frontend/doctors.php');
$parts = explode('</html>', $content);
if (count($parts) > 1) {
    file_put_contents('frontend/doctors.php', $parts[0] . '</html>');
    echo "Duplicate content removed successfully.";
} else {
    echo "No duplication found or </html> tag missing.";
}
?>
