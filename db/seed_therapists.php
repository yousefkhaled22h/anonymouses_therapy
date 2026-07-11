<?php
// seed_therapists.php
require_once '../includes/db_connect.php';

$therapists = [
    [
        'first_name' => 'John',
        'last_name' => 'Anderson',
        'email' => 'anderson@safehaven.com',
        'bio' => 'Licensed clinical psychologist specializing in cognitive behavioral therapy and mindfulness techniques for anxiety and depression management.',
        'specialties' => 'Anxiety, Depression, CBT, Mindfulness',
        'rate' => 120.00,
        'years' => 12,
        'rating' => 4.9,
        'reviews' => 234
    ],
    [
        'first_name' => 'Maria',
        'last_name' => 'Martinez',
        'email' => 'martinez@safehaven.com',
        'bio' => 'Expert in trauma-focused therapy with extensive experience in EMDR and somatic approaches.',
        'specialties' => 'Trauma, PTSD, EMDR, Somatic Therapy',
        'rate' => 150.00,
        'years' => 15,
        'rating' => 4.8,
        'reviews' => 189
    ],
    [
        'first_name' => 'David',
        'last_name' => 'Chen',
        'email' => 'chen@safehaven.com',
        'bio' => 'Focuses on relationship dynamics, communication skills, and attachment-based therapy.',
        'specialties' => 'Relationships, Couples Therapy, Communication, Attachment',
        'rate' => 130.00,
        'years' => 8,
        'rating' => 4.9,
        'reviews' => 167
    ],
    [
        'first_name' => 'Sarah',
        'last_name' => 'Williams',
        'email' => 'williams@safehaven.com',
        'bio' => 'Helps clients develop coping strategies for stress, burnout, and life transitions.',
        'specialties' => 'Stress, Burnout, Coping Strategies',
        'rate' => 110.00,
        'years' => 10,
        'rating' => 4.7,
        'reviews' => 203
    ]
];

echo "Seeding Therapists...\n";

foreach ($therapists as $t) {
    try {
        // 1. Create User
        $user_id = bin2hex(random_bytes(8));
        $hash = password_hash('password123', PASSWORD_DEFAULT);

        // Check if exists by email
        $check = $pdo->prepare("SELECT user_id FROM User WHERE email = ?");
        $check->execute([$t['email']]);
        if ($check->fetch()) {
            echo "Skipping {$t['first_name']} (already exists)\n";
            continue;
        }

        $stmt = $pdo->prepare("INSERT INTO User (user_id, email, password_hash, role) VALUES (?, ?, ?, 'therapist')");
        $stmt->execute([$user_id, $t['email'], $hash]);

        // 2. Create Profile
        $therapist_id = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("INSERT INTO Therapist (therapist_id, user_id, first_name, last_name, bio, specialties, hourly_rate, years_experience, rating, review_count, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$therapist_id, $user_id, $t['first_name'], $t['last_name'], $t['bio'], $t['specialties'], $t['rate'], $t['years'], $t['rating'], $t['reviews']]);

        // 3. Create Verification
        $ver_id = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("INSERT INTO Therapist_Verification (verification_id, therapist_id, verification_status) VALUES (?, ?, 'Approved')");
        $stmt->execute([$ver_id, $therapist_id]);

        echo "Created {$t['first_name']} {$t['last_name']}\n";

    } catch (PDOException $e) {
        echo "Error creating {$t['first_name']}: " . $e->getMessage() . "\n";
    }
}

echo "Done.";
?>