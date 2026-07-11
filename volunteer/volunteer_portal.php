<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/i18n.php';
ob_start('translate_html_buffer');

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. Session Security: Ensure volunteer is authenticated
if (!isset($_SESSION['user_id'])) {
    // Redirect to signin page if not logged in
    header("Location: signin.php");
    exit();
}

// Initialize variables for feedback messages and state
$message = '';
$isSuccess = false;
$userId = $_SESSION['user_id'];
$activeTab = 'dashboard'; // Default tab to display

// Initialize data containers
$user = [];
$upcomingSessions = [];
$availabilities = [];

try {
    require_once __DIR__ . '/../includes/db_connect.php';
    
    // 3. Unified Form Handling

    // Update Profile Information
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
        $bio = htmlspecialchars(trim($_POST['bio']));
        $specialties = htmlspecialchars(trim($_POST['specialties']));
        $nickname = htmlspecialchars(trim($_POST['nickname']));
        
        // Update basic volunteer fields
        $stmt = $pdo->prepare("UPDATE volunteers SET bio = ?, specialties = ?, nickname = ? WHERE id = ?");
        $stmt->execute([$bio, $specialties, $nickname, $userId]);

        // Process profile-specific features (e.g., language skills)
        $langSkills = htmlspecialchars(trim($_POST['language_skills']));
        $stmtCheck = $pdo->prepare("SELECT id FROM volunteer_profiles WHERE volunteer_id = ?");
        $stmtCheck->execute([$userId]);
        $hasProfile = $stmtCheck->fetch();

        if ($hasProfile) {
            // Update existing profile
            $stmtProf = $pdo->prepare("UPDATE volunteer_profiles SET language_skills = ? WHERE volunteer_id = ?");
            $stmtProf->execute([$langSkills, $userId]);
        } else {
            // Insert new profile record if none exists
            $stmtProf = $pdo->prepare("INSERT INTO volunteer_profiles (volunteer_id, language_skills) VALUES (?, ?)");
            $stmtProf->execute([$userId, $langSkills]);
        }

        $message = "Profile updated successfully!";
        $isSuccess = true;
        // Keep the user on the Profile tab after submission
        $activeTab = 'profile'; 
    }

    // Add Availability Slot
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_availability'])) {
        $day = htmlspecialchars(trim($_POST['day_of_week']));
        $time_slot = htmlspecialchars(trim($_POST['time_slot']));
        
        $stmt = $pdo->prepare("INSERT INTO availabilities (volunteer_id, day_of_week, time_slot, status) VALUES (?, ?, ?, 'Available')");
        $stmt->execute([$userId, $day, $time_slot]);
        
        $message = "Availability slot added successfully!";
        $isSuccess = true;
        $activeTab = 'profile'; 
    }

    // Remove Availability Slot
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_availability'])) {
        $availId = (int) $_POST['avail_id'];
        
        $stmt = $pdo->prepare("DELETE FROM availabilities WHERE id = ? AND volunteer_id = ?");
        $stmt->execute([$availId, $userId]);
        
        $message = "Availability slot removed successfully!";
        $isSuccess = true;
        $activeTab = 'profile'; 
    }

    // 4. Fetch Required Data for the View

    // Fetch User Info for both Dashboard and Profile needs
    $stmt = $pdo->prepare("
        SELECT v.first_name, v.last_name, v.email, v.bio, v.specialties, v.nickname, v.verification_status, vp.language_skills 
        FROM volunteers v
        LEFT JOIN volunteer_profiles vp ON v.id = vp.volunteer_id
        WHERE v.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no user found, the session might be invalid
    if (!$user) {
        session_destroy();
        header("Location: signin.php");
        exit();
    }
    
    // Fetch Upcoming Group Sessions for Dashboard Tab
    $stmtSessions = $pdo->prepare("
        SELECT gs.topic, gs.scheduled_at, sv.role 
        FROM group_sessions gs
        JOIN session_volunteers sv ON gs.id = sv.session_id
        WHERE sv.volunteer_id = ? AND gs.scheduled_at > NOW()
        ORDER BY gs.scheduled_at ASC
    ");
    $stmtSessions->execute([$userId]);
    $upcomingSessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Availability for Profile Tab
    $stmtAvail = $pdo->prepare("
        SELECT id, day_of_week, time_slot, status 
        FROM availabilities 
        WHERE volunteer_id = ? 
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), time_slot ASC
    ");
    $stmtAvail->execute([$userId]);
    $availabilities = $stmtAvail->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($e->getCode() == '42S02' || $e->getCode() == '42S22') {
        $message = "Database Error: Please ensure you have run the updated database schema.";
    } else {
        $message = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Portal - Safe Haven</title>
    <!-- Bootstrap 5 CSS for clean UI styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .portal-header {
            background-color: #1b4332;
            color: #fff;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .portal-header h1 {
            margin: 0;
            font-weight: 600;
        }
        .portal-header a {
            color: #bbf7d0;
            text-decoration: none;
            font-weight: 500;
        }
        .portal-header a:hover {
            color: #fff;
            text-decoration: underline;
        }
        .nav-tabs .nav-link {
            color: #2d6a4f;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #1b4332;
            font-weight: 700;
            border-bottom: 3px solid #1b4332;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 1.25rem;
            color: #2d6a4f;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 20px;
        }
        .btn-custom {
            background-color: #40916c;
            color: white;
            font-weight: 500;
        }
        .btn-custom:hover {
            background-color: #2d6a4f;
            color: white;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header class="portal-header box-shadow">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="../assets/images/logo.png" alt="Safe Haven Logo" style="height: 45px; width: auto; filter: brightness(0) invert(1);">
                <h1 class="mb-0">Volunteer Portal</h1>
            </div>
            <div>
                <span class="me-3">Welcome, <?php echo htmlspecialchars($user['nickname'] ?? $user['first_name'] ?? 'Volunteer'); ?>!</span>
                <a href="home.php" class="me-3">Home</a>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </header>

    <div class="container mb-5">
        
        <!-- Feedback Messages -->
        <?php if ($message): ?>
            <div class="alert <?php echo $isSuccess ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="volunteerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="<?php echo $activeTab === 'dashboard' ? 'true' : 'false'; ?>">Dashboard</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="<?php echo $activeTab === 'profile' ? 'true' : 'false'; ?>">Profile & Availability</button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="volunteerTabsContent">
            
            <!-- TAB 1: Dashboard -->
            <div class="tab-pane fade <?php echo $activeTab === 'dashboard' ? 'show active' : ''; ?>" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <div class="row">
                    <!-- Verification Status Card -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">Verification Status</div>
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <?php 
                                    $vStatus = $user['verification_status'] ?? 'Pending';
                                    $badgeClass = '';
                                    if ($vStatus === 'Approved') $badgeClass = 'bg-success';
                                    elseif ($vStatus === 'Rejected') $badgeClass = 'bg-danger';
                                    else $badgeClass = 'bg-warning text-dark';
                                ?>
                                <h4 class="mb-3">Current Status</h4>
                                <div><span class="badge status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($vStatus); ?></span></div>
                                <?php if($vStatus === 'Pending'): ?>
                                    <p class="text-muted mt-3 small">Your application is currently under review by an administrator.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Community Q&A Quick Links -->
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header">Community Q&A</div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                <h5>Help Clients Anonymously</h5>
                                <p class="text-muted">Browse open questions from users seeking quick professional advice and share your insights.</p>
                                <div>
                                    <a href="javascript:void(0);" onclick="alert('Navigating to Q&A Forum...');" class="btn btn-custom">View Open Questions</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Group Sessions -->
                <div class="card mt-4">
                    <div class="card-header">Upcoming Group Sessions</div>
                    <div class="card-body p-0">
                        <?php if (!empty($upcomingSessions)): ?>
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Topic</th>
                                        <th>Date & Time</th>
                                        <th>Your Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingSessions as $session): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium"><?php echo htmlspecialchars($session['topic']); ?></td>
                                            <td><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($session['scheduled_at']))); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($session['role']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="p-4 text-muted">You have no upcoming group sessions scheduled.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Profile & Availability -->
            <div class="tab-pane fade <?php echo $activeTab === 'profile' ? 'show active' : ''; ?>" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                
                <div class="row">
                    <!-- Profile Update Form -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">Update Profile</div>
                            <div class="card-body">
                                <form action="volunteer_portal.php" method="POST">
                                    
                                    <div class="mb-3">
                                        <label for="nickname" class="form-label fw-bold">Nickname</label>
                                        <input type="text" class="form-control" id="nickname" name="nickname" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>" placeholder="Publicly visible name">
                                    </div>

                                    <div class="mb-3">
                                        <label for="language_skills" class="form-label fw-bold">Language Skills</label>
                                        <input type="text" class="form-control" id="language_skills" name="language_skills" value="<?php echo htmlspecialchars($user['language_skills'] ?? ''); ?>" placeholder="e.g., English, Spanish">
                                    </div>

                                    <div class="mb-3">
                                        <label for="specialties" class="form-label fw-bold">Specialties</label>
                                        <input type="text" class="form-control" id="specialties" name="specialties" value="<?php echo htmlspecialchars($user['specialties'] ?? ''); ?>" placeholder="Anxiety, Depression, etc.">
                                    </div>

                                    <div class="mb-4">
                                        <label for="bio" class="form-label fw-bold">Professional Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Tell clients about your approach..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>

                                    <button type="submit" name="update_profile" class="btn btn-custom w-100">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Availability Schedule -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">Availability Schedule</div>
                            <div class="card-body">
                                
                                <!-- Add New Availability Form -->
                                <form action="volunteer_portal.php" method="POST" class="mb-4 p-3 bg-light rounded border">
                                    <h6 class="fw-bold mb-3">Add New Slot</h6>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small">Day of Week</label>
                                            <select name="day_of_week" class="form-select" required>
                                                <option value="Monday">Monday</option>
                                                <option value="Tuesday">Tuesday</option>
                                                <option value="Wednesday">Wednesday</option>
                                                <option value="Thursday">Thursday</option>
                                                <option value="Friday">Friday</option>
                                                <option value="Saturday">Saturday</option>
                                                <option value="Sunday">Sunday</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small">Time Slot</label>
                                            <input type="text" name="time_slot" class="form-control" placeholder="e.g. 09:00 AM - 11:00 AM" required>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" name="add_availability" class="btn btn-custom w-100">+</button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Existing Availability List -->
                                <h6 class="fw-bold mb-3">Current Slots</h6>
                                <?php if (empty($availabilities)): ?>
                                    <p class="text-muted small">No availability set.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Day</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($availabilities as $avail): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($avail['day_of_week']); ?></td>
                                                        <td><?php echo htmlspecialchars($avail['time_slot']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $avail['status'] === 'Available' ? 'success' : 'secondary'; ?>">
                                                                <?php echo htmlspecialchars($avail['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <form action="volunteer_portal.php" method="POST" class="d-inline">
                                                                <input type="hidden" name="avail_id" value="<?php echo $avail['id']; ?>">
                                                                <button type="submit" name="delete_availability" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove">&times;</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Force reload if page is restored from Back-Forward Cache (bfcache)
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</body>
</html>
