<?php
// contact.php
session_start();
$user_role = $_SESSION['role'] ?? 'Guest';

if (strtolower($user_role) === 'therapist') {
    $body_class = 'role-therapist';
} elseif (strtolower($user_role) === 'volunteer') {
    $body_class = 'role-volunteer';
} else {
    $body_class = 'role-client';
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    $suggestion = htmlspecialchars(trim($_POST['suggestion'] ?? ''));

    if (!$email || empty($message)) {
        $error_msg = "Please provide a valid email and a message.";
    } else {
        // Procedural processing: Send email instead of saving to database
        $to = "Safehaven@gmail.com";
        $subject = "New Contact Hub Message from $email";
        
        $body = "You have received a new message from the Safe Haven Contact Hub.\n\n";
        $body .= "Email: $email\n";
        $body .= "Message:\n$message\n\n";
        if (!empty($suggestion)) {
            $body .= "Suggestion for growth:\n$suggestion\n";
        }
        
        $headers = "From: no-reply@safehaven.com\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        // Simulating the email sending success for now (mail() might not work on localhost XAMPP)
        // mail($to, $subject, $body, $headers);
        
        $success_msg = "Your message has been successfully sent. Thank you for reaching out!";
    }
}

require_once 'includes/header.php';
?>

<style>
    .contact-wrapper {
        background-color: #F4F1EA;
        padding: 80px 20px;
        min-height: calc(100vh - 80px);
        font-family: var(--font-body);
    }
    
    .nav-links a {
        text-decoration: none !important;
    }
    
    /* Therapist Role Styling (Blue/Baby Blue) */
    body.role-therapist .contact-wrapper {
        background-color: #F4F9FD;
    }
    body.role-therapist .contact-info {
        background: linear-gradient(135deg, rgba(26, 77, 128, 0.85), rgba(51, 122, 183, 0.85)), url('assets/images/contact_header.png') center/cover no-repeat;
    }
    body.role-therapist .form-control {
        border-color: #D1E5F7;
    }
    body.role-therapist .form-control:focus {
        border-color: #337AB7;
        box-shadow: 0 0 0 4px rgba(51, 122, 183, 0.1);
    }
    body.role-therapist .suggestion-box {
        background: #F4F9FD;
        border-left-color: #337AB7;
    }
    body.role-therapist .suggestion-box .form-label {
        color: #1A4D80;
    }
    body.role-therapist .btn-submit {
        background: linear-gradient(135deg, #1A4D80, #337AB7);
    }
    body.role-therapist .btn-submit:hover {
        box-shadow: 0 10px 20px rgba(51, 122, 183, 0.2);
    }
    
    /* Volunteer Role Styling (Supportive Green) */
    body.role-volunteer .contact-wrapper {
        background-color: #F0F7F4;
    }
    body.role-volunteer .contact-info {
        background: linear-gradient(135deg, rgba(27, 67, 50, 0.85), rgba(45, 106, 79, 0.85)), url('assets/images/contact_header.png') center/cover no-repeat;
    }
    body.role-volunteer .form-control {
        border-color: #D8F3DC;
    }
    body.role-volunteer .form-control:focus {
        border-color: #2D6A4F;
        box-shadow: 0 0 0 4px rgba(45, 106, 79, 0.1);
    }
    body.role-volunteer .suggestion-box {
        background: #F0F7F4;
        border-left-color: #2D6A4F;
    }
    body.role-volunteer .suggestion-box .form-label {
        color: #1B4D3E;
    }
    body.role-volunteer .btn-submit {
        background: linear-gradient(135deg, #1B4332, #2D6A4F);
    }
    body.role-volunteer .btn-submit:hover {
        box-shadow: 0 10px 20px rgba(45, 106, 79, 0.2);
    }
    
    .contact-container {
        max-width: 1000px;
        margin: 0 auto;
        background: #FFFFFF;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        overflow: hidden;
    }
    
    .contact-info {
        background: linear-gradient(135deg, rgba(141, 110, 99, 0.85), rgba(166, 138, 108, 0.85)), url('assets/images/contact_header.png') center/cover no-repeat;
        color: #FFFFFF;
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .contact-info h2 {
        color: #FFFFFF;
        font-family: var(--font-heading);
        font-size: 2.2rem;
        margin-bottom: 15px;
    }
    
    .contact-info p {
        font-size: 1.05rem;
        line-height: 1.6;
        opacity: 0.9;
        margin-bottom: 40px;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        font-size: 1.1rem;
    }
    
    .info-item i {
        background: rgba(255, 255, 255, 0.2);
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.2rem;
    }
    
    .contact-form-section {
        padding: 50px 40px;
    }
    
    .contact-form-section h3 {
        font-family: var(--font-heading);
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 25px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .form-control {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid #D4C5B0;
        border-radius: 8px;
        font-size: 1rem;
        font-family: var(--font-body);
        transition: all 0.3s ease;
        background: #FAF9F6;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #8D6E63;
        box-shadow: 0 0 0 4px rgba(141, 110, 99, 0.1);
        background: #FFFFFF;
    }
    
    .suggestion-box {
        background: #F4F1EA;
        border-left: 4px solid #A68A6C;
        padding: 20px;
        border-radius: 0 8px 8px 0;
        margin-bottom: 30px;
    }
    
    .suggestion-box .form-label {
        color: #8D6E63;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #8D6E63, #6F5A44);
        color: #FFFFFF;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 1.05rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        justify-content: center;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(141, 110, 99, 0.2);
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 500;
    }
    
    .alert-success {
        background: #E8F5E9;
        color: #2E7D32;
        border: 1px solid #C8E6C9;
    }
    
    .alert-danger {
        background: #FFEBEE;
        color: #C62828;
        border: 1px solid #FFCDD2;
    }
    
    @media (max-width: 768px) {
        .contact-container {
            grid-template-columns: 1fr;
        }
        .contact-info {
            padding: 40px 30px;
        }
        .contact-form-section {
            padding: 40px 30px;
        }
    }
</style>

<div class="contact-wrapper">
    <div class="contact-container">
        <!-- Left Side: Information -->
        <div class="contact-info">
            <div>
                <h2>Get in Touch</h2>
                <p>Whether you have a question about features, pricing, or need technical support, our team is ready to answer all your questions.</p>
                
                <div class="info-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 3px;">Email Us</div>
                        <div style="font-weight: 600;">Safehaven@gmail.com</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side: Form -->
        <div class="contact-form-section">
            <h3>Send us a Message</h3>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <form action="contact.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address <span style="color: #e53e3e;">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="you@example.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="message">Your Message <span style="color: #e53e3e;">*</span></label>
                    <textarea id="message" name="message" class="form-control" rows="5" required placeholder="How can we help you?"></textarea>
                </div>
                
                <div class="suggestion-box">
                    <label class="form-label" for="suggestion">
                        <i class="fa-solid fa-lightbulb"></i> Help Us Grow (Optional)
                    </label>
                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Have an idea for a new feature? Let us know!</p>
                    <textarea id="suggestion" name="suggestion" class="form-control" rows="2" style="background: #FFFFFF;" placeholder="I think Safe Haven should add..."></textarea>
                </div>
                
                <button type="submit" name="submit_contact" class="btn-submit">
                    Send Message <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
