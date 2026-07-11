<?php
// api/report/submit_report.php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$reporter_id = $_SESSION['user_id'];
$reporter_role = strtolower($_SESSION['role'] ?? '');

$reported_id = $_POST['reported_user_id'] ?? '';
$report_type = $_POST['report_type'] ?? '';
$description = trim($_POST['description'] ?? '');

if (empty($reported_id) || empty($report_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

try {
    // Prevent self-reporting
    if ($reporter_id === $reported_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report yourself.']);
        exit();
    }

    // Fetch the role of the reported user to enforce hierarchy
    $stmt = $pdo->prepare("SELECT role FROM user WHERE user_id = ?");
    $stmt->execute([$reported_id]);
    $reported_role = strtolower($stmt->fetchColumn() ?: '');

    if (!$reported_role) {
         echo json_encode(['success' => false, 'message' => 'Reported user not found.']);
         exit();
    }

    // Hierarchy Logic
    // Therapists cannot report other Therapists.
    // Volunteers cannot report Therapists.
    if ($reporter_role === 'therapist' && $reported_role === 'therapist') {
         echo json_encode(['success' => false, 'message' => 'Therapists cannot report other Therapists.']);
         exit();
    }
    if ($reporter_role === 'volunteer' && $reported_role === 'therapist') {
         echo json_encode(['success' => false, 'message' => 'Volunteers cannot report Therapists.']);
         exit();
    }

    // Insert Report
    $report_id = 'rep_' . bin2hex(random_bytes(8));
    
    $stmt = $pdo->prepare("
        INSERT INTO report (report_id, reporter_user_id, reported_user_id, report_type, description, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    
    $stmt->execute([$report_id, $reporter_id, $reported_id, $report_type, $description]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);

} catch (PDOException $e) {
    error_log("Report Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>
