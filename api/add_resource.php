<?php
// api/add_resource.php
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE)
    session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Therapist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$therapist_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$type = $_POST['type'] ?? 'Article';
$category = $_POST['category'] ?? 'Mental Health';
$content = trim($_POST['content'] ?? '');
$link_url = trim($_POST['link_url'] ?? '#');

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Title and Content are required.']);
    exit();
}

try {
    // We don't have image upload yet, so let's pick a default based on category or type
    $image_url = 'assets/images/default_resource.jpg';
    if ($type === 'Video')
        $image_url = 'assets/images/resource_listening.jpg';
    elseif ($category === 'Mindfulness')
        $image_url = 'assets/images/resource_meditation.jpg';
    elseif ($category === 'Anxiety')
        $image_url = 'assets/images/resource_anxiety.jpg';

    $resource_id = uniqid('RES_');
    $stmt = $pdo->prepare("INSERT INTO Resource (resource_id, title, type, category, content, url, image_url, added_by_therapist_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$resource_id, $title, $type, $category, $content, $link_url, $image_url, $therapist_id]);

    echo json_encode(['success' => true, 'message' => 'Resource added successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>