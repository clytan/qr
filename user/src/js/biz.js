let currentTab = 'programmes';
let currentProgramme = null;

$(document).ready(function () {
    loadProgrammes();
    // Only load these if isBizUser is true, but since this is a separate file, we check the global variable defined in PHP or ensure this runs conditionally.
    // However, to keep it clean, we'll check if the element exists or rely on the global variable if defined.
    if (typeof isBizUser !== 'undefined' && isBizUser) {
        loadMyProgrammes();
    }

    // Initialize Prize Carousel
    if (jQuery().owlCarousel) {
        jQuery('#prizeCarousel').owlCarousel({
            loop: true,
            margin: 10,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 3000,
            responsive: {
                0: { items: 1 },
                600: { items: 2 },
                1000: { items: 3 }
            }
        });
    }
});

function showTab(tab) {
    currentTab = tab;

    // Update button styles
    $('.poll-tab-item').each(function () {
        $(this).css({
            'background': 'transparent',
            'color': '#94a3b8'
        }).removeClass('active');
    });

    // Find and activate the correct tab
    $('.poll-tab-item').each(function () {
        const text = $(this).text().trim().toLowerCase();
        if ((tab === 'programmes' && text === 'all') ||
            (tab === 'my_referrals' && text === 'referrals') ||
            (tab === 'my_collabs' && text === 'created')) {
            $(this).css({
                'background': 'rgba(255, 255, 255, 0.15)',
                'color': '#fff',
                'font-weight': '600'
            }).addClass('active');
        }
    });

    $('.tab-content').removeClass('active');
    $(`#tab-${tab}`).addClass('active');

    if (tab === 'my_referrals') {
        loadMyReferrals();
    } else if (tab === 'my_collabs') {
        loadMyProgrammes();
    }
}

function loadProgrammes() {
    $.post('', { action: 'get_programmes' }, function (response) {
        if (response.status && response.data.length > 0) {
            renderProgrammes(response.data);
        } else {
            $('#programmes-grid').html(`
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Active Programmes</h3>
                    <p>Check back soon for new partner opportunities!</p>
                </div>
            `);
        }
    }, 'json').fail(function () {
        showToast('Failed to load programmes', 'error');
    });
}

function renderProgrammes(programmes) {
    let html = '';

    programmes.forEach(p => {
        html += `
            <div class="programme-card">
                <div class="programme-content">
                    <div class="programme-header">${escapeHtml(p.programme_header)}</div>
                    ${p.company_name ? `<div class="company-name"><i class="fas fa-building"></i> ${escapeHtml(p.company_name)}</div>` : ''}
                    
                    <div class="programme-description">${escapeHtml(p.description)}</div>
                    
                    <div class="commission-box">
                        <div class="commission-label"><i class="fas fa-dollar-sign"></i> Commission Structure</div>
                        <div class="commission-text">${escapeHtml(p.commission_details)}</div>
                    </div>
                    
                    ${p.product_link ? `
                        <a href="${p.product_link}" target="_blank" class="product-link">
                            <i class="fas fa-external-link-alt"></i>
                            View Company Website
                        </a>
                    ` : ''}
                    
                    <button class="refer-button" onclick="openReferModal(${p.id}, '${escapeHtml(p.programme_header)}')">
                        <i class="fas fa-user-plus"></i>
                        Refer a Client
                    </button>
                </div>
            </div>
        `;
    });

    $('#programmes-grid').html(html);
}

