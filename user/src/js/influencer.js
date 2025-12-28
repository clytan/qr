// Tab Management
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
        if ((tab === 'available' && text.includes('available')) ||
            (tab === 'my_collabs' && text.includes('accepted')) ||
            (tab === 'my_created' && text.includes('created'))) {
            $(this).css({
                'background': 'rgba(255, 255, 255, 0.15)',
                'color': '#fff',
                'font-weight': '600'
            }).addClass('active');
        }
    });

    // Update content
    $('.tab-content').removeClass('active');
    $(`#tab-${tab}`).addClass('active');

    // Load data
    if (tab === 'my_created') {
        if (typeof loadMyCreated === 'function') {
            loadMyCreated();
        }
    } else {
        loadCollabs(tab);
    }
}

// Load Collaborations
function loadCollabs(status) {
    $.post('', {
        action: 'get_collabs',
        status: status
    }, function (response) {
        if (response.status && response.data.length > 0) {
            renderCollabs(response.data, status);
            updateStats(response.data, status);
        } else {
            const emptyMessage = status === 'available'
                ? 'No collaborations available at the moment. Check back soon!'
                : "You haven't accepted any collaborations yet. Browse available opportunities!";

            $(`#${status === 'available' ? 'available' : 'my-collabs'}-grid`).html(`
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Collaborations</h3>
                    <p>${emptyMessage}</p>
                </div>
            `);
        }
    }, 'json').fail(function () {
        showToast('Failed to load collaborations', 'error');
    });
}

// Render Collaborations
function renderCollabs(collabs, status) {
    let html = '';

    collabs.forEach(c => {
        const photo1 = c.photo_1 ? `<img src="../../../${c.photo_1}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
        const photo2 = c.photo_2 ? `<img src="../../../${c.photo_2}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
        const photo3 = c.photo_3 ? `<img src="../../../${c.photo_3}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';

        const financialText = c.financial_type === 'paid'
            ? `₹${parseFloat(c.financial_amount).toLocaleString()}`
            : 'Barter';

        const financialIcon = c.financial_type === 'paid' ? 'money-bill-wave' : 'handshake';

        const isAccepted = status === 'my_collabs';

        html += `
            <div class="collab-card">
                <div class="product-photos">
                    <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_1}')">
                        ${photo1}
                        ${c.photo_1 ? '<div class="download-overlay"><i class="fas fa-download download-btn"></i></div>' : ''}
                    </div>
                    <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_2}')">
                        ${photo2}
                        ${c.photo_2 ? '<div class="download-overlay"><i class="fas fa-download download-btn"></i></div>' : ''}
                    </div>
                    <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_3}')">
                        ${photo3}
                        ${c.photo_3 ? '<div class="download-overlay"><i class="fas fa-download download-btn"></i></div>' : ''}
                    </div>
                </div>
                
                <div class="collab-content">
                    <div class="collab-header">
                        <div>
                            <div class="collab-title">
                                ${escapeHtml(c.collab_title)}
                                <button class="copy-btn" onclick="copyToClipboard('${escapeHtml(c.collab_title.replace(/'/g, "\\'"))}')" title="Copy Title">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <span class="category-badge">${c.category === 'other' ? (c.other_category || 'Other') : c.category}</span>
                        </div>
                        <span class="status-badge ${c.status}">${c.status}</span>
                    </div>
                    
                    <div class="collab-description">
                        ${escapeHtml(c.product_description)}
                        <button class="copy-btn-sm" onclick="copyToClipboard('${escapeHtml(c.product_description.replace(/'/g, "\\'"))}')" title="Copy Description">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    
                    ${c.product_link ? `
                        <a href="${c.product_link}" target="_blank" class="product-link">
                            <i class="fas fa-external-link-alt"></i>
                            View Product Page
                        </a>
                    ` : ''}
                    
                    <div class="financial-box">
                        <span class="financial-label">
                            <i class="fas fa-${financialIcon}"></i>
                            ${c.financial_type === 'paid' ? 'Payment' : 'Barter Deal'}
                        </span>
                        <span class="financial-value">${financialText}</span>
                    </div>
                    
                    <div class="collab-summary">
                        <strong>📋 Requirements:</strong><br>
                        ${escapeHtml(c.detailed_summary)}
                    </div>
                    
                    ${isAccepted ? `
                        <div class="accepted-badge">
                            <i class="fas fa-check-circle"></i> You accepted this collaboration
                        </div>
                    ` : `
                        <button class="accept-button" onclick="acceptCollab(${c.id})">
                            <i class="fas fa-handshake"></i>
                            Accept Collaboration
                        </button>
                    `}
                </div>
            </div>
        `;
    });

    $(`#${status === 'available' ? 'available' : 'my-collabs'}-grid`).html(html);
}

// Update Stats
function updateStats(collabs, status) {
    if (status === 'available') {
        $('#total-collabs').text(collabs.length);
    } else {
        $('#your-collabs').text(collabs.length);
    }
}

// Accept Collaboration
function acceptCollab(id) {
    if (!confirm('Are you sure you want to accept this collaboration? You will be committed to delivering the requirements.')) {
        return;
    }

    $.post('', {
        action: 'accept_collab',
        collab_id: id
    }, function (response) {
        if (response.status) {
            showToast(response.message, 'success');
            loadCollabs(currentTab);
            // Reload the other tab's data too
            if (currentTab === 'available') {
                $.post('', { action: 'get_collabs', status: 'my_collabs' }, function (r) {
                    if (r.status) updateStats(r.data, 'my_collabs');
                }, 'json');
            }
        } else {
            showToast(response.message, 'error');
        }
    }, 'json').fail(function () {
        showToast('Failed to accept collaboration', 'error');
    });
}

