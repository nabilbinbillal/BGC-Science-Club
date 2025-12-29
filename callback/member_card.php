<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/db.php';

// Get member data
$member_id = isset($_GET['member_id']) ? trim($_GET['member_id']) : '';

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
    
    // Format dates - avoid showing 1970 for invalid/zero timestamps
    if (function_exists('formatDate')) {
        $join_date = formatDate($member['created_at'], 'd M Y');
    } else {
        $ts = strtotime($member['created_at'] ?? '');
        $join_date = ($ts === false || $ts <= 0) ? date('d M Y') : date('d M Y', $ts);
    }
    $expiry_date = date('d M Y', strtotime('+1 year'));
    
} catch (Exception $e) {
    error_log('Error in member card: ' . $e->getMessage());
    header('Location: /500.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Card - <?php echo htmlspecialchars($member['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        .card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin: 2rem auto;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .card-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: -60px auto 1rem;
            background: #fff;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-info {
            padding: 1.5rem;
            text-align: center;
        }
        .member-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        .member-id {
            color: #4f46e5;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .member-details {
            text-align: left;
            margin-top: 1.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .detail-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .detail-value {
            color: #1f2937;
            font-weight: 500;
        }
        .card-footer {
            background: #f9fafb;
            padding: 1rem;
            text-align: center;
            font-size: 0.75rem;
            color: #6b7280;
        }
        @media print {
            body {
                padding: 20px;
            }
            .no-print {
                display: none;
            }
            .card {
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="card">
            <div class="card-header">
                <div class="text-xl font-bold">BGC SCIENCE CLUB</div>
                <div class="text-sm opacity-80">Member Identification Card</div>
            </div>
            
            <div class="member-photo">
                <?php if (!empty($member['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="Member Photo">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                        <i class="fas fa-user text-4xl text-gray-400"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="member-info">
                <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                <div class="member-id">ID: <?php echo htmlspecialchars($member['member_id']); ?></div>
                
                <div class="member-details">
                    <?php if (!empty($member['department'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Department:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($member['department']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-row">
                        <span class="detail-label">Member Since:</span>
                        <span class="detail-value"><?php echo $join_date; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Expires:</span>
                        <span class="detail-value"><?php echo $expiry_date; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <p>This is an official BGC Science Club membership card.</p>
                <p>If found, please return to BGC Science Club.</p>
            </div>
        </div>
        
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-print mr-2"></i> Print Card
            </button>
        </div>
    </div>
</body>
</html>
