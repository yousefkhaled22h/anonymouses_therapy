<?php
// scratch/extract_english_strings.php

$projectRoot = __DIR__ . '/..';
$filesToScan = [];

// Recursively find all php files
function getPhpFiles($dir, &$files) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.git' || $item === 'scratch' || $item === 'assets' || $item === 'db' || $item === 'config') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            getPhpFiles($path, $files);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
}

getPhpFiles($projectRoot, $filesToScan);

// Load existing translations to compare
$arJsonPath = $projectRoot . '/messages/ar.json';
$arKeys = [];
if (file_exists($arJsonPath)) {
    $arTranslations = json_decode(file_get_contents($arJsonPath), true) ?: [];
    $arKeys = array_keys($arTranslations);
}

// Normalize helper
function normalize($str) {
    return trim(preg_replace('/\s+/', ' ', $str));
}

$normalizedArKeys = array_map('normalize', $arKeys);

$allStrings = [];

foreach ($filesToScan as $file) {
    $content = file_get_contents($file);
    $relativeName = str_replace($projectRoot . '/', '', $file);
    
    // 1. Find all __("...") or __('...') strings
    preg_match_all('/__\(([\'"])(.*?)\1\)/', $content, $matches);
    if (!empty($matches[2])) {
        foreach ($matches[2] as $str) {
            $norm = normalize($str);
            if ($norm !== '') {
                $allStrings[$norm][$relativeName] = true;
            }
        }
    }
    
    // 2. Parse HTML text blocks (outside PHP tags)
    // We can split the file by PHP tags to look only at the HTML parts
    $parts = preg_split('/(<\?php.*?\?>|<\?=.*?\?>)/is', $content);
    foreach ($parts as $html) {
        // Strip script and style blocks
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Find visible inner text between tags: e.g. >text<
        preg_match_all('/>([^<]+)</', $html, $matchesHtml);
        if (!empty($matchesHtml[1])) {
            foreach ($matchesHtml[1] as $text) {
                // Check if it has English letters
                if (preg_match('/[a-zA-Z]/', $text)) {
                    $norm = normalize($text);
                    // Filter out comments, or dynamic code residues, or CSS selectors
                    if ($norm !== '' && strpos($norm, '<!--') === false && strpos($norm, '{') === false && strpos($norm, '}') === false) {
                        $allStrings[$norm][$relativeName] = true;
                    }
                }
            }
        }
        
        // Find placeholders
        preg_match_all('/placeholder=["\']([^"\']+)["\']/', $html, $matchesPlaceholder);
        if (!empty($matchesPlaceholder[1])) {
            foreach ($matchesPlaceholder[1] as $text) {
                if (preg_match('/[a-zA-Z]/', $text)) {
                    $norm = normalize($text);
                    $allStrings[$norm][$relativeName] = true;
                }
            }
        }
        
        // Find alt text
        preg_match_all('/alt=["\']([^"\']+)["\']/', $html, $matchesAlt);
        if (!empty($matchesAlt[1])) {
            foreach ($matchesAlt[1] as $text) {
                if (preg_match('/[a-zA-Z]/', $text)) {
                    $norm = normalize($text);
                    $allStrings[$norm][$relativeName] = true;
                }
            }
        }
        
        // Find titles
        preg_match_all('/title=["\']([^"\']+)["\']/', $html, $matchesTitle);
        if (!empty($matchesTitle[1])) {
            foreach ($matchesTitle[1] as $text) {
                if (preg_match('/[a-zA-Z]/', $text) && strlen($text) > 1 && strlen($text) < 100) {
                    $norm = normalize($text);
                    $allStrings[$norm][$relativeName] = true;
                }
            }
        }
        
        // Find input submit/button values
        preg_match_all('/<input[^>]+type=["\'](?:submit|button)["\'][^>]+value=["\']([^"\']+)["\']/', $html, $matchesVal);
        if (!empty($matchesVal[1])) {
            foreach ($matchesVal[1] as $text) {
                if (preg_match('/[a-zA-Z]/', $text)) {
                    $norm = normalize($text);
                    $allStrings[$norm][$relativeName] = true;
                }
            }
        }
    }
}

// Filter and find what is untranslated
$untranslated = [];
foreach ($allStrings as $str => $locations) {
    if (!in_array(normalize($str), $normalizedArKeys)) {
        // Exclude things that are clearly not text (like PHP variable names or tags or numbers or URLs)
        if (preg_match('/^[a-zA-Z\s\d\.,!\?\'"\(\)&\/\-#%🏆✨😰😤😔🌊🌫️🧱:\s]+$/', $str)) {
            // Also exclude pure numbers/special characters
            if (!preg_match('/^[0-9\s]+$/', $str)) {
                $untranslated[$str] = array_keys($locations);
            }
        }
    }
}

// Print results
echo "Total distinct untranslated strings found: " . count($untranslated) . "\n\n";
foreach ($untranslated as $str => $locs) {
    echo "String: \"$str\"\n";
    echo "Files: " . implode(', ', $locs) . "\n\n";
}

// Also output them as JSON to help us build the ar.json update
file_put_contents(__DIR__ . '/untranslated_strings.json', json_encode($untranslated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Saved JSON list to scratch/untranslated_strings.json\n";
