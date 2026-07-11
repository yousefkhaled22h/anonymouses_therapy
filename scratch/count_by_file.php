<?php
$json = json_decode(file_get_contents(__DIR__ . '/untranslated_strings.json'), true);
$files = [];
foreach ($json as $str => $locs) {
    foreach ($locs as $loc) {
        if (!isset($files[$loc])) $files[$loc] = 0;
        $files[$loc]++;
    }
}
arsort($files);
foreach ($files as $f => $c) {
    echo "$f: $c untranslated strings\n";
}
