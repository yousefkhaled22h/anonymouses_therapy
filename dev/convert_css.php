<?php
function convertToLogical($content)
{
    $replacements = [
        'margin-left' => 'margin-inline-start',
        'margin-right' => 'margin-inline-end',
        'padding-left' => 'padding-inline-start',
        'padding-right' => 'padding-inline-end',
        'border-left' => 'border-inline-start',
        'border-right' => 'border-inline-end',
        'left:' => 'inset-inline-start:',
        'right:' => 'inset-inline-end:',
        'text-align: left' => 'text-align: start',
        'text-align: right' => 'text-align: end',
    ];

    // carefully replace only standard property occurrences
    foreach ($replacements as $old => $new) {
        $content = preg_replace("/\b" . preg_quote($old, "/") . "(?![A-Za-z0-9\-])\b/", $new, $content);
    }
    return $content;
}

$cssFiles = [
    __DIR__ . '/assets/css/style.css',
    __DIR__ . '/assets/css/dashboard-style.css'
];

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newContent = convertToLogical($content);

        // Append explicit [dir="rtl"] overrides for cases that don't nicely map to logical properties (like float or font families or transform).
        $rtlAppend = "\n" . '
/* ==== RTL Layout Toggles ==== */
[dir="rtl"] {
    --font-heading: "Almarai", "Outfit", sans-serif;
    --font-body: "Tajawal", "Inter", sans-serif;
}
[dir="rtl"] .dropdown-menu {
    inset-inline-start: auto;
    inset-inline-end: 0;
}
[dir="rtl"] .dropdown-icon {
    margin-inline-start: 5px;
}
[dir="rtl"] .sofa-img {
    inset-inline-end: auto;
    inset-inline-start: -20px;
    transform: scaleX(-1);
}
[dir="rtl"] .custom-shape-divider-bottom-1766255146 svg {
    transform: scaleX(-1);
}
';
        if (strpos($newContent, '/* ==== RTL Layout Toggles ==== */') === false && basename($file) === 'style.css') {
            $newContent .= $rtlAppend;
        }

        file_put_contents($file, $newContent);
        echo "Updated $file\n";
    }
}
?>