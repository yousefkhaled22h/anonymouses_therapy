<?php
$json = json_decode(file_get_contents(__DIR__ . '/untranslated_strings.json'), true);
$adminKeys = [];
foreach ($json as $str => $locs) {
    $isAdmin = false;
    foreach ($locs as $loc) {
        if (strpos($loc, 'admin/') === 0) {
            $isAdmin = true;
            break;
        }
    }
    if ($isAdmin) {
        $adminKeys[] = $str;
    }
}
sort($adminKeys);
echo "Total Admin Keys: " . count($adminKeys) . "\n";
echo json_encode($adminKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
