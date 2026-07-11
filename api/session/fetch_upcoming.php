<?php
// api/session/fetch_upcoming.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// ── Auto-ensure optional columns exist (safe to run every request) ──────────
$optional_cols = [
    'early_start_requested' => "ALTER TABLE private_sessions ADD COLUMN early_start_requested TINYINT(1) NOT NULL DEFAULT 0",
    'proposed_datetime'     => "ALTER TABLE private_sessions ADD COLUMN proposed_datetime DATETIME NULL",
    'reschedule_requested'  => "ALTER TABLE private_sessions ADD COLUMN reschedule_requested TINYINT(1) NOT NULL DEFAULT 0",
    'payment_status'        => "ALTER TABLE private_sessions ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'paid'",
    'duration_minutes'      => "ALTER TABLE private_sessions ADD COLUMN duration_minutes INT NOT NULL DEFAULT 60",
    'cancel_reason'         => "ALTER TABLE private_sessions ADD COLUMN cancel_reason TEXT NULL",
    'cancellation_acknowledged' => "ALTER TABLE private_sessions ADD COLUMN cancellation_acknowledged TINYINT(1) NOT NULL DEFAULT 0",
];
// Only check once per PHP session to avoid repeated SHOW COLUMNS calls
if (empty($_SESSION['_cols_verified'])) {
    try {
        $existing = [];
        foreach ($pdo->query("SHOW COLUMNS FROM private_sessions") as $c) {
            $existing[] = $c['Field'];
        }
        foreach ($optional_cols as $col => $ddl) {
            if (!in_array($col, $existing)) {
                try { $pdo->exec($ddl); } catch (PDOException $ignored) {}
            }
        }
        $_SESSION['_cols_verified'] = true;
    } catch (PDOException $ignored) {}
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Client';
$sessions  = [];

// ── Allowed statuses — ALL cases covered ────────────────────────────────────
$status_list = "('active','scheduled','reserved','pending','confirmed','RESERVED','PENDING','Active','scheduled')";

try {
    if ($user_role == 'Client') {
        $client_id = $_SESSION['client_id'] ?? null;
        if (!$client_id) {
            $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $client_id = $stmt->fetchColumn();
            $_SESSION['client_id'] = $client_id;
        }

        if ($client_id) {
            $stmt = $pdo->prepare("
                SELECT
                    ps.private_session_id          AS id,
                    ps.session_date,
                    'One-on-One'                AS type,
                    CONCAT(t.first_name,' ',t.last_name) AS partner_name,
                    ps.status                   AS session_status,
                    ps.communication_method,
                    IFNULL(ps.early_start_requested, 0)  AS early_start_requested,
                    ps.proposed_datetime,
                    IFNULL(ps.reschedule_requested, 0)   AS reschedule_requested,
                    ps.cancel_reason,
                    IFNULL(ps.cancellation_acknowledged, 0) AS cancellation_acknowledged,
                    ps.therapist_id,
                    IFNULL(ps.amount, 0)        AS amount,
                    t.zoom_link,
                    ps.early_start_to
                FROM private_sessions ps
                JOIN therapist t ON ps.therapist_id = t.therapist_id
                WHERE ps.client_id = ?
                  AND (
                      (LOWER(ps.status) IN ('active','scheduled','reserved','pending','confirmed','pending_reschedule') AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= DATE_SUB(NOW(), INTERVAL 30 MINUTE))
                      OR (LOWER(ps.status) = 'cancelled' AND IFNULL(ps.cancellation_acknowledged, 0) = 0 AND (ps.cancel_reason IS NULL OR ps.cancel_reason NOT LIKE 'Cancelled by Client%'))
                  )
                ORDER BY ps.session_date ASC
            ");
            $stmt->execute([$client_id]);
            $sessions = array_merge($sessions, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

    } elseif ($user_role == 'Therapist') {
        $therapist_id = $_SESSION['therapist_id'] ?? null;
        if (!$therapist_id) {
            $stmt = $pdo->prepare("SELECT therapist_id FROM therapist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $therapist_id = $stmt->fetchColumn();
            $_SESSION['therapist_id'] = $therapist_id;
        }

        if ($therapist_id) {
            $stmt = $pdo->prepare("
                SELECT
                    ps.private_session_id          AS id,
                    ps.session_date,
                    'One-on-One'                AS type,
                    COALESCE(c.name, c.anonymous_id, 'Client') AS partner_name,
                    ps.status                   AS session_status,
                    ps.communication_method,
                    IFNULL(ps.early_start_requested, 0)  AS early_start_requested,
                    ps.proposed_datetime,
                    IFNULL(ps.reschedule_requested, 0)   AS reschedule_requested,
                    ps.therapist_id,
                    IFNULL(ps.amount, 0)        AS amount,
                    NULL                        AS zoom_link
                FROM private_sessions ps
                JOIN client c ON ps.client_id = c.client_id
                WHERE ps.therapist_id = ?
                  AND LOWER(ps.status) IN ('active','scheduled','reserved','pending','confirmed')
                  AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= NOW()
                ORDER BY ps.session_date ASC
            ");
            $stmt->execute([$therapist_id]);
            $sessions = array_merge($sessions, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    }

    // ── Group Sessions (optional, non-fatal) ─────────────────────────────────
    try {
        $stmt = $pdo->prepare("
            SELECT
                gs.group_session_id AS id,
                gs.session_date,
                'Group Session'     AS type,
                gs.topic            AS partner_name,
                gs.status           AS session_status,
                'Group Session'     AS communication_method,
                0                   AS early_start_requested,
                NULL                AS proposed_datetime,
                0                   AS reschedule_requested,
                NULL                AS therapist_id,
                0                   AS amount,
                NULL                AS zoom_link
            FROM group_sessions gs
            JOIN group_session_participants gsp ON gs.group_session_id = gsp.group_session_id
            WHERE gsp.user_id = ?
              AND LOWER(gs.status) IN ('active','scheduled')
              AND gs.session_date >= (NOW() - INTERVAL 2 HOUR)
        ");
        $stmt->execute([$user_id]);
        $sessions = array_merge($sessions, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $ge) {
        // Group session tables may not exist — skip silently
    }

    // ── Sort by date ─────────────────────────────────────────────────────────
    usort($sessions, function ($a, $b) {
        return strtotime($a['session_date']) - strtotime($b['session_date']);
    });

    // ── Format for JS ────────────────────────────────────────────────────────
    foreach ($sessions as &$s) {
        $ts = strtotime($s['session_date']);
        $s['formatted_date'] = date('D, M d · H:i', $ts);
        $s['is_joinable']    = (time() >= ($ts - 300)) || (strtolower($s['session_status']) === 'active');
        $s['countdown']      = max(1, round(($ts - time()) / 60));
        // Ensure booleans are ints for JS
        $s['early_start_requested'] = (int)($s['early_start_requested'] ?? 0);
        $s['reschedule_requested']  = (int)($s['reschedule_requested']  ?? 0);
        $s['cancellation_acknowledged'] = (int)($s['cancellation_acknowledged'] ?? 0);
    }

    echo json_encode(['status' => 'success', 'data' => $sessions]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
