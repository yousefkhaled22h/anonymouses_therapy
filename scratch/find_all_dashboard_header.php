<?php
$content = file_get_contents('assets/css/dashboard-style.css');
preg_match_all('/([^\}\{]*header[^\}\{]*)\{([^}]+)\}/i', $content, $matches);
foreach ($matches[0] as $match) {
    echo trim($match) . "\n\n";
}
?>
