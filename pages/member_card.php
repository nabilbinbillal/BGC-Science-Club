<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/db.php';

// Get member data
$member_id = isset($_GET['member_id']) ? trim($_GET['member_id']) : '';

// Get site settings
$settings = [];
try {
    $settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Error fetching settings: ' . $e->getMessage());
}

if (empty($member_id)) {
    header('Location: /404.php');
    exit;
}

try {
    // Prepare and execute the query
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = :member_id");
    $stmt->execute(['member_id' => $member_id]);
    $member = $stmt->fetch();

    if (!$member) {
        header('Location: /404.php');
        exit;
    }
    
    // Format dates - use helper if available and fall back safely for direct access
    if (function_exists('formatDate')) {
        $join_date = formatDate($member['created_at'], 'd M Y');
    } else {
        $ts = strtotime($member['created_at'] ?? '');
        $join_date = ($ts === false || $ts <= 0) ? date('d M Y') : date('d M Y', $ts);
    }
    $expiry_date = date('d M Y', strtotime('+1 year'));
    
    // Set page title
    $page_title = 'Member Card - ' . htmlspecialchars($member['name']);
    $page = 'member_card';
    
} catch (PDOException $e) {
    error_log('Database error in member_card.php: ' . $e->getMessage());
    header('Location: 500.php');
    exit;
} catch (Exception $e) {
    error_log('Error in member_card.php: ' . $e->getMessage());
    header('Location: 500.php');
    exit;
}

