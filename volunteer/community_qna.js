// Toggle 3-dot menus
function toggleMenu(menuId) {
    // Close other menus first
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== menuId) {
            menu.classList.remove('show');
        }
    });
    
    // Toggle requested menu
    const menu = document.getElementById(menuId);
    if (menu) {
        menu.classList.toggle('show');
    }
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.q-menu-container')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Modal Logic
function openAskModal() {
    document.getElementById('askModal').classList.remove('hidden');
}

function closeAskModal() {
    document.getElementById('askModal').classList.add('hidden');
    // Clear inputs
    document.getElementById('askTitle').value = '';
    document.getElementById('askContent').value = '';
    document.getElementById('askTags').value = '';
}

// Global API Fetch function
async function apiCall(action, data) {
    try {
        const response = await fetch(`community_qna.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if(!result.success) {
            alert("Error: " + (result.error || "Something went wrong"));
        }
        return result;
    } catch (e) {
        alert("Network error occurred.");
        return { success: false };
    }
}

// Create Question
async function submitQuestion() {
    const title = document.getElementById('askTitle').value.trim();
    const content = document.getElementById('askContent').value.trim();
    const tags = document.getElementById('askTags').value.trim();

    if (!title || !content) {
        alert('Please fill out the title and description.');
        return;
    }

    const res = await apiCall('ask', { title, content, tags });
    if (res.success) {
        closeAskModal();
        window.location.reload(); // Reload to show new post
    }
}

// Search
function handleSearch(query) {
    window.location.href = `community_qna.php?search=${encodeURIComponent(query)}`;
}

// Like System
async function toggleLike(postId, btnElement) {
    const res = await apiCall('like', { post_id: postId });
    if (res.success) {
        // Update UI Instantly
        const countSpan = btnElement.querySelector('.like-count');
        countSpan.textContent = res.count;
        
        if (res.action === 'liked') {
            btnElement.classList.add('liked');
        } else {
            btnElement.classList.remove('liked');
        }
    }
}

// Toggle Replies Visibility
function toggleReplies(repliesId) {
    const el = document.getElementById(repliesId);
    if(el) {
        el.classList.toggle('hidden');
    }
}

// Add Reply
async function submitReply(postId) {
    const inputEl = document.getElementById(`reply-input-${postId}`);
    const replyText = inputEl.value.trim();
    
    if (!replyText) return;

    const res = await apiCall('reply', { post_id: postId, reply: replyText });
    if (res.success) {
        window.location.reload(); // Simple reload for fresh data
    }
}

// --- Report Modal Logic ---
function openReportModal(postId, replyId, reportedUserId) {
    document.getElementById('reportPostId').value = postId;
    document.getElementById('reportReplyId').value = replyId || '';
    document.getElementById('reportedUserId').value = reportedUserId || '';
    document.getElementById('reportReason').value = '';
    document.getElementById('reportDetails').value = '';
    
    document.getElementById('reportModal').classList.remove('hidden');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.add('hidden');
}

async function submitReportAction() {
    const postId = document.getElementById('reportPostId').value;
    const replyId = document.getElementById('reportReplyId').value;
    const reportedUserId = document.getElementById('reportedUserId').value;
    const reason = document.getElementById('reportReason').value;
    const details = document.getElementById('reportDetails').value;
    
    if (!reason) {
        // Silently require reason or show UI validation
        return;
    }
    
    const res = await apiCall('report', { 
        post_id: postId, 
        reply_id: replyId ? replyId : null,
        reported_user_id: reportedUserId,
        reason: reason,
        details: details
    });
    
    if (res.success) {
        closeReportModal();
        if(replyId) {
            toggleMenu(`menu-rep-${replyId}`);
        } else {
            toggleMenu(`menu-${postId}`);
        }
    }
}

// --- Delete Modal Logic ---
function openDeleteModal(postId, replyId) {
    document.getElementById('deletePostId').value = postId;
    document.getElementById('deleteReplyId').value = replyId || '';
    
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

async function confirmDeleteAction() {
    const postId = document.getElementById('deletePostId').value;
    const replyId = document.getElementById('deleteReplyId').value;
    
    if (replyId) {
        const res = await apiCall('delete_reply', { post_id: postId, reply_id: replyId });
        if (res.success) {
            window.location.reload();
        }
    } else {
        const res = await apiCall('delete_post', { post_id: postId });
        if (res.success) {
            window.location.reload();
        }
    }
}
