<?php
require_once 'includes/db_connect.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `community_comment` (
          `comment_id` varchar(50) NOT NULL,
          `post_id` varchar(50) NOT NULL,
          `user_id` varchar(50) NOT NULL,
          `content` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`comment_id`),
          KEY `post_id` (`post_id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `community_comment_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_qna` (`post_id`) ON DELETE CASCADE,
          CONSTRAINT `community_comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Successfully created community_comment table.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>