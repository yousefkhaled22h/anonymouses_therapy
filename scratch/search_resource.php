<?php
$content = file_get_contents('api/admin/admin_action.php');
if (strpos($content, 'resource') !== false) {
    echo "Found 'resource' in admin_action.php!\n";
    // Let's print the lines containing it
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'resource') !== false || strpos($line, 'Resource') !== false) {
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "No 'resource' found in admin_action.php.\n";
}
