<?php
// alter_db_qna.php
require_once '../includes/db_connect.php';

try {
    // Add Q&A related columns to community_qna table
    // user_id will store the questioner (can be 'anonymous').
    // post_type will be 'question'.
    $sql = "ALTER TABLE community_qna 
            ADD COLUMN IF NOT EXISTS answer_content TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS answered_by_therapist_id VARCHAR(50) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS answered_at TIMESTAMP NULL DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS is_public TINYINT(1) DEFAULT 1,
            MODIFY COLUMN user_id VARCHAR(50) NULL;"; # Allow NULL for truly anonymous or system posts

    $pdo->exec($sql);
    echo "Community QNA table updated successfully for Q&A Hub.\n";

} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>