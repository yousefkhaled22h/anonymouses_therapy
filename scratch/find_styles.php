<?php
$content = file_get_contents('assets/css/style.css');
preg_match_all('/([^\}\{]*volunteer[^\}\{]*)\{([^}]+)\}/i', $content, $matches);
foreach ($matches[0] as $match) {
    echo trim($match) . "\n\n";
}
?>
