<?php
// api/onboarding/match_therapist.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$q1 = $data['q1'] ?? '';   // frequency tag
$q2 = $data['q2'] ?? '';   // coping tag
$q3 = $data['q3'] ?? '';   // goal tag
$q4 = $data['q4'] ?? '';   // open text

// Build specialty priority list from tags
$specialty_map = [
    'High Intensity'           => ['Anxiety', 'Depression', 'Trauma'],
    'Medium Intensity'         => ['Anxiety', 'Depression'],
    'Low Intensity'            => ['Depression', 'Relationships'],
    'Casual'                   => ['Relationships'],
    'Skill-Seeker'             => ['Anxiety', 'Depression'],
    'Proactive'                => ['Relationships', 'Childhood'],
    'Avoidant'                 => ['Anxiety', 'Trauma'],
    'High-Need'                => ['Trauma', 'Anxiety'],
    'Anxiety-Focus'            => ['Anxiety'],
    'Trauma/Organization-Focus'=> ['Trauma'],
    'Loneliness-Focus'         => ['Relationships', 'Childhood'],
    'Depression-Focus'         => ['Depression'],
];

$priorities = [];
foreach ([$q1, $q2, $q3] as $tag) {
    if (isset($specialty_map[$tag])) {
        $priorities = array_merge($priorities, $specialty_map[$tag]);
    }
}

// Extract keywords from q4 open text
$text_keywords = [];
$anxiety_words = ['anxious', 'anxiety', 'panic', 'worry', 'worried', 'stress', 'nervous', 'overwhelm'];
$trauma_words  = ['trauma', 'abuse', 'ptsd', 'flashback', 'nightmare', 'hurt', 'past'];
$depression_words = ['sad', 'hopeless', 'empty', 'depressed', 'depression', 'numb', 'worthless', 'alone'];
$relationship_words = ['lonely', 'relationship', 'divorce', 'family', 'friend', 'partner', 'social'];

$text_lower = strtolower($q4);
foreach ($anxiety_words    as $w) if (strpos($text_lower, $w) !== false) { $priorities[] = 'Anxiety'; break; }
foreach ($trauma_words     as $w) if (strpos($text_lower, $w) !== false) { $priorities[] = 'Trauma'; break; }
foreach ($depression_words as $w) if (strpos($text_lower, $w) !== false) { $priorities[] = 'Depression'; break; }
foreach ($relationship_words as $w) if (strpos($text_lower, $w) !== false) { $priorities[] = 'Relationships'; break; }

// Count frequency of each specialty
$counts = array_count_values($priorities);
arsort($counts);
$top_specialty = key($counts) ?? 'Anxiety';

    // Fetch best matching therapist (verified, rated, matching specialty)
    try {
        $stmt = $pdo->prepare("
            SELECT t.therapist_id, t.first_name, t.last_name, t.bio, t.specialties,
                   t.hourly_rate, t.years_experience, t.rating, t.review_count,
                   t.profile_image, u.user_id
            FROM therapist t
            JOIN user u ON t.user_id = u.user_id
            WHERE t.specialties LIKE ? AND t.verified = 1
            ORDER BY t.rating DESC, t.years_experience DESC
            LIMIT 1
        ");
        $stmt->execute(['%' . $top_specialty . '%']);
        $therapist = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$therapist) {
            // Fallback: any verified therapist
            $stmt2 = $pdo->prepare("
                SELECT t.therapist_id, t.first_name, t.last_name, t.bio, t.specialties,
                       t.hourly_rate, t.years_experience, t.rating, t.review_count,
                       t.profile_image, t.user_id
                FROM therapist t
                WHERE t.verified = 1
                ORDER BY t.rating DESC
                LIMIT 1
            ");
            $stmt2->execute();
            $therapist = $stmt2->fetch(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'status'         => 'success',
            'therapist'      => $therapist,
            'matched_tag'    => $top_specialty,
            'ai_reflection'  => null // Privacy Protocol: AI reflections disabled
        ]);

    } catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
