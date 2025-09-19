// Report modal functions
function openReportModal(messageId) {
    document.getElementById('reportMessageId').value = messageId;
    document.getElementById('reportReason').value = '';
    document.getElementById('reportModal').classList.add('active');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('active');
}

function submitReport(event) {
    event.preventDefault();
    
    const messageId = document.getElementById('reportMessageId').value;
    const reason = document.getElementById('reportReason').value;

    fetch('../backend/report_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message_id: messageId,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            alert('Message reported successfully');
            closeReportModal();
        } else {
            alert(data.message || 'Failed to report message');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to report message');
    });
}