<?php
// db/add_foreign_keys.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Helper function to check if constraint exists
    function constraintExists($pdo, $table, $constraint) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND CONSTRAINT_NAME = ?
        ");
        $stmt->execute([$table, $constraint]);
        return $stmt->fetchColumn() > 0;
    }

    echo "=== Running Foreign Key Migrations ===\n";

    // 1. completed_challenges.client_id -> client.client_id
    if (!constraintExists($pdo, 'completed_challenges', 'fk_completed_challenges_client')) {
        $pdo->exec("ALTER TABLE completed_challenges ADD CONSTRAINT fk_completed_challenges_client FOREIGN KEY (client_id) REFERENCES client(client_id) ON DELETE CASCADE");
        echo "✅ Added foreign key completed_challenges.client_id -> client.client_id\n";
    } else {
        echo "⏭️  Foreign key completed_challenges.client_id already exists\n";
    }

    // 2. client_mood_history.client_id -> client.client_id
    if (!constraintExists($pdo, 'client_mood_history', 'fk_client_mood_history_client')) {
        $pdo->exec("ALTER TABLE client_mood_history ADD CONSTRAINT fk_client_mood_history_client FOREIGN KEY (client_id) REFERENCES client(client_id) ON DELETE CASCADE");
        echo "✅ Added foreign key client_mood_history.client_id -> client.client_id\n";
    } else {
        echo "⏭️  Foreign key client_mood_history.client_id already exists\n";
    }

    // 3. daily_challenges.client_id -> client.client_id
    if (!constraintExists($pdo, 'daily_challenges', 'fk_daily_challenges_client')) {
        $pdo->exec("ALTER TABLE daily_challenges ADD CONSTRAINT fk_daily_challenges_client FOREIGN KEY (client_id) REFERENCES client(client_id) ON DELETE CASCADE");
        echo "✅ Added foreign key daily_challenges.client_id -> client.client_id\n";
    } else {
        echo "⏭️  Foreign key daily_challenges.client_id already exists\n";
    }

    // 4. journal_entry.client_id -> client.client_id
    if (!constraintExists($pdo, 'journal_entry', 'fk_journal_entry_client')) {
        $pdo->exec("ALTER TABLE journal_entry ADD CONSTRAINT fk_journal_entry_client FOREIGN KEY (client_id) REFERENCES client(client_id) ON DELETE CASCADE");
        echo "✅ Added foreign key journal_entry.client_id -> client.client_id\n";
    } else {
        echo "⏭️  Foreign key journal_entry.client_id already exists\n";
    }

    // 5. helpful_votes.user_id -> user.user_id
    // First alter user_id type to VARCHAR(50) so it matches user.user_id exactly
    $pdo->exec("ALTER TABLE helpful_votes MODIFY user_id VARCHAR(50) NOT NULL");
    echo "✅ Standardized helpful_votes.user_id column width to VARCHAR(50)\n";

    if (!constraintExists($pdo, 'helpful_votes', 'fk_helpful_votes_user')) {
        $pdo->exec("ALTER TABLE helpful_votes ADD CONSTRAINT fk_helpful_votes_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE");
        echo "✅ Added foreign key helpful_votes.user_id -> user.user_id\n";
    } else {
        echo "⏭️  Foreign key helpful_votes.user_id already exists\n";
    }

    // 6. private_session_presence.private_session_id -> private_sessions.private_session_id
    if (!constraintExists($pdo, 'private_session_presence', 'fk_presence_private_sessions')) {
        $pdo->exec("ALTER TABLE private_session_presence ADD CONSTRAINT fk_presence_private_sessions FOREIGN KEY (private_session_id) REFERENCES private_sessions(private_session_id) ON DELETE CASCADE");
        echo "✅ Added foreign key private_session_presence.private_session_id -> private_sessions.private_session_id\n";
    } else {
        echo "⏭️  Foreign key private_session_presence.private_session_id already exists\n";
    }

    // 7. private_session_presence.user_id -> user.user_id
    if (!constraintExists($pdo, 'private_session_presence', 'fk_presence_user')) {
        $pdo->exec("ALTER TABLE private_session_presence ADD CONSTRAINT fk_presence_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE");
        echo "✅ Added foreign key private_session_presence.user_id -> user.user_id\n";
    } else {
        echo "⏭️  Foreign key private_session_presence.user_id already exists\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "🎉 All foreign key alterations completed successfully!\n";

} catch (PDOException $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "❌ Error applying constraints: " . $e->getMessage() . "\n";
}
