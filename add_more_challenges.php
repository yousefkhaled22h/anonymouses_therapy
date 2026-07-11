<?php
require_once 'includes/db_connect.php';

$new_challenges = [
    ['ch_11', 'Take stairs (5 floors)', 'Choose the stairs over the elevator for at least 5 floors.', 'Medium', 15],
    ['ch_12', 'Drink herbal tea (1 cup)', 'Enjoy a soothing cup of herbal tea.', 'Easy', 5],
    ['ch_13', 'Morning sunlight (10 mins)', 'Spend 10 minutes in the morning sun for Vitamin D.', 'Easy', 10],
    ['ch_14', 'Evening walk (15 mins)', 'Take a relaxing 15-minute walk in the evening.', 'Medium', 15],
    ['ch_15', 'Limit caffeine (1 cup)', 'Stick to just one cup of caffeine today.', 'Medium', 10],
    ['ch_16', 'Write gratitude (3 things)', 'List 3 things you are grateful for today.', 'Easy', 5],
    ['ch_17', 'No sugar snacks (1 day)', 'Avoid sugary snacks for the entire day.', 'Hard', 20],
    ['ch_18', 'Clean your room (10 mins)', 'Spend 10 minutes tidying up your personal space.', 'Easy', 10],
    ['ch_19', 'Listen to podcast (10 mins)', 'Listen to an educational or uplifting podcast.', 'Easy', 10],
    ['ch_20', 'Practice mindfulness (5 mins)', 'Take 5 minutes to be present and mindful.', 'Easy', 5],
    ['ch_21', 'Compliment someone (1 time)', 'Give a genuine compliment to someone.', 'Easy', 5],
    ['ch_22', 'Drink no soda (1 day)', 'Avoid soda and sugary drinks for 24 hours.', 'Medium', 15],
    ['ch_23', 'Stretch neck/back (5 mins)', 'Relieve tension with 5 minutes of stretching.', 'Easy', 5],
    ['ch_24', 'Plan tomorrow (5 mins)', 'Spend 5 minutes planning your next day.', 'Easy', 5],
    ['ch_25', 'Declutter desk (5 mins)', 'Clear off your desk for a fresh start.', 'Easy', 5],
    ['ch_26', 'Practice breathing (3 mins)', 'Do a 3-minute focused breathing exercise.', 'Easy', 5],
    ['ch_27', 'No phone meals (1 meal)', 'Eat one meal without using your phone.', 'Medium', 10],
    ['ch_28', 'Smile at others (3 times)', 'Share a smile with three different people.', 'Easy', 5],
    ['ch_29', 'Drink warm water (1 glass)', 'Sip a glass of warm water for digestion.', 'Easy', 5],
    ['ch_30', 'Do nothing (5 mins)', 'Sit in silence and do absolutely nothing for 5 minutes.', 'Medium', 5]
];

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO daily_challenges (challenge_id, title, description, difficulty, points) VALUES (?, ?, ?, ?, ?)");
    foreach ($new_challenges as $c) {
        $stmt->execute($c);
    }
    echo "20 new challenges added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
