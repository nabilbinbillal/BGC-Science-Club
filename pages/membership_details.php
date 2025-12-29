<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define ABSPATH if not defined
if (!defined('ABSPATH')) {
    // Path to wp-load.php in the root directory
    $wp_load = dirname(__DIR__, 2) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once($wp_load);
    } else {
        die('Error: Could not find WordPress. Make sure the wp-load.php file exists in the root directory.');
    }
}

// Include header if not already included
if (!function_exists('get_header')) {
    $header_path = __DIR__ . '/../includes/header.php';
    if (file_exists($header_path)) {
        include $header_path;
    }
}

// Get member data
$member_id = isset($_GET['member_id']) ? trim($_GET['member_id']) : '';

if (empty($member_id)) {
    header('Location: 404.php');
    exit;
}

try {
    // Fetch member details from database using WordPress functions
    global $wpdb;
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}members WHERE member_id = %s",
        $member_id
    ), 'ARRAY_A');

    if (!$member) {
        header('Location: 404.php');
        exit;
    }
    
    // Format dates - use helper if available to prevent 1970-01-01 for invalid dates
    if (function_exists('formatDate')) {
        $join_date = formatDate($member['created_at'], 'd M Y');
    } else {
        $ts = strtotime($member['created_at'] ?? '');
        $join_date = ($ts === false || $ts <= 0) ? date('d M Y') : date('d M Y', $ts);
    }
    $expiry_date = date('d M Y', strtotime('+1 year'));
    
    // Set page title
    $page_title = 'Membership Details - ' . htmlspecialchars($member['name']);
    $page = 'membership_details';
    
} catch (PDOException $e) {
    error_log('Database error in membership_details.php: ' . $e->getMessage());
    header('Location: 500.php');
    exit;
} catch (Exception $e) {
    error_log('Error in membership_details.php: ' . $e->getMessage());
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

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">Membership Details</h1>
            <p class="mt-2 text-sm text-gray-600">View and manage your BGC Science Club membership information</p>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-blue-600 to-indigo-700">
                <h3 class="text-lg leading-6 font-medium text-white">
                    Member Information
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-blue-100">
                    Membership #<?php echo htmlspecialchars($member['member_id']); ?>
                </p>
            </div>
            
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Full name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo htmlspecialchars($member['name']); ?>
                        </dd>
                    </div>
                    
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Member ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo htmlspecialchars($member['member_id']); ?>
                        </dd>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Email address</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo htmlspecialchars($member['email']); ?>
                        </dd>
                    </div>
                    
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo !empty($member['phone']) ? htmlspecialchars($member['phone']) : 'Not provided'; ?>
                        </dd>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo !empty($member['department']) ? htmlspecialchars($member['department']) : 'Not specified'; ?>
                        </dd>
                    </div>
                    
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Membership Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </dd>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo $join_date; ?>
                        </dd>
                    </div>
                    
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Membership Expires</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo $expiry_date; ?>
                        </dd>
                    </div>
                    
                    <?php if (!empty($member['address'])): ?>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo nl2br(htmlspecialchars($member['address'])); ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($member['bio'])): ?>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Bio</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo nl2br(htmlspecialchars($member['bio'])); ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
            
            <div class="px-4 py-4 bg-gray-50 text-right sm:px-6">
                <a href="member_card.php?member_id=<?php echo urlencode($member_id); ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-id-card mr-2"></i> View Member Card
                </a>
                <button type="button" onclick="window.print()" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>
        </div>
        
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Membership Benefits
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Your BGC Science Club membership includes:
                </p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700">
                    <li>Access to exclusive science club events and workshops</li>
                    <li>Discounts on science-related merchandise and equipment</li>
                    <li>Opportunity to participate in research projects</li>
                    <li>Networking with like-minded science enthusiasts</li>
                    <li>Access to members-only online resources</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
