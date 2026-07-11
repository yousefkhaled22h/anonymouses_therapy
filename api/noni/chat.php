<?php
// api/noni/chat.php - Noni AI Concierge Response Engine (Gemini LLM)
require_once '../../includes/db_connect.php';
require_once '../../config/ai_api.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => "Please log in to chat with Noni."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['reply' => "I'm here whenever you're ready. 💙"]);
    exit();
}

// ───────────────────────────────────────────────
// GEMINI API INTEGRATION
// ───────────────────────────────────────────────

$userRole = $_SESSION['role'] ?? 'User';
$userName = $_SESSION['name'] ?? 'Friend';

// Dynamic prompt injecting user's state
$dynamicPrompt = NONI_SYSTEM_PROMPT . "\n\nThe user you are talking to is named $userName. Their role on the platform is: $userRole.\nIf they ask for a therapist match, append '[[ACTION: FIND_THERAPIST]]' at the very end of your message.\nIf they ask for urgent crisis help, append '[[ACTION: CRISIS]]'.";

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => "System Context:\n" . $dynamicPrompt . "\n\nUser Message: " . $message]
            ]
        ]
    ]
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    // Fallback if API key is invalid or not set
    if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY') {
        echo json_encode([
            'reply' => "I'm currently resting. Please ask the admin to configure my Gemini API key so I can help you! 🌿",
            'type'  => 'info'
        ]);
        exit();
    }
    echo json_encode(['reply' => "I'm having a little trouble connecting my thoughts right now. Could you try again? 🌿"]);
    exit();
}

$responseData = json_decode($response, true);
$aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? "I'm here for you. 🌿";

$type = 'info';
$therapist = null;
$hotlines = [];

// Parse Actions
if (strpos($aiReply, '[[ACTION: FIND_THERAPIST]]') !== false) {
    $aiReply = str_replace('[[ACTION: FIND_THERAPIST]]', '', $aiReply);
    
    // Instead of matching a random one, just tell the frontend to show the specialized UI card
    try {
        $stmt2 = $pdo->prepare("SELECT t.first_name, t.last_name, t.specialties, t.hourly_rate, t.rating, t.bio, u.user_id FROM therapist t JOIN user u ON t.user_id = u.user_id WHERE t.verified=1 ORDER BY t.rating DESC LIMIT 1");
        $stmt2->execute();
        $therapist = $stmt2->fetch(PDO::FETCH_ASSOC);
        $type = 'match';
    } catch (PDOException $e) {}
}

if (strpos($aiReply, '[[ACTION: CRISIS]]') !== false) {
    $aiReply = str_replace('[[ACTION: CRISIS]]', '', $aiReply);
    $type = 'crisis';
    $hotlines = [
        ['name' => 'Crisis Text Line — Text HOME to 741741', 'url' => 'https://www.crisistextline.org/'],
        ['name' => 'Befrienders Worldwide', 'url' => 'https://www.befrienders.org/'],
    ];
}

echo json_encode([
    'reply'     => trim($aiReply),
    'type'      => $type,
    'therapist' => $therapist,
    'hotlines'  => $hotlines
]);
