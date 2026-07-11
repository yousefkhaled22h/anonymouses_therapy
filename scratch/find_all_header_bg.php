<?php
$content = file_get_contents('assets/css/style.css');
preg_match_all('/([^\}\{]*header[^\}\{]*)\{([^}]+)\}/i', $content, $matches);
foreach ($matches[0] as $match) {
    if (strpos($match, 'background') !== false) {
        echo trim($match) . "\n\n";
    }
}
?>
