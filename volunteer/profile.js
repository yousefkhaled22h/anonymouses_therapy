document.addEventListener('DOMContentLoaded', function() {
    /* === Time Slot Validation Logic === */
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const addAvailabilityBtn = document.getElementById('add_availability_btn');
    const timeErrorMsg = document.getElementById('time_error_msg');

    function validateTimeSlot() {
        if (startTimeInput && endTimeInput && startTimeInput.value && endTimeInput.value) {
            // Compare the HTML5 time strings
            if (startTimeInput.value >= endTimeInput.value) {
                timeErrorMsg.style.display = 'block';
                timeErrorMsg.innerText = "End Time must be after Start Time.";
                addAvailabilityBtn.disabled = true;
                addAvailabilityBtn.style.opacity = '0.5';
                addAvailabilityBtn.style.cursor = 'not-allowed';
            } else {
                timeErrorMsg.style.display = 'none';
                addAvailabilityBtn.disabled = false;
                addAvailabilityBtn.style.opacity = '1';
                addAvailabilityBtn.style.cursor = 'pointer';
            }
        }
        
        // Dynamically update the minimum allowed End Time based on Start Time
        if (startTimeInput && endTimeInput && startTimeInput.value) {
            endTimeInput.min = startTimeInput.value;
        }
    }

    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('input', validateTimeSlot);
        endTimeInput.addEventListener('input', validateTimeSlot);
        
        // Trigger once on load in case fields are pre-filled
        validateTimeSlot();
    }

    /* === Profile Edit Mode Logic === */
    const profileForm = document.getElementById('profile-form');
    const editBtn = document.getElementById('edit-profile-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    
    if (editBtn && cancelBtn && profileForm) {
        editBtn.addEventListener('click', function() {
            profileForm.classList.add('is-editing');
            editBtn.style.display = 'none';
        });
        
        cancelBtn.addEventListener('click', function() {
            profileForm.reset(); // Restores original untouched info
            profileForm.classList.remove('is-editing');
            editBtn.style.display = 'inline-flex';
        });
    }
});
