<?php
// db/migrate_data.php - Database migration and data preservation script
require_once __DIR__ . '/../includes/db_connect.php';

echo "<h2>Starting Database Migration</h2><pre style='font-family:monospace;font-size:14px'>";

try {
    // 1. Create community_posts table
    $sql_posts = "CREATE TABLE IF NOT EXISTS `community_posts` (
        `post_id` VARCHAR(50) PRIMARY KEY,
        `user_id` VARCHAR(50) NULL,
        `parent_id` VARCHAR(50) NULL,
        `post_type` ENUM('forum', 'qna', 'reply') NOT NULL,
        `category` VARCHAR(50) NOT NULL DEFAULT 'General',
        `content` TEXT NOT NULL,
        `report_status` VARCHAR(50) DEFAULT NULL,
        `is_public` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`parent_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql_posts);
    echo "✅ Table community_posts created successfully.\n";

    // 2. Create client_activity_log table
    $sql_activity = "CREATE TABLE IF NOT EXISTS `client_activity_log` (
        `activity_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` VARCHAR(50) NOT NULL,
        `activity_type` ENUM('game_session', 'grounding_session', 'user_artwork', 'user_safe_space') NOT NULL,
        `game_type` VARCHAR(50) DEFAULT NULL,
        `duration_seconds` INT DEFAULT NULL,
        `payload` LONGTEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
        INDEX `idx_user_activity` (`user_id`, `activity_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sql_activity);
    echo "✅ Table client_activity_log created successfully.\n";

    // 3. Alter resource table schema
    $resource_cols_stmt = $pdo->query("SHOW COLUMNS FROM Resource");
    $resource_cols = $resource_cols_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('description', $resource_cols)) {
        $pdo->exec("ALTER TABLE Resource CHANGE COLUMN description content TEXT NOT NULL");
        echo "✅ Renamed description column to content in Resource table.\n";
    }

    if (!in_array('image_url', $resource_cols)) {
        $pdo->exec("ALTER TABLE Resource ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
        echo "✅ Added image_url column to Resource table.\n";
    }

    // 4. Migrate Community Q&A and Forum posts
    $qna_count = $pdo->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
    if ($qna_count == 0) {
        echo "Migrating community forum & Q&A data...\n";

        // Fetch old Q&A and forum posts
        $old_qna_stmt = $pdo->query("SELECT * FROM community_qna");
        $old_qnas = $old_qna_stmt->fetchAll(PDO::FETCH_ASSOC);

        $inserted_posts = 0;
        $inserted_replies = 0;

        foreach ($old_qnas as $row) {
            $post_id = $row['post_id'];
            $user_id = $row['user_id'];
            $content = $row['content'] ?? '';
            $report_status = $row['report_status'];
            $is_public = $row['is_public'] ?? 1;
            $created_at = $row['created_at'];

            // Determine if it was a question or a forum post
            if ($row['post_type'] === 'question') {
                $post_type = 'qna';
                $category = $row['category'] ?? 'General';
            } else {
                $post_type = 'forum';
                $category = $row['post_type'] ?? 'General'; // post_type holds category name for forum posts
            }

            // Insert post
            $ins_post = $pdo->prepare("INSERT INTO community_posts (post_id, user_id, parent_id, post_type, category, content, report_status, is_public, created_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)");
            $ins_post->execute([$post_id, $user_id, $post_type, $category, $content, $report_status, $is_public, $created_at]);
            $inserted_posts++;

            // Migrate therapist answers if they exist
            if (!empty($row['answer_content'])) {
                $reply_id = 'cmt_ans_' . bin2hex(random_bytes(8));
                $ans_user_id = null;

                // Map therapist_id from answered_by_therapist_id to user_id in user table
                if (!empty($row['answered_by_therapist_id'])) {
                    $t_stmt = $pdo->prepare("SELECT user_id FROM therapist WHERE therapist_id = ?");
                    $t_stmt->execute([$row['answered_by_therapist_id']]);
                    $ans_user_id = $t_stmt->fetchColumn() ?: null;
                }

                if ($ans_user_id) {
                    $ins_reply = $pdo->prepare("INSERT INTO community_posts (post_id, user_id, parent_id, post_type, category, content, created_at) VALUES (?, ?, ?, 'reply', ?, ?, ?)");
                    $ins_reply->execute([$reply_id, $ans_user_id, $post_id, $category, $row['answer_content'], $row['answered_at'] ?? $created_at]);
                    $inserted_replies++;
                }
            }
        }
        echo "✅ Migrated $inserted_posts posts and $inserted_replies therapist answers.\n";

        // Fetch comments
        $old_comments_stmt = $pdo->query("SELECT * FROM community_comment");
        $old_comments = $old_comments_stmt->fetchAll(PDO::FETCH_ASSOC);

        $inserted_comments = 0;
        foreach ($old_comments as $row) {
            $comment_id = $row['comment_id'];
            $post_id = $row['post_id'];
            $user_id = $row['user_id'];
            $content = $row['content'] ?? '';
            $created_at = $row['created_at'];

            $ins_comment = $pdo->prepare("INSERT INTO community_posts (post_id, user_id, parent_id, post_type, content, created_at) VALUES (?, ?, ?, 'reply', ?, ?)");
            $ins_comment->execute([$comment_id, $user_id, $post_id, $content, $created_at]);
            $inserted_comments++;
        }
        echo "✅ Migrated $inserted_comments replies/comments.\n";
    } else {
        echo "⏭️  community_posts already contains data. Skipping Q&A migration.\n";
    }

    // 5. Migrate Client Activities (mini-games)
    $activity_count = $pdo->query("SELECT COUNT(*) FROM client_activity_log")->fetchColumn();
    if ($activity_count == 0) {
        echo "Migrating client interactive activities...\n";

        // game_session
        $gs_stmt = $pdo->query("SELECT * FROM game_session");
        $gs_rows = $gs_stmt->fetchAll(PDO::FETCH_ASSOC);
        $inserted_gs = 0;
        foreach ($gs_rows as $row) {
            $ins = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, game_type, duration_seconds, created_at) VALUES (?, 'game_session', ?, ?, ?)");
            $ins->execute([$row['user_id'], $row['game_type'], $row['duration_seconds'], $row['played_at']]);
            $inserted_gs++;
        }
        echo "✅ Migrated $inserted_gs game sessions.\n";

        // grounding_session
        $gr_stmt = $pdo->query("SELECT * FROM grounding_session");
        $gr_rows = $gr_stmt->fetchAll(PDO::FETCH_ASSOC);
        $inserted_gr = 0;
        foreach ($gr_rows as $row) {
            $ins = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, payload, created_at) VALUES (?, 'grounding_session', ?, ?)");
            $ins->execute([$row['user_id'], $row['responses'], $row['created_at']]);
            $inserted_gr++;
        }
        echo "✅ Migrated $inserted_gr grounding sessions.\n";

        // user_artwork
        $art_stmt = $pdo->query("SELECT * FROM user_artwork");
        $art_rows = $art_stmt->fetchAll(PDO::FETCH_ASSOC);
        $inserted_art = 0;
        foreach ($art_rows as $row) {
            $ins = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, game_type, payload, created_at, updated_at) VALUES (?, 'user_artwork', ?, ?, ?, ?)");
            $ins->execute([$row['user_id'], $row['artwork_type'], $row['svg_data'], $row['updated_at'], $row['updated_at']]);
            $inserted_art++;
        }
        echo "✅ Migrated $inserted_art user artwork drawings.\n";

        // user_safe_space
        $ss_stmt = $pdo->query("SELECT * FROM user_safe_space");
        $ss_rows = $ss_stmt->fetchAll(PDO::FETCH_ASSOC);
        $inserted_ss = 0;
        foreach ($ss_rows as $row) {
            $ins = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, payload, created_at, updated_at) VALUES (?, 'user_safe_space', ?, ?, ?)");
            $ins->execute([$row['user_id'], $row['design_data'], $row['updated_at'], $row['updated_at']]);
            $inserted_ss++;
        }
        echo "✅ Migrated $inserted_ss user safe-space configurations.\n";
    } else {
        echo "⏭️  client_activity_log already contains data. Skipping mini-game activities migration.\n";
    }

    // 6. Migrate Mood entries from emotional_dashboard to mood_history
    echo "Syncing mood logs to mood_history...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS mood_history (
        history_id VARCHAR(50) PRIMARY KEY,
        client_id VARCHAR(50) NOT NULL,
        mood VARCHAR(50) NOT NULL,
        recorded_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $dash_moods_stmt = $pdo->query("SELECT * FROM emotional_dashboard WHERE type = 'mood'");
    $dash_moods = $dash_moods_stmt->fetchAll(PDO::FETCH_ASSOC);
    $synced_moods = 0;

    foreach ($dash_moods as $row) {
        $client_id = $row['client_id'];
        $mood = $row['content'];
        $recorded_date = $row['recorded_date'];
        $created_at = $row['created_at'];

        // Check if already in mood_history
        $chk = $pdo->prepare("SELECT COUNT(*) FROM mood_history WHERE client_id = ? AND recorded_date = ?");
        $chk->execute([$client_id, $recorded_date]);
        if ($chk->fetchColumn() == 0) {
            $history_id = 'mood_synced_' . bin2hex(random_bytes(6));
            $ins = $pdo->prepare("INSERT INTO mood_history (history_id, client_id, mood, recorded_date, created_at) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$history_id, $client_id, $mood, $recorded_date, $created_at]);
            $synced_moods++;
        }
    }
    echo "✅ Synced $synced_moods mood logs into mood_history.\n";

    echo "\n🎉 Database Migration and Data Preservation completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
