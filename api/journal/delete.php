<?php
// api/journal/delete.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$entry_id = $_POST['entry_id'] ?? null;
if (!$entry_id) { echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit(); }

$client_id = $_SESSION['client_id'] ?? null;
if (!$client_id) {
    $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client_id = $stmt->fetchColumn();
}

try {
    $stmt = $pdo->prepare("DELETE FROM journal_entry WHERE entry_id=? AND client_id=?");
    $stmt->execute([$entry_id, $client_id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
