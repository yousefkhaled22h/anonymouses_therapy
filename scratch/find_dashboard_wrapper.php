<?php
$file = file_get_contents('assets/css/dashboard-style.css');
$pos = strpos($file, '.dashboard-main');
if ($pos !== false) {
    echo substr($file, $pos, 1000);
} else {
    echo "Not found";
}
?>
