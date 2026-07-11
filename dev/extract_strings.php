<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
$strings = [];

foreach ($files as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), 'api') === false) {
        $content = file_get_contents($file->getPathname());

        // Match >text<
        preg_match_all('/>([^<]+)</', $content, $matches);
        foreach ($matches[1] as $text) {
            $trimmed = trim($text);
            if ($trimmed !== '' && !preg_match('/^[0-9\W_]+$/', $trimmed)) {
                // Ignore PHP tags
                if (strpos($trimmed, '<?php') === false && strpos($trimmed, '?>') === false) {
                    $strings[$trimmed] = true;
                }
            }
        }

        // Match attributes
        $attrs = ['placeholder', 'value', 'title', 'alt'];
        foreach ($attrs as $attr) {
            preg_match_all('/' . $attr . '="([^"]+)"/i', $content, $matches);
            foreach ($matches[1] as $text) {
                $trimmed = trim($text);
                if ($trimmed !== '' && !preg_match('/^[0-9\W_]+$/', $trimmed)) {
                    if (strpos($trimmed, '<?php') === false && strpos($trimmed, '?>') === false) {
                        $strings[$trimmed] = true;
                    }
                }
            }
        }
    }
}

$keys = array_keys($strings);
sort($keys);
file_put_contents(__DIR__ . '/extracted_strings.json', json_encode($keys, JSON_PRETTY_PRINT));
echo "Extracted " . count($keys) . " strings.";
?>