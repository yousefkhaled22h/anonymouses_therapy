<?php
// api/submit_notes.php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// In a real application, you would validate and sanitize these inputs
// and insert them into a 'progress_notes' table.
// For now, we will simulate a successful save.

// $patient_first_name = $_POST['patient_first_name'] ?? '';
// ... other fields ...

// Mock success
echo json_encode(['success' => true, 'message' => 'Progress note saved successfully!']);
?>