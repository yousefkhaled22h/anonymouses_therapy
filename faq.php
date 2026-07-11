<?php
// faq.php
session_start();
$user_role = $_SESSION['role'] ?? 'Guest';

if (strtolower($user_role) === 'therapist') {
    $body_class = 'role-therapist';
} elseif (strtolower($user_role) === 'volunteer') {
    $body_class = 'role-volunteer';
} else {
    $body_class = 'role-client';
}

require_once 'includes/header.php';
?>

<style>
    .faq-wrapper {
        background-color: #F4F1EA;
        padding: 80px 20px;
        min-height: calc(100vh - 80px);
        font-family: var(--font-body);
    }
    
    .faq-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    /* Fix Bootstrap conflict with custom navbar dropdown */
    .nav-dropdown .dropdown-menu {
        display: block !important;
    }
    
    .nav-links a {
        text-decoration: none !important;
    }
    
    /* Fix Bootstrap breaking the navbar hover underline pseudo-element */
    .nav-links a::after {
        border: none !important;
        margin: 0 !important;
    }
    
    /* Therapist Role Styling (Blue/Baby Blue) */
    body.role-therapist .faq-wrapper {
        background-color: #F4F9FD;
    }
    body.role-therapist .faq-header h1 {
        color: #1A4D80;
    }
    body.role-therapist .accordion-item {
        border-color: #D1E5F7;
    }
    body.role-therapist .accordion-item:hover {
        border-color: #B8D4EF;
        box-shadow: 0 10px 20px rgba(51, 122, 183, 0.08);
    }
    body.role-therapist .accordion-button {
        color: #1A4D80 !important;
    }
    body.role-therapist .accordion-button:not(.collapsed) {
        color: #337AB7 !important;
        background-color: #EBF4FB !important;
    }
    body.role-therapist .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23337AB7'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e") !important;
    }
    body.role-therapist .accordion-body {
        background-color: #EBF4FB;
    }
    body.role-therapist .faq-contact-card {
        background: linear-gradient(135deg, #286090, #337AB7);
        box-shadow: 0 15px 30px rgba(51, 122, 183, 0.2);
    }
    body.role-therapist .btn-contact {
        color: #337AB7;
    }
    body.role-therapist .btn-contact:hover {
        color: #286090;
    }
    
    /* Volunteer Role Styling (Supportive Green) */
    body.role-volunteer .faq-wrapper {
        background-color: #F0F7F4;
    }
    body.role-volunteer .faq-header h1 {
        color: #1B4D3E;
    }
    body.role-volunteer .accordion-item {
        border-color: #D8F3DC;
    }
    body.role-volunteer .accordion-item:hover {
        border-color: #95D5B2;
        box-shadow: 0 10px 20px rgba(45, 106, 79, 0.08);
    }
    body.role-volunteer .accordion-button {
        color: #1B4D3E !important;
    }
    body.role-volunteer .accordion-button:not(.collapsed) {
        color: #2D6A4F !important;
        background-color: #D8F3DC !important;
    }
    body.role-volunteer .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232D6A4F'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e") !important;
    }
    body.role-volunteer .accordion-body {
        background-color: #D8F3DC;
    }
    body.role-volunteer .faq-contact-card {
        background: linear-gradient(135deg, #1B4332, #2D6A4F);
        box-shadow: 0 15px 30px rgba(45, 106, 79, 0.2);
    }
    body.role-volunteer .btn-contact {
        color: #2D6A4F;
    }
    body.role-volunteer .btn-contact:hover {
        color: #1B4332;
    }
    
    .faq-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .faq-header h1 {
        font-family: var(--font-heading);
        font-size: 2.5rem;
        color: #3D3530;
        margin-bottom: 15px;
        font-weight: 800;
    }
    
    .faq-header p {
        font-size: 1.1rem;
        color: #666;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .accordion-item {
        background-color: #FFFFFF;
        border: 1px solid #E0D8CC;
        border-radius: 12px !important;
        margin-bottom: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    
    .accordion-item:hover {
        box-shadow: 0 10px 20px rgba(141, 110, 99, 0.08);
        border-color: #D4C5B0;
    }
    
    .accordion-button {
        background-color: #FFFFFF !important;
        color: #3D3530 !important;
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 1.15rem;
        padding: 20px 25px;
        box-shadow: none !important;
        transition: all 0.3s ease;
    }
    
    .accordion-button:not(.collapsed) {
        color: #8D6E63 !important;
        background-color: #FAF9F6 !important;
    }
    
    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%238D6E63'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e") !important;
    }
    
    .accordion-body {
        padding: 0 25px 25px 25px;
        color: #555;
        font-size: 1.05rem;
        line-height: 1.7;
        background-color: #FAF9F6;
    }
    
    .faq-contact-card {
        margin-top: 50px;
        background: linear-gradient(135deg, #8D6E63, #A68A6C);
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        color: white;
        box-shadow: 0 15px 30px rgba(141, 110, 99, 0.2);
    }
    
    .faq-contact-card h3 {
        font-family: var(--font-heading);
        font-size: 1.8rem;
        margin-bottom: 15px;
        color: white;
    }
    
    .faq-contact-card p {
        font-size: 1.05rem;
        opacity: 0.9;
        margin-bottom: 25px;
    }
    
    .btn-contact {
        background: white;
        color: #8D6E63;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-contact:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        color: #6F5A44;
    }
</style>

<!-- Require Bootstrap CSS & JS for Accordions -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="faq-wrapper">
    <div class="faq-container">
        <div class="faq-header">
            <img src="assets/images/faq_header.png" alt="Mental Health Support FAQ" style="width: 100%; height: 300px; object-fit: cover; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(141, 110, 99, 0.1);">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about our therapy platform, privacy policies, and how to get started on your mental health journey.</p>
        </div>
        
        <div class="accordion" id="faqAccordion">
            <!-- FAQ 1 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        How does anonymous therapy work?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Your privacy is our absolute priority. When you sign up, you only need to provide an email (which can be a dedicated anonymous address) and create a password. We do not require your real name, location, or any identifying documents. During sessions, you can choose text-only chat, audio-only calls, or video calls with the camera turned off. All your data and session logs are strictly confidential and encrypted.
                    </div>
                </div>
            </div>
            
            <!-- FAQ 2 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        Are the therapists certified professionals?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes. Every therapist on the Safe Haven platform undergoes a rigorous vetting process. We verify their credentials, licenses, and clinical experience before they are allowed to host sessions. Our team consists of licensed psychologists, clinical social workers, and certified counselors with diverse specialties ranging from anxiety and depression to trauma and relationship counseling.
                    </div>
                </div>
            </div>
            
            <!-- FAQ 3 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        What is the cancellation and rescheduling policy?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        You can cancel or reschedule a session directly from your dashboard. If a therapist needs to cancel a session, you will receive a formal notification explaining the reason, and your account will be immediately refunded. For rescheduling, we offer a flexible system that allows both clients and therapists to propose alternative times without losing the reserved slot until both parties agree.
                    </div>
                </div>
            </div>
            
            <!-- FAQ 4 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        Is there financial support available?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        We believe mental healthcare should be accessible to everyone. Safe Haven offers a free Group Therapy Hub and Community Q&A section led by volunteers and professionals. For one-on-one sessions, we occasionally provide subsidized rates based on financial need. Please contact our support team through the Contact Hub if you require financial assistance options.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="faq-contact-card">
            <h3>Still have questions?</h3>
            <p>We're here to help. Reach out to our support team and we'll get back to you as soon as possible.</p>
            <a href="contact.php" class="btn-contact">Contact Support</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once 'includes/footer.php'; ?>
