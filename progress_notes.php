<?php
// progress_notes.php
$body_class = 'role-therapist';
require_once 'includes/header.php';

// Ensure user is logged in (likely a therapist or admin, but for now allowing logged in users to see it or specific role)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="booking-containerClass"
    style="max-width: 900px; margin: 60px auto; padding: 0 20px; font-family: var(--font-body);">

    <!-- Header Section with Image Background aesthetic -->
    <div
        style="display: flex; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 40px;">
        <div
            style="flex: 1; min-height: 300px; background: url('assets/images/therapist_notes.jpg') center/cover no-repeat; background-color: #f0f0f0;">
            <!-- Placeholder for image if we had one, or a nice gradient -->
            <div
                style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(0,0,0,0.05));">
                <!-- If you have the specific image, we'd use it here. For now, a placeholder style. -->
            </div>
        </div>
        <div style="flex: 1; padding: 60px; display: flex; align-items: center;">
            <div>
                <h1
                    style="font-family: var(--font-heading); font-size: 2.5rem; color: #5A4A3A; line-height: 1.2; margin-bottom: 0;">
                    MENTAL HEALTH<br>PROGRESS NOTES<br>FORM
                </h1>
            </div>
        </div>
    </div>

    <form action="api/submit_notes.php" method="POST"
        style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">

        <!-- Patient Name -->
        <div style="margin-bottom: 30px;">
            <label style="display: block; font-size: 1.1rem; color: #2C3E50; margin-bottom: 15px;">Patient Name</label>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <input type="text" name="patient_first_name" class="form-control"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;" placeholder="">
                    <small style="color: #666; margin-top: 5px; display: block;">First Name</small>
                </div>
                <div style="flex: 1;">
                    <input type="text" name="patient_last_name" class="form-control"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;" placeholder="">
                    <small style="color: #666; margin-top: 5px; display: block;">Last Name</small>
                </div>
            </div>
        </div>

        <!-- Therapist Name -->
        <div style="margin-bottom: 30px;">
            <label style="display: block; font-size: 1.1rem; color: #2C3E50; margin-bottom: 15px;">Therapist
                Name</label>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <input type="text" name="therapist_first_name" class="form-control"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;" placeholder="">
                    <small style="color: #666; margin-top: 5px; display: block;">First Name</small>
                </div>
                <div style="flex: 1;">
                    <input type="text" name="therapist_last_name" class="form-control"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;" placeholder="">
                    <small style="color: #666; margin-top: 5px; display: block;">Last Name</small>
                </div>
            </div>
        </div>

        <!-- Session Date & Time -->
        <div style="margin-bottom: 30px; display: flex; gap: 40px;">
            <div style="flex: 1;">
                <label style="display: block; font-size: 1.1rem; color: #2C3E50; margin-bottom: 15px;">Session
                    Date</label>
                <div style="position: relative;">
                    <input type="text" name="session_date" placeholder="MM-DD-YYYY" class="form-control"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;">
                    <span style="position: absolute; right: 10px; top: 12px; color: #999;">📅</span>
                </div>
                <small style="color: #666; margin-top: 5px; display: block;">Date</small>
            </div>
            <div style="flex: 1;">
                <label style="display: block; font-size: 1.1rem; color: #2C3E50; margin-bottom: 15px;">Session
                    Time</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="session_time" placeholder="HH : MM" class="form-control"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;">
                    <select name="session_ampm" class="form-control"
                        style="padding: 12px; border: 1px solid #ddd; border-radius: 6px; background: white;">
                        <option>PM</option>
                        <option>AM</option>
                    </select>
                </div>
                <small style="color: #666; margin-top: 5px; display: block;">Hour Minutes</small>
            </div>
        </div>

        <!-- Reason for Treatment -->
        <div style="margin-bottom: 30px;">
            <label style="display: block; font-size: 1.1rem; color: #2C3E50; margin-bottom: 15px;">Reason for
                Treatment</label>
            <textarea class="form-control"  name="treatment_reason" rows="4"
                style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; resize: vertical;"></textarea>
        </div>

        <button type="submit" class="btn btn-primary"
            style="background-color: #8A7055; border: none; padding: 15px 40px; font-weight: bold; border-radius: 30px;">SUBMIT
            NOTE</button>

    </form>
</div>

<?php
require_once 'includes/footer.php';
?>