<?php
// api/admin/admin_action.php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

// 1. Verify Admin Role
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin session required.']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_role = $_SESSION['admin_role'] ?? 'Support Staff';

// 2. Helper function to check role permissions
function hasPermission($action_category) {
    global $admin_role;
    if ($admin_role === 'Super Admin') return true;

    switch ($action_category) {
        case 'user':
            return false; // Only Super Admin
        case 'therapist':
            return in_array($admin_role, ['Therapist Manager']);
        case 'session':
            return in_array($admin_role, ['Therapist Manager', 'Support Staff']);
        case 'report':
            return in_array($admin_role, ['Support Staff']);
        case 'challenge':
        case 'qa':
        case 'resource':
            return in_array($admin_role, ['Content Manager']);
        case 'notification':
            return in_array($admin_role, ['Support Staff']);
        default:
            return false;
    }
}

// Helper function to log audit events
function logAudit($action, $details) {
    global $pdo, $admin_id;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    logAdminAudit($pdo, $admin_id, $_SESSION['email'] ?? 'admin@safehaven.com', $action, $details, $ip_address);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
    exit();
}

try {
    switch ($action) {
        // --- 1. USER ACTIONS (Super Admin only) ---
        case 'edit_user':
            if (!hasPermission('user')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin only.']);
                exit();
            }
            $target_user_id = $_POST['target_user_id'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $status = $_POST['status'] ?? 'Active';
            $admin_role_val = $_POST['admin_role'] ?? null;
            $activity_score = (int)($_POST['activity_score'] ?? 0);

            if (empty($target_user_id) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            // Update user table
            $stmt = $pdo->prepare("UPDATE user SET email = ?, status = ?, admin_role = ?, activity_score = ? WHERE user_id = ?");
            $stmt->execute([$email, $status, $admin_role_val ?: null, $activity_score, $target_user_id]);

            logAudit('Edit User', "Edited user ID: $target_user_id, status: $status, role: $admin_role_val");
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
            break;

        case 'suspend_user':
        case 'reactivate_user':
        case 'delete_user':
            if (!hasPermission('user')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin only.']);
                exit();
            }
            $target_user_id = $_POST['target_user_id'] ?? '';
            if (empty($target_user_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
                exit();
            }

            $new_status = ($action === 'suspend_user') ? 'Suspended' : (($action === 'delete_user') ? 'Deleted' : 'Active');
            $stmt = $pdo->prepare("UPDATE user SET status = ? WHERE user_id = ?");
            $stmt->execute([$new_status, $target_user_id]);

            logAudit(ucwords(str_replace('_', ' ', $action)), "Set user ID $target_user_id status to $new_status");
            echo json_encode(['success' => true, 'message' => "User status updated to $new_status."]);
            break;

        case 'add_admin':
            if (!hasPermission('user')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin only.']);
                exit();
            }
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $admin_role_val = $_POST['admin_role'] ?? 'Support Staff';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
                exit();
            }

            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                exit();
            }

            // Check if email already exists in User table
            $check = $pdo->prepare("SELECT user_id FROM user WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already registered.']);
                exit();
            }

            try {
                $pdo->beginTransaction();

                $new_user_id = 'usr_' . bin2hex(random_bytes(8));
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert into User
                $ins_user = $pdo->prepare("INSERT INTO user (user_id, email, password_hash, role, admin_role, status) VALUES (?, ?, ?, 'admin', ?, 'Active')");
                $ins_user->execute([$new_user_id, $email, $hash, $admin_role_val]);

                // Insert into Admin
                $ins_admin = $pdo->prepare("INSERT INTO admin (admin_id, email, password_hash) VALUES (?, ?, ?)");
                $ins_admin->execute([$new_user_id, $email, $hash]);

                $pdo->commit();
                logAudit('Add Admin', "Created new Administrator: $email with role $admin_role_val");
                echo json_encode(['success' => true, 'message' => 'New Administrator added successfully.']);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        // --- 2. THERAPIST ACTIONS (Super Admin / Therapist Manager) ---
        case 'approve_therapist':
            if (!hasPermission('therapist')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Therapist Manager / Super Admin required.']);
                exit();
            }
            $therapist_id = $_POST['therapist_id'] ?? '';
            if (empty($therapist_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing therapist ID.']);
                exit();
            }

            $pdo->beginTransaction();
            // Verify therapist
            $stmt = $pdo->prepare("UPDATE therapist SET verified = 1 WHERE therapist_id = ?");
            $stmt->execute([$therapist_id]);

            // Update verification request if exists
            $stmt = $pdo->prepare("UPDATE therapist_verification SET verification_status = 'Approved', admin_id = ?, reviewed_at = NOW() WHERE therapist_id = ?");
            $stmt->execute([$admin_id, $therapist_id]);

            $pdo->commit();
            logAudit('Approve Therapist', "Approved therapist ID: $therapist_id");
            echo json_encode(['success' => true, 'message' => 'Therapist approved successfully.']);
            break;

        case 'reject_therapist':
            if (!hasPermission('therapist')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $therapist_id = $_POST['therapist_id'] ?? '';
            $comments = $_POST['comments'] ?? '';
            if (empty($therapist_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing therapist ID.']);
                exit();
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE therapist SET verified = 0 WHERE therapist_id = ?");
            $stmt->execute([$therapist_id]);

            $stmt = $pdo->prepare("UPDATE therapist_verification SET verification_status = 'Rejected', admin_comments = ?, admin_id = ?, reviewed_at = NOW() WHERE therapist_id = ?");
            $stmt->execute([$comments, $admin_id, $therapist_id]);

            $pdo->commit();
            logAudit('Reject Therapist', "Rejected therapist ID: $therapist_id. Reason: $comments");
            echo json_encode(['success' => true, 'message' => 'Therapist application rejected.']);
            break;

        case 'approve_volunteer':
            if (!hasPermission('therapist')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Therapist Manager / Super Admin required.']);
                exit();
            }
            $volunteer_id = $_POST['volunteer_id'] ?? '';
            if (empty($volunteer_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing volunteer ID.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE volunteer SET verification_status = 'Approved' WHERE volunteer_id = ?");
            $stmt->execute([$volunteer_id]);

            logAudit('Approve Volunteer', "Approved volunteer ID: $volunteer_id");
            echo json_encode(['success' => true, 'message' => 'Volunteer approved successfully.']);
            break;

        case 'reject_volunteer':
            if (!hasPermission('therapist')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Therapist Manager / Super Admin required.']);
                exit();
            }
            $volunteer_id = $_POST['volunteer_id'] ?? '';
            if (empty($volunteer_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing volunteer ID.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE volunteer SET verification_status = 'Rejected' WHERE volunteer_id = ?");
            $stmt->execute([$volunteer_id]);

            logAudit('Reject Volunteer', "Rejected volunteer ID: $volunteer_id");
            echo json_encode(['success' => true, 'message' => 'Volunteer application rejected.']);
            break;

        case 'edit_therapist_profile':
            if (!hasPermission('therapist')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $therapist_id = $_POST['therapist_id'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $specialties = trim($_POST['specialties'] ?? '');
            $hourly_rate = (float)($_POST['hourly_rate'] ?? 0);
            $years_experience = (int)($_POST['years_experience'] ?? 0);

            if (empty($therapist_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing therapist ID.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE therapist SET first_name = ?, last_name = ?, specialties = ?, hourly_rate = ?, years_experience = ? WHERE therapist_id = ?");
            $stmt->execute([$first_name, $last_name, $specialties, $hourly_rate, $years_experience, $therapist_id]);

            logAudit('Edit Therapist Profile', "Edited therapist ID: $therapist_id");
            echo json_encode(['success' => true, 'message' => 'Therapist profile updated.']);
            break;

        // --- 3. SESSION ACTIONS (Super Admin / Therapist Manager / Support Staff) ---
        case 'cancel_session':
            if (!hasPermission('session')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $session_id = $_POST['session_id'] ?? '';
            $reason = $_POST['reason'] ?? 'Cancelled by Admin';
            if (empty($session_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing session ID.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE private_sessions SET status = 'cancelled', cancel_reason = ? WHERE private_session_id = ?");
            $stmt->execute([$reason, $session_id]);

            logAudit('Cancel Session', "Cancelled session ID: $session_id. Reason: $reason");
            echo json_encode(['success' => true, 'message' => 'Session cancelled successfully.']);
            break;

        case 'reschedule_session':
            if (!hasPermission('session')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $session_id = $_POST['session_id'] ?? '';
            $new_date = $_POST['new_date'] ?? '';
            if (empty($session_id) || empty($new_date)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE private_sessions SET session_date = ?, reschedule_requested = 0 WHERE private_session_id = ?");
            $stmt->execute([$new_date, $session_id]);

            logAudit('Reschedule Session', "Rescheduled session ID: $session_id to $new_date");
            echo json_encode(['success' => true, 'message' => 'Session rescheduled successfully.']);
            break;

        case 'reassign_therapist':
            if (!hasPermission('session')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $session_id = $_POST['session_id'] ?? '';
            $therapist_id = $_POST['therapist_id'] ?? '';
            if (empty($session_id) || empty($therapist_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE private_sessions SET therapist_id = ? WHERE private_session_id = ?");
            $stmt->execute([$therapist_id, $session_id]);

            logAudit('Reassign Therapist', "Reassigned session ID $session_id to therapist ID $therapist_id");
            echo json_encode(['success' => true, 'message' => 'Therapist reassigned successfully.']);
            break;

        case 'create_group_session':
            if (!hasPermission('session') && $admin_role !== 'Content Manager') {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $topic = trim($_POST['topic'] ?? '');
            $room_name = trim($_POST['room_name'] ?? '');
            $session_date = $_POST['session_date'] ?? '';
            $max_participants = (int)($_POST['max_participants'] ?? 15);
            $duration_minutes = (int)($_POST['duration_minutes'] ?? 60);
            $therapist_id = $_POST['therapist_id'] ?? null;

            if (empty($topic) || empty($session_date)) {
                echo json_encode(['success' => false, 'message' => 'Topic and date are required.']);
                exit();
            }

            $gs_id = 'gs_' . uniqid();
            $stmt = $pdo->prepare("INSERT INTO group_sessions (group_session_id, room_name, topic, session_date, max_participants, duration_minutes, therapist_id, status, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, NOW())");
            $stmt->execute([$gs_id, $room_name ?: null, $topic, $session_date, $max_participants, $duration_minutes, $therapist_id ?: null, $admin_id]);

            logAudit('Create Group Session', "Created group session ID: $gs_id, Topic: $topic");
            echo json_encode(['success' => true, 'message' => 'Group session created.']);
            break;

        case 'edit_group_session':
            if (!hasPermission('session') && $admin_role !== 'Content Manager') {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $gs_id = $_POST['group_session_id'] ?? '';
            $topic = trim($_POST['topic'] ?? '');
            $room_name = trim($_POST['room_name'] ?? '');
            $session_date = $_POST['session_date'] ?? '';
            $max_participants = (int)($_POST['max_participants'] ?? 15);
            $therapist_id = $_POST['therapist_id'] ?? null;

            if (empty($gs_id) || empty($topic) || empty($session_date)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE group_sessions SET topic = ?, room_name = ?, session_date = ?, max_participants = ?, therapist_id = ? WHERE group_session_id = ?");
            $stmt->execute([$topic, $room_name ?: null, $session_date, $max_participants, $therapist_id ?: null, $gs_id]);

            logAudit('Edit Group Session', "Updated group session ID: $gs_id");
            echo json_encode(['success' => true, 'message' => 'Group session updated successfully.']);
            break;

        case 'cancel_group_session':
            if (!hasPermission('session') && $admin_role !== 'Content Manager') {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $gs_id = $_POST['group_session_id'] ?? '';
            if (empty($gs_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing group session ID.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE group_sessions SET status = 'cancelled' WHERE group_session_id = ?");
            $stmt->execute([$gs_id]);

            logAudit('Cancel Group Session', "Cancelled group session ID: $gs_id");
            echo json_encode(['success' => true, 'message' => 'Group session cancelled.']);
            break;

        case 'remove_group_participant':
            if (!hasPermission('session')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $gs_id = $_POST['group_session_id'] ?? '';
            $user_id = $_POST['user_id'] ?? '';
            if (empty($gs_id) || empty($user_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM group_session_participants WHERE group_session_id = ? AND user_id = ?");
            $stmt->execute([$gs_id, $user_id]);

            logAudit('Remove Group Participant', "Removed user ID $user_id from group session ID $gs_id");
            echo json_encode(['success' => true, 'message' => 'Participant removed.']);
            break;

        case 'close_group_session':
            if (!hasPermission('session') && $admin_role !== 'Content Manager') {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $gs_id = $_POST['group_session_id'] ?? '';
            if (empty($gs_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing group session ID.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE group_sessions SET status = 'closed' WHERE group_session_id = ?");
            $stmt->execute([$gs_id]);

            logAudit('Close Group Session', "Closed group session ID: $gs_id");
            echo json_encode(['success' => true, 'message' => 'Group session closed.']);
            break;

        // --- 4. REPORT MODERATION ACTIONS (Super Admin / Support Staff) ---
        case 'update_report_status':
            if (!hasPermission('report')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $report_id = $_POST['report_id'] ?? '';
            $status = $_POST['status'] ?? 'Pending';
            $action_taken = $_POST['action_taken'] ?? '';
            $reported_user_id = $_POST['reported_user_id'] ?? '';
            $user_action = $_POST['user_action'] ?? 'none';

            if (empty($report_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing report ID.']);
                exit();
            }

            if ($user_action === 'ignore') {
                $status = 'Dismissed';
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE report SET status = ?, action_taken = ?, admin_id = ?, reviewed_date = NOW() WHERE report_id = ?");
            $stmt->execute([$status, $action_taken ?: null, $admin_id, $report_id]);

            if (!empty($reported_user_id) && ($user_action === 'suspend' || $user_action === 'ban')) {
                $new_user_status = ($user_action === 'suspend') ? 'Suspended' : 'Deleted';
                $stmt_usr = $pdo->prepare("UPDATE user SET status = ? WHERE user_id = ?");
                $stmt_usr->execute([$new_user_status, $reported_user_id]);
                logAudit('Moderate User Account', "Report ID $report_id resolution: Set user ID $reported_user_id status to $new_user_status");
            }

            $pdo->commit();

            logAudit('Update Report Status', "Updated report ID: $report_id to status: $status (User Action: $user_action)");
            echo json_encode(['success' => true, 'message' => 'Report updated successfully.']);
            break;

        // --- 5. CHALLENGE ACTIONS (Super Admin / Content Manager) ---
        case 'create_challenge':
            if (!hasPermission('challenge')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $difficulty = $_POST['difficulty'] ?? 'Easy';
            $points = (int)($_POST['points'] ?? 10);

            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required.']);
                exit();
            }

            $ch_id = 'ch_' . uniqid();
            $stmt = $pdo->prepare("INSERT INTO daily_challenges (challenge_id, title, description, difficulty, points, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$ch_id, $title, $description ?: null, $difficulty, $points]);

            logAudit('Create Challenge', "Created challenge ID: $ch_id, Title: $title");
            echo json_encode(['success' => true, 'message' => 'Challenge created successfully.']);
            break;

        case 'edit_challenge':
            if (!hasPermission('challenge')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $ch_id = $_POST['challenge_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $difficulty = $_POST['difficulty'] ?? 'Easy';
            $points = (int)($_POST['points'] ?? 10);

            if (empty($ch_id) || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE daily_challenges SET title = ?, description = ?, difficulty = ?, points = ? WHERE challenge_id = ?");
            $stmt->execute([$title, $description ?: null, $difficulty, $points, $ch_id]);

            logAudit('Edit Challenge', "Updated challenge ID: $ch_id");
            echo json_encode(['success' => true, 'message' => 'Challenge updated successfully.']);
            break;

        case 'delete_challenge':
            if (!hasPermission('challenge')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $ch_id = $_POST['challenge_id'] ?? '';
            if (empty($ch_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing challenge ID.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM daily_challenges WHERE challenge_id = ?");
            $stmt->execute([$ch_id]);

            logAudit('Delete Challenge', "Deleted challenge ID: $ch_id");
            echo json_encode(['success' => true, 'message' => 'Challenge deleted.']);
            break;

        // --- 6. Q&A ACTIONS (Super Admin / Content Manager) ---
        case 'moderate_qa':
            if (!hasPermission('qa')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $post_id = $_POST['post_id'] ?? '';
            $sub_action = $_POST['sub_action'] ?? ''; // 'hide', 'show', 'delete'

            if (empty($post_id) || empty($sub_action)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            if ($sub_action === 'hide') {
                $stmt = $pdo->prepare("UPDATE community_qna SET is_public = 0 WHERE post_id = ?");
                $stmt->execute([$post_id]);
                logAudit('Moderate Q&A (Hide)', "Hid Q&A item: $post_id");
            } elseif ($sub_action === 'show') {
                $stmt = $pdo->prepare("UPDATE community_qna SET is_public = 1 WHERE post_id = ?");
                $stmt->execute([$post_id]);
                logAudit('Moderate Q&A (Show)', "Showed Q&A item: $post_id");
            } elseif ($sub_action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM community_qna WHERE post_id = ?");
                $stmt->execute([$post_id]);
                logAudit('Moderate Q&A (Delete)', "Deleted Q&A item: $post_id");
            }

            echo json_encode(['success' => true, 'message' => 'Q&A item moderated.']);
            break;

        // --- 8. RESOURCE ACTIONS (Super Admin / Content Manager) ---
        case 'add_resource':
            if (!hasPermission('resource')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden: Content Manager or Super Admin role required.']);
                exit();
            }
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['type'] ?? 'Article';
            $category = $_POST['category'] ?? 'Mental Health';
            $content = trim($_POST['description'] ?? '');
            $url = trim($_POST['url'] ?? '');

            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Resource title is required.']);
                exit();
            }

            $res_id = 'RES_' . uniqid();
            // Default image based on type/category
            $image_url = 'assets/images/default_resource.jpg';
            if ($type === 'Video') $image_url = 'assets/images/resource_listening.jpg';
            elseif ($category === 'Mindfulness') $image_url = 'assets/images/resource_meditation.jpg';
            elseif ($category === 'Anxiety') $image_url = 'assets/images/resource_anxiety.jpg';

            $stmt = $pdo->prepare("INSERT INTO resource (resource_id, title, type, category, content, url, image_url, added_by_admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$res_id, $title, $type, $category, $content ?: null, $url ?: null, $image_url, $admin_id]);

            logAudit('Add Resource', "Created resource ID: $res_id, Title: $title");
            echo json_encode(['success' => true, 'message' => 'Resource added successfully.']);
            break;

        case 'edit_resource':
            if (!hasPermission('resource')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $res_id = $_POST['resource_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['type'] ?? 'Article';
            $category = $_POST['category'] ?? 'Mental Health';
            $content = trim($_POST['description'] ?? '');
            $url = trim($_POST['url'] ?? '');

            if (empty($res_id) || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Missing fields.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE resource SET title = ?, type = ?, category = ?, content = ?, url = ?, last_updated = NOW() WHERE resource_id = ?");
            $stmt->execute([$title, $type, $category, $content ?: null, $url ?: null, $res_id]);

            logAudit('Edit Resource', "Updated resource ID: $res_id, Title: $title");
            echo json_encode(['success' => true, 'message' => 'Resource updated successfully.']);
            break;

        case 'delete_resource':
            if (!hasPermission('resource')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $res_id = $_POST['resource_id'] ?? '';
            if (empty($res_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing resource ID.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM resource WHERE resource_id = ?");
            $stmt->execute([$res_id]);

            logAudit('Delete Resource', "Deleted resource ID: $res_id");
            echo json_encode(['success' => true, 'message' => 'Resource deleted.']);
            break;

        // --- 7. NOTIFICATION ACTIONS (Super Admin / Support Staff) ---
        case 'send_notification':
            if (!hasPermission('notification')) {
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit();
            }
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $target_role = $_POST['target_role'] ?? 'all'; // 'all', 'client', 'therapist'
            $type = $_POST['type'] ?? 'system';
            $scheduled_at = $_POST['scheduled_at'] ?? '';

            if (empty($title) || empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Title and message are required.']);
                exit();
            }

            if (empty($scheduled_at)) {
                $scheduled_at = date('Y-m-d H:i:s');
            }

            scheduleAdminNotification($pdo, $admin_id, $target_role, $title, $message, $type, $scheduled_at);

            logAudit('Send Notification', "Dispatched notification, target: $target_role, type: $type");
            echo json_encode(['success' => true, 'message' => 'Notification dispatched/scheduled successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown admin action: ' . $action]);
            break;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[ADMIN API ERROR]: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
