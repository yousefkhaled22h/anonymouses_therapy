<?php
$content = file_get_contents('assets/css/dashboard-style.css');
preg_match_all('/[^\n]*--sidebar-width[^\n]*/i', $content, $matches);
foreach ($matches[0] as $line) {
    echo trim($line) . "\n";
}
?>
