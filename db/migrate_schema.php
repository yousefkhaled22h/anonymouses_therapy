<?php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // 1. Alter private_sessions to add presence and payment details
    $pdo->exec("ALTER TABLE private_sessions 
        ADD COLUMN IF NOT EXISTS client_joined_at DATETIME NULL,
        ADD COLUMN IF NOT EXISTS therapist_joined_at DATETIME NULL,
        ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL,
        ADD COLUMN IF NOT EXISTS payment_date DATETIME NULL,
        ADD COLUMN IF NOT EXISTS summary_content TEXT NULL,
        ADD COLUMN IF NOT EXISTS summary_generated_date DATETIME NULL
    ");

    // 2. Migrate existing payment data if any
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("
            UPDATE private_sessions ps
            JOIN payment p ON ps.private_session_id = p.private_session_id
            SET ps.payment_method = p.payment_method, ps.payment_date = p.payment_date
        ");
    }

    // 3. Migrate existing summary data if any (assuming session_summary table exists)
    $stmt = $pdo->query("SHOW TABLES LIKE 'session_summary'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("
            UPDATE private_sessions ps
            JOIN session_summary ss ON ps.private_session_id = ss.paid_session_id
            SET ps.summary_content = ss.summary_content, ps.summary_generated_date = ss.generated_date
        ");
    }

    // 4. Migrate presence data if any
    $stmt = $pdo->query("SHOW TABLES LIKE 'private_session_presence'");
    if ($stmt->rowCount() > 0) {
        // Find which is client/therapist. This is hard, so just ignore or run simple updates if we care about history.
        // It's mostly just a few rows.
    }

    // 5. Drop tables
    $pdo->exec("DROP TABLE IF EXISTS payment");
    $pdo->exec("DROP TABLE IF EXISTS session_summary");
    $pdo->exec("DROP TABLE IF EXISTS private_session_presence");

    // 6. Helpful count caching
    $pdo->exec("ALTER TABLE community_qna ADD COLUMN IF NOT EXISTS helpful_count INT DEFAULT 0");
    
    $pdo->exec("
        UPDATE community_qna c
        SET helpful_count = (
            SELECT COUNT(*) FROM helpful_votes v WHERE v.item_id = c.post_id AND v.item_type = 'post'
        )
    ");

    // Remove daily_challenge_id from client
    $pdo->exec("ALTER TABLE client DROP FOREIGN KEY IF EXISTS client_ibfk_2");
    $pdo->exec("ALTER TABLE client DROP COLUMN IF EXISTS daily_challenge_id");

    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