// Download Photo
function downloadPhoto(url) {
    if (!url || url.includes('null') || url.includes('undefined')) return;
    window.open(url, '_blank');
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        showToast('Failed to copy', 'error');
    });
}

// Toast Notification
function showToast(message, type) {
    const toast = $('#toast');
    toast.removeClass('success error').addClass(type).text(message).addClass('show');
    setTimeout(() => toast.removeClass('show'), 4000);
}

// Utility Functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

// ===== BIZ USER FUNCTIONS =====

// Open Create Modal
function openCreateModal() {
    $('#createCollabForm')[0].reset();
    $('.photo-upload img').remove();
    $('#amountGroup').hide();
    $('#createModal').addClass('show');
}

function closeCreateModal() {
    $('#createModal').removeClass('show');
}

// Toggle Amount Field
function toggleAmount(select) {
    $('#amountGroup').toggle(select.value === 'paid');
}

// Toggle Other Category
function toggleOtherCategory(select) {
    if (select.value === 'other') {
        $('#otherCategoryGroup').show();
        $('input[name="other_category"]').prop('required', true);
    } else {
        $('#otherCategoryGroup').hide();
        $('input[name="other_category"]').prop('required', false);
    }
}

// Preview Photo
function previewPhoto(input, uploadId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const container = $('#' + uploadId);
            container.find('img').remove();
            container.prepend(`<img src="${e.target.result}">`);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Load My Created Collabs
function loadMyCreated() {
    $.post('', { action: 'get_my_created' }, function (response) {
        if (response.status && response.data.length > 0) {
            renderMyCreated(response.data);
            $('#created-collabs').text(response.data.length);
        } else {
            $('#my-created-grid').html(`
                <div class="empty-state">
                    <i class="fas fa-edit"></i>
                    <h3>No Created Collaborations</h3>
                    <p>Click "Create Collaboration" to get started!</p>
                </div>
            `);
            $('#created-collabs').text('0');
        }
    }, 'json');
}

// Render My Created Collabs
function renderMyCreated(collabs) {
    let html = '';

    collabs.forEach(c => {
        const photo1 = c.photo_1 ? `<img src="../../../${c.photo_1}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
        const photo2 = c.photo_2 ? `<img src="../../../${c.photo_2}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
        const photo3 = c.photo_3 ? `<img src="../../../${c.photo_3}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';

        const financialText = c.financial_type === 'paid'
            ? `₹${parseFloat(c.financial_amount).toLocaleString()}`
            : 'Barter';

        const financialIcon = c.financial_type === 'paid' ? 'money-bill-wave' : 'handshake';

        html += `
            <div class="collab-card">
                <div class="product-photos">
                    <div class="product-photo">${photo1}</div>
                    <div class="product-photo">${photo2}</div>
                    <div class="product-photo">${photo3}</div>
                </div>
                
                <div class="collab-content">
                    <div class="collab-header">
                        <div>
                            <div class="collab-title">${escapeHtml(c.collab_title)}</div>
                            <span class="category-badge">${c.category}</span>
                        </div>
                        <span class="status-badge ${c.status}">${c.status}</span>
                    </div>
                    
                    <div class="collab-description">${escapeHtml(c.product_description)}</div>
                    
                    <div class="financial-box">
                        <span class="financial-label">
                            <i class="fas fa-${financialIcon}"></i>
                            ${c.financial_type === 'paid' ? 'Payment' : 'Barter Deal'}
                        </span>
                        <span class="financial-value">${financialText}</span>
                    </div>
                    
                    ${c.status === 'pending' ? `
                        <button class="delete-btn" onclick="deleteCollab(${c.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ` : `
                        <div style="color: #4ade80; font-size: 13px;">
                            <i class="fas fa-check-circle"></i> Accepted by influencer
                        </div>
                    `}
                </div>
            </div>
        `;
    });

    $('#my-created-grid').html(html);
}

// Delete Collaboration
function deleteCollab(id) {
    if (!confirm('Are you sure you want to delete this collaboration?')) return;

    $.post('', { action: 'delete_collab', collab_id: id }, function (response) {
        if (response.status) {
            showToast(response.message, 'success');
            loadMyCreated();
            loadCollabs('available');
        } else {
            showToast(response.message, 'error');
        }
    }, 'json');
}

// Submit Create Form
$('#createCollabForm').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'create_collab');

    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.status) {
                showToast(response.message, 'success');
                closeCreateModal();
                loadMyCreated();
                loadCollabs('available');
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function () {
            showToast('Failed to create collaboration', 'error');
        }
    });
});

// Close modal on overlay click
$('#createModal').on('click', function (e) {
    if (e.target === this) closeCreateModal();
});

// Main Initialization
let currentTab = 'available';

$(document).ready(function () {
    loadCollabs('available');
    // Only load my created if user is a biz user
    // The variable isBizUser is defined in the parent PHP file
    if (typeof isBizUser !== 'undefined' && isBizUser) {
        loadMyCreated();
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