function loadMyReferrals() {
    $.post('', { action: 'get_my_referrals' }, function (response) {
        if (response.status && response.data.length > 0) {
            renderMyReferrals(response.data);
        } else {
            $('#referrals-body').html(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                        No referrals yet. Start referring clients!
                    </td>
                </tr>
            `);
        }
    }, 'json').fail(function () {
        showToast('Failed to load referrals', 'error');
    });
}

function renderMyReferrals(referrals) {
    let html = '';

    referrals.forEach(r => {
        html += `
            <tr>
                <td>${escapeHtml(r.programme_header)}</td>
                <td>${escapeHtml(r.client_name)}</td>
                <td>${escapeHtml(r.client_phone)}</td>
                <td>${escapeHtml(r.product_name)}</td>
                <td><span class="status-badge ${r.status}">${r.status.replace('_', ' ')}</span></td>
                <td>${formatDate(r.created_on)}</td>
            </tr>
        `;
    });

    $('#referrals-body').html(html);
}

function openReferModal(programmeId, programmeName) {
    currentProgramme = { id: programmeId, name: programmeName };
    $('#programme-id').val(programmeId);
    $('#referForm')[0].reset();
    $('#referModal').addClass('show');
}

function closeModal() {
    $('#referModal').removeClass('show');
}

$('#referForm').on('submit', function (e) {
    e.preventDefault();

    // Prevent duplicate submissions
    const $submitBtn = $(this).find('button[type="submit"]');
    if ($submitBtn.prop('disabled')) {
        return false; // Already submitting
    }

    // Disable submit button
    $submitBtn.prop('disabled', true).text('Submitting...');

    $.post('', {
        action: 'submit_referral',
        programme_id: $('#programme-id').val(),
        client_name: $('#client-name').val(),
        client_phone: $('#client-phone').val(),
        client_email: $('#client-email').val(),
        product_name: $('#product-name').val()
    }, function (response) {
        // Re-enable button
        $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Referral');

        if (response.status) {
            showToast(response.message, 'success');
            closeModal();
            if (currentTab === 'my_referrals') {
                loadMyReferrals();
            }
        } else {
            showToast(response.message, 'error');
        }
    }, 'json').fail(function () {
        // Re-enable button on error
        $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Referral');
        showToast('Failed to submit referral. Please try again.', 'error');
    });
});

function showToast(message, type) {
    const toast = $('#toast');
    toast.removeClass('success error').addClass(type).text(message).addClass('show');
    setTimeout(() => toast.removeClass('show'), 4000);
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ===== BIZ USER FUNCTIONS =====
function openCreateModal() {
    $('#createProgrammeForm')[0].reset();
    $('#createProgrammeModal').addClass('show');
}

function closeCreateModal() {
    $('#createProgrammeModal').removeClass('show');
}

function loadMyProgrammes() {
    $.post('', { action: 'get_my_programmes' }, function (response) {
        if (response.status && response.data.length > 0) {
            renderMyProgrammes(response.data);
        } else {
            $('#my-collabs-grid').html(`
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Created Programmes</h3>
                    <p>Click "Create Partner Programme" to get started!</p>
                </div>
            `);
        }
    }, 'json');
}

function renderMyProgrammes(programmes) {
    let html = '';

    programmes.forEach(p => {
        html += `
            <div class="collab-card">
                <div class="collab-card-header">
                    <div class="collab-title">${escapeHtml(p.programme_header)}</div>
                    <span class="collab-status ${p.status}">${p.status}</span>
                </div>
                ${p.company_name ? `<div style="color: #E9437A; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-building"></i> ${escapeHtml(p.company_name)}</div>` : ''}
                <div class="collab-desc">${escapeHtml(p.description || '')}</div>
                <div style="font-size: 12px; color: #E2AD2A; margin: 10px 0;">
                    <i class="fas fa-users"></i> ${p.referral_count || 0} referrals
                </div>
                <button class="collab-delete-btn" onclick="deleteProgramme(${p.id})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;
    });

    $('#my-collabs-grid').html(html);
}

function deleteProgramme(id) {
    if (!confirm('Are you sure you want to delete this programme?')) return;

    $.post('', { action: 'delete_programme', programme_id: id }, function (response) {
        if (response.status) {
            showToast(response.message, 'success');
            loadMyProgrammes();
        } else {
            showToast(response.message, 'error');
        }
    }, 'json');
}

// Submit create programme form
$('#createProgrammeForm').on('submit', function (e) {
    e.preventDefault();

    $.post('', $(this).serialize() + '&action=create_programme', function (response) {
        if (response.status) {
            showToast(response.message, 'success');
            closeCreateModal();
            loadMyProgrammes();
            loadProgrammes(); // Reload the main list too
        } else {
            showToast(response.message, 'error');
        }
    }, 'json').fail(function () {
        showToast('Failed to create programme', 'error');
    });
});

// Close modal on overlay click
$('#createProgrammeModal').on('click', function (e) {
    if (e.target === this) closeCreateModal();
});
