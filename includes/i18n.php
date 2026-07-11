<?php
// includes/i18n.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
$lang = 'en';

// Check if language is provided in URL
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'ar' ? 'ar' : 'en';
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// Load translations from JSON
$translations = [];
$langFilePath = __DIR__ . "/../messages/{$lang}.json";

if (file_exists($langFilePath)) {
    $translations = json_decode(file_get_contents($langFilePath), true) ?: [];
}

// Helper function to translate a string
if (!function_exists('__')) {
    function __($key)
    {
        global $translations;
        return isset($translations[$key]) ? $translations[$key] : $key;
    }
}

// Helper function to safely replace text with translations matching word boundaries for short alphanumeric keys
if (!function_exists('safe_translate_text')) {
    function safe_translate_text($text, $search, $replace)
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $text));
        if ($normalized === '') return $text;

        $index = array_search($normalized, $search);
        if ($index !== false) {
            return $replace[$index];
        }

        // Case-insensitive exact match fallback
        $lowerNormalized = mb_strtolower($normalized);
        foreach ($search as $idx => $s) {
            if (mb_strtolower($s) === $lowerNormalized) {
                return $replace[$idx];
            }
        }

        // Substring / word replacement with dynamic word boundary safety
        foreach ($search as $idx => $s) {
            $r = $replace[$idx];
            // If the key starts or ends with an alphanumeric character, use word boundary safety
            if (preg_match('/[a-zA-Z0-9]$/', $s) || preg_match('/^[a-zA-Z0-9]/', $s)) {
                $pattern = '/';
                if (preg_match('/^[a-zA-Z0-9]/', $s)) {
                    $pattern .= '\b';
                }
                $pattern .= preg_quote($s, '/');
                if (preg_match('/[a-zA-Z0-9]$/', $s)) {
                    $pattern .= '\b';
                }
                $pattern .= '/iu';
                $text = preg_replace($pattern, $r, $text);
            } else {
                $text = str_replace($s, $r, $text);
            }
        }
        return $text;
    }
}

// Auto-translate HTML output buffer
if (!function_exists('translate_html_buffer')) {
    function translate_html_buffer($buffer)
    {
        global $lang, $translations;
        if ($lang === 'en' || empty($translations)) {
            return $buffer;
        }

        // ── Step 1: Stash script & style blocks so we don't translate code ──
        $scripts = [];
        $styles  = [];

        $buffer = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/is', function ($m) use (&$scripts) {
            $key = '<!--!SCRIPT_' . count($scripts) . '!-->';
            $scripts[$key] = $m[0];
            return $key;
        }, $buffer);

        $buffer = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function ($m) use (&$styles) {
            $key = '<!--!STYLE_' . count($styles) . '!-->';
            $styles[$key] = $m[0];
            return $key;
        }, $buffer);

        // ── Build sorted search/replace arrays (longest key first = most specific) ──
        $keys = array_keys($translations);
        usort($keys, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        $search  = [];
        $replace = [];
        foreach ($keys as $k) {
            if (trim($k) !== '' && mb_strlen($k) > 1) {
                $search[]  = $k;
                $replace[] = $translations[$k];
            }
        }

        // ── Step 2: Translate visible inner text between tags ──
        $buffer = preg_replace_callback('/>([^<]+)</', function ($matches) use ($search, $replace) {
            return '>' . safe_translate_text($matches[1], $search, $replace) . '<';
        }, $buffer);

        // ── Step 3: Translate HTML attributes ──

        // placeholder="..."
        $buffer = preg_replace_callback('/placeholder="([^"]+)"/', function ($m) use ($search, $replace) {
            return 'placeholder="' . safe_translate_text($m[1], $search, $replace) . '"';
        }, $buffer);

        // alt="..."
        $buffer = preg_replace_callback('/\balt="([^"]+)"/', function ($m) use ($search, $replace) {
            return 'alt="' . safe_translate_text($m[1], $search, $replace) . '"';
        }, $buffer);

        // title="..." (tooltips — skip html element title tags)
        $buffer = preg_replace_callback('/\btitle="([^"]{2,80})"/', function ($m) use ($search, $replace) {
            return 'title="' . safe_translate_text($m[1], $search, $replace) . '"';
        }, $buffer);

        // value="..." only on input[type=submit] and input[type=button]
        $buffer = preg_replace_callback('/(<input[^>]+type=["\'](?:submit|button)["\'][^>]+)value="([^"]+)"/', function ($m) use ($search, $replace) {
            return $m[1] . 'value="' . safe_translate_text($m[2], $search, $replace) . '"';
        }, $buffer);

        // ── Step 4: Inject Arabic Google Fonts into <head> ──
        if (strpos($buffer, 'Tajawal') === false) {
            $arabicFonts = '<link rel="preconnect" href="https://fonts.googleapis.com">'
                . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
                . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Almarai:wght@300;400;700;800&display=swap">';
            $buffer = str_replace('</head>', $arabicFonts . '</head>', $buffer);
        }

        // ── Step 5: Restore scripts and styles ──
        $buffer = str_replace(array_keys($scripts), array_values($scripts), $buffer);
        $buffer = str_replace(array_keys($styles),  array_values($styles),  $buffer);

        return $buffer;
    }
}
?>