<?php
// admin/src/ui/search.php
require_once '../components/auth_check.php';
$query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Search Results - Admin Panel</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, html {
            background: #0f172a;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
            margin: 0;
            padding: 0;
        }
        .search-container { padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-title { font-size: 24px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px; }
        .search-meta { color: #94a3b8; font-size: 14px; }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .section-title { font-size: 18px; font-weight: 600; color: #f8fafc; margin: 0; }
        .section-badge { 
            background: rgba(255,255,255,0.1); 
            padding: 2px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            color: #cbd5e1; 
        }

        .result-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .result-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: inherit;
        }
        .result-card:hover {
            background: rgba(30, 41, 59, 1);
            transform: translateY(-2px);
            border-color: rgba(230, 119, 83, 0.3);
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }

        .info { flex: 1; min-width: 0; }
        .name { font-weight: 600; font-size: 16px; color: #f1f5f9; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sub-text { font-size: 13px; color: #94a3b8; display: flex; align-items: center; gap: 6px; min-width: 0; }
        .sub-text span:last-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge { font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.1); flex-shrink: 0; }

        .loading { text-align: center; padding: 40px; color: #94a3b8; }
        .no-results { text-align: center; padding: 40px; color: #64748b; font-style: italic; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="search-container">
            <div class="page-header">
                <h1 class="page-title">Search Results</h1>
                <p class="search-meta">Showing results for "<?php echo $query; ?>"</p>
            </div>

            <div id="searchResults">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px;">Searching database...</p>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const query = "<?php echo $query; ?>";
            
            if(query.length < 2) {
                $('#searchResults').html('<div class="no-results">Please enter a search term of at least 2 characters.</div>');
                return;
            }

            $.ajax({
                url: '../backend/global_search.php',
                type: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        renderResults(response.data);
                    } else {
                        $('#searchResults').html('<div class="no-results">' + (response.message || 'Error occurred') + '</div>');
                    }
                },
                error: function() {
                    $('#searchResults').html('<div class="no-results">Failed to fetch results</div>');
                }
            });

            function renderResults(data) {
                let html = '';
                
                // Users Section
                if (data.users && data.users.length > 0) {
                    html += `
                        <div class="section-header">
                            <h2 class="section-title"><i class="fas fa-users" style="color: #e67753; margin-right: 8px;"></i> Users</h2>
                            <span class="section-badge">${data.users.length}</span>
                        </div>
                        <div class="result-grid">
                    `;
                    
                    data.users.forEach(user => {
                        const initial = (user.user_full_name || 'U').charAt(0).toUpperCase();
                        const avatar = user.user_image_path 
                            ? `<img src="../../..${user.user_image_path}" onerror="this.parentElement.innerHTML='${initial}'">`
                            : initial;
                            
                        html += `
                            <a href="#" class="result-card">
                                <div class="avatar">${avatar}</div>
                                <div class="info">
                                    <div class="name">${user.user_full_name || 'No Name'}</div>
                                    <div class="sub-text">
                                        <span class="badge">${user.user_qr_id}</span>
                                        <span>${user.user_email || ''}</span>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                }

                // Communities Section
                if (data.communities && data.communities.length > 0) {
                    html += `
                        <div class="section-header">
                            <h2 class="section-title"><i class="fas fa-comments" style="color: #E9437A; margin-right: 8px;"></i> Communities</h2>
                            <span class="section-badge">${data.communities.length}</span>
                        </div>
                        <div class="result-grid">
                    `;
                    
                    data.communities.forEach(comm => {
                        const initial = (comm.community_name || 'C').charAt(0).toUpperCase();
                         // Fix path? usually similar to users
                        const avatar = initial; // Placeholder for now as community images might vary
                        
                        html += `
                            <a href="#" class="result-card">
                                <div class="avatar" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                                    ${avatar}
                                </div>
                                <div class="info">
                                    <div class="name">${comm.community_name}</div>
                                    <div class="sub-text">
                                        <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd;">Community</span>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                }

                if ((!data.users || data.users.length === 0) && (!data.communities || data.communities.length === 0)) {
                    html = '<div class="no-results">No matches found for "' + query + '"</div>';
                }

                $('#searchResults').html(html);
            }
        });
    </script>
</body>
</html>
