<?php
// alter_db_therapist_cv.php
require_once '../includes/db_connect.php';

try {
    // Add CV related columns to therapist table
    $sql = "ALTER TABLE therapist 
            ADD COLUMN IF NOT EXISTS education TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS experience_details TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS languages TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT 'assets/images/default_avatar.jpg';";

    $pdo->exec($sql);
    echo "Therapist table updated successfully for CV profiles.\n";

    // Seed some example data for existing therapists if any
    $update_sql = "UPDATE therapist SET 
        education = 'Ph.D. in Clinical Psychology, Stanford University',
        experience_details = '10+ years of private practice in Cognitive Behavioral Therapy. Former lead counselor at Mental Health Clinic.',
        languages = 'English, Spanish',
        profile_image = 'assets/images/therapist1.jpg'
        WHERE education IS NULL LIMIT 1;";
    $pdo->exec($update_sql);

} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>