<?php
require_once '../includes/db_connect.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS Therapist_Availability (
        availability_id INT AUTO_INCREMENT PRIMARY KEY,
        therapist_id VARCHAR(50) NOT NULL,
        day_of_week VARCHAR(20) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (therapist_id) REFERENCES therapist(therapist_id) ON DELETE CASCADE
    );";
    $pdo->exec($sql);
    echo "Success";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>