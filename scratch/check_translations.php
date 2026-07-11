<?php
$files = ['messages/en.json', 'messages/ar.json'];
foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "=== File: $file ===\n";
        $data = json_decode(file_get_contents($path), true);
        foreach ($data as $k => $v) {
            if (stripos($k, 'Opens in') !== false || stripos($k, 'min') !== false || stripos($k, 'countdown') !== false) {
                echo "  '$k' => '$v'\n";
            }
        }
    }
}
