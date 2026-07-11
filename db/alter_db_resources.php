<?php
require_once '../includes/db_connect.php';
try {
    $pdo->exec("DROP TABLE IF EXISTS Resource;");

    $sql = "CREATE TABLE Resource (
        resource_id INT AUTO_INCREMENT PRIMARY KEY,
        therapist_id VARCHAR(50) DEFAULT NULL,
        title VARCHAR(150) NOT NULL,
        type VARCHAR(50) NOT NULL,
        category VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        link_url VARCHAR(255) DEFAULT '#',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (therapist_id) REFERENCES therapist(therapist_id) ON DELETE SET NULL
    );";
    $pdo->exec($sql);

    $mock_data = [
        ['Understanding Anxiety', 'Article', 'Mental Health', 'Anxiety is a normal emotion. It’s your brain’s way of reacting to stress and alerting you of potential danger ahead...', 'assets/images/resource_anxiety.jpg'],
        ['The Power of Active Listening', 'Video', 'Training', 'Learn how to truly listen to others without judgment. This skill is essential for volunteers and therapists alike.', 'assets/images/resource_listening.jpg'],
        ['5 Minute Meditation', 'Audio', 'Mindfulness', 'A quick guided meditation to help you reset your day and find your center.', 'assets/images/resource_meditation.jpg'],
        ['Coping with Grief', 'Article', 'Support', 'Grief is a multifaceted response to loss. Here are some strategies to help you navigate this difficult time.', 'assets/images/resource_grief.jpg'],
        ['Building Resilience', 'Workshop', 'Self-Improvement', 'Resilience is not just about bouncing back, it’s about growing through adversity.', 'assets/images/resource_resilience.jpg']
    ];

    $insert = $pdo->prepare("INSERT INTO Resource (title, type, category, content, image_url) VALUES (?, ?, ?, ?, ?)");
    foreach ($mock_data as $res) {
        $insert->execute($res);
    }

    echo "Success: Recreated Resource table and seeded data.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>