// Include header if not already included
if (!function_exists('get_header')) {
    $header_path = __DIR__ . '/../includes/header.php';
    if (file_exists($header_path)) {
        include $header_path;
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
            
            * {
                font-family: 'Poppins', sans-serif;
            }
            
            .card {
                background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%);
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                overflow: hidden;
                margin-bottom: 2rem;
                border: 1px solid rgba(255, 255, 255, 0.5);
                backdrop-filter: blur(10px);
            }
            
            .card-header {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                color: white;
                padding: 1.5rem;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            
            .card-header::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
                transform: rotate(30deg);
            }
            
            .card-body {
                padding: 2rem;
                position: relative;
            }
            
            .member-photo {
                width: 140px;
                height: 140px;
                border-radius: 50%;
                margin: -70px auto 1.5rem;
                background: linear-gradient(145deg, #f0f0f0 0%, #e0e0e0 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border: 4px solid white;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                position: relative;
                z-index: 2;
            }
            
            .member-photo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .member-info {
                background: white;
                margin: 1.5rem 0;
                text-align: left;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            
            .info-row {
                display: flex;
                margin: 0.75rem 0;
                padding: 0.5rem 0;
                border-bottom: 1px solid #f3f4f6;
                align-items: center;
            }
            
            .info-row:last-child {
                border-bottom: none;
            }
            
            .info-label {
                font-size: 0.875rem;
                color: #6b7280;
                font-weight: 500;
                width: 120px;
                flex-shrink: 0;
            }
            
            .info-value {
                font-weight: 500;
                color: #1f2937;
                flex-grow: 1;
                text-align: right;
            }
            
            .member-id {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                padding: 0.75rem 1rem;
                border-radius: 8px;
                text-align: center;
                font-size: 1.1rem;
                font-weight: 600;
                color: #4f46e5;
                margin: 1.5rem 0;
                border: 1px dashed #c7d2fe;
                letter-spacing: 0.5px;
                position: relative;
                overflow: hidden;
            }
            
            .member-id::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #4f46e5, #8b5cf6, #4f46e5);
                background-size: 200% 100%;
                animation: gradientBG 3s ease infinite;
            }
            
            .qr-code {
                width: 100px;
                height: 100px;
                margin: 1rem auto;
                background: white;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .qr-code img {
                max-width: 100%;
                height: auto;
            }
            
            .screenshot-instructions {
                background: #f8fafc;
                border: 1px dashed #cbd5e1;
                border-radius: 8px;
                padding: 1rem;
                margin-top: 1.5rem;
                text-align: center;
            }
            
            .screenshot-instructions p {
                margin: 0.25rem 0;
                color: #475569;
            }
            
            .screenshot-instructions kbd {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                font-family: monospace;
                font-size: 0.875em;
                color: #334155;
            }
            
            .screenshot-instructions {
                margin-top: 1rem;
                text-align: center;
                font-size: 0.875rem;
                color: #475569;
                color: #6b7280;
                background: rgba(255, 255, 255, 0.7);
                padding: 0.75rem;
                border-radius: 8px;
                border: 1px dashed #d1d5db;
            }
            
            .card-footer {
                padding: 1rem 1.5rem;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                font-size: 0.75rem;
                color: #6b7280;
            }
            
            .logo {
                font-weight: 700;
                color: #4f46e5;
                letter-spacing: 0.5px;
            }
            
            @keyframes gradientBG {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            
            /* Print styles */
            @media print {
                @page {
                    size: 85mm 54mm; /* Standard ID card size */
                    margin: 0;
                }
                
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                    background: white !important;
                }
                
                .no-print {
                    display: none !important;
                }
                
                .card {
                    box-shadow: none;
                    margin: 0;
                    border-radius: 0;
                    min-height: 100vh;
                }
                
                .print-button, .print-instructions {
                    display: none;
                }
            }
        </style>

        <div class="card">
            <div class="card-header">
                <div class="text-xl font-bold tracking-wide">BGC SCIENCE CLUB</div>
                <div class="text-sm text-blue-100 mt-1">Member Identity Card</div>
            </div>
            
            <div class="card-body">
                <div class="member-photo">
                    <?php if (!empty($member['image'])): ?>
                        <img src="../uploads/members/<?php echo htmlspecialchars($member['image']); ?>" 
                             alt="<?php echo htmlspecialchars($member['name']); ?>" 
                             class="member-photo-img">
                    <?php else: ?>
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    <?php endif; ?>
                </div>
                
                <div class="member-info">
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['member_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Class</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['class_level']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Department</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['department']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Roll No</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['roll_no']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Valid Until</span>
                        <span class="info-value"><?php echo $expiry_date; ?></span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-500">
                        <div>Issued: <?php echo $join_date; ?></div>
                        <div class="text-xs mt-1">BGC Science Club © <?php echo date('Y'); ?></div>
                    </div>
                    
                    <div class="qr-code">
                        <!-- You can add a QR code generator here -->
                        <div class="text-center text-xs text-gray-400">
                            <div class="font-bold mb-1">SCAN ME</div>
                            <div>ID: <?php echo substr($member['member_id'], -4); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($settings['whatsapp_enabled']) && !empty($settings['whatsapp_link'])): ?>
                    <div class="mt-6 p-4 bg-green-50 border border-green-100 rounded-lg text-center">
                        <h3 class="font-medium text-green-800 mb-2">Join Our WhatsApp Community</h3>
                        <p class="text-sm text-green-700 mb-3">Connect with other members and stay updated with the latest club activities and events.</p>
                        <a href="<?php echo htmlspecialchars($settings['whatsapp_link']); ?>" 
                           target="_blank" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.5 2h-11C4 2 2 4 2 6.5v11C2 19.9 4.1 22 6.5 22h11c2.5 0 4.5-2 4.5-4.5v-11C22 4 20 2 17.5 2zm-5.3 15.3c-.2.2-.5.3-.7.3s-.5-.1-.7-.3l-1.4-1.4-1.4 1.4c-.2.2-.5.3-.7.3s-.5-.1-.7-.3c-.4-.4-.4-1 0-1.4l1.4-1.4-1.4-1.4c-.4-.4-.4-1 0-1.4s1-.4 1.4 0l1.4 1.4 1.4-1.4c.4-.4 1-.4 1.4 0s.4 1 0 1.4l-1.4 1.4 1.4 1.4c.4.4.4 1 0 1.4z"/>
                                <path d="M16.5 8.8c-.3 0-.5-.2-.5-.5v-1.8c0-1.1-.9-2-2-2H9.6c-1.1 0-2 .9-2 2v9.6c0 1.1.9 2 2 2h6.4c1.1 0 2-.9 2-2v-6.3c0-.3-.2-.5-.5-.5h-1.8c-.3 0-.5.2-.5.5v4.1c0 .3-.2.5-.5.5s-.5-.2-.5-.5V9.3c0-.3-.2-.5-.5-.5h-1.8c-.3 0-.5.2-.5.5v1.8c0 .3-.2.5-.5.5s-.5-.2-.5-.5V8.3c0-.3-.2-.5-.5-.5H8.6c-.3 0-.5.2-.5.5v6.4c0 .3.2.5.5.5h6.4c.3 0 .5-.2.5-.5V9.3c0-.3-.2-.5-.5-.5z"/>
                            </svg>
                            Join WhatsApp Group
                        </a>
                        <?php if (!empty($settings['whatsapp_number'])): ?>
                            <p class="mt-3 text-xs text-green-600">
                                Need help? WhatsApp us: +<?php echo htmlspecialchars($settings['whatsapp_country_code'] ?? '880') . ' ' . htmlspecialchars($settings['whatsapp_number']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-6 text-center text-sm text-gray-500 no-print">
                    <p>Need a copy? Take a screenshot of this page</p>
                    <p class="mt-1 text-xs">On Windows: Press <kbd class="px-1 py-0.5 bg-gray-100 rounded">Win + Shift + S</kbd></p>
                    <p class="text-xs">On Mac: Press <kbd class="px-1 py-0.5 bg-gray-100 rounded">Cmd + Shift + 4</kbd></p>
                </div>
{{ ... }}
            
            <div class="card-footer no-print">
                <div>
                    <div class="logo">BGC SCIENCE CLUB</div>
                    <div class="text-xs text-gray-500 mt-1">
                        <?php 
                        $contact_info = [];
                        if (!empty($settings['contact_phone'])) {
                            $contact_info[] = '📞 ' . htmlspecialchars($settings['contact_phone']);
                        }
                        if (!empty($settings['contact_email'])) {
                            $contact_info[] = '✉️ ' . htmlspecialchars($settings['contact_email']);
                        }
                        echo implode(' | ', $contact_info);
                        ?>
                    </div>
                </div>
                <div class="text-right">
                    <div>ID: <?php echo htmlspecialchars($member['member_id']); ?></div>
                    <?php if (!empty($settings['whatsapp_enabled']) && !empty($settings['whatsapp_number'])): ?>
                        <div class="text-xs text-blue-600">
                            <a href="https://wa.me/<?php echo htmlspecialchars(($settings['whatsapp_country_code'] ?? '880') . $settings['whatsapp_number']); ?>" 
                               target="_blank" 
                               class="hover:underline">
                                WhatsApp Support
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-print when the page loads (uncomment to enable)
// window.addEventListener('load', function() {
//     setTimeout(window.print, 1000);
// });

// Add animation to member photo on hover
const memberPhoto = document.querySelector('.member-photo');
if (memberPhoto) {
    memberPhoto.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.05)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    memberPhoto.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
}
</script>

<?php
// Include header if exists
$header_path = __DIR__ . '/../includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
} ?>
