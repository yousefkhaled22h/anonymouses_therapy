<?php
$lines = explode("\n", file_get_contents(__DIR__ . '/../dashboard.php'));
foreach ($lines as $i => $line) {
    if (strpos($line, 'function refreshUpcomingSessions') !== false) {
        for ($j = max(0, $i - 2); $j <= min(count($lines) - 1, $i + 25); $j++) {
            echo ($j + 1) . ": " . $lines[$j] . "\n";
        }
    }
}
