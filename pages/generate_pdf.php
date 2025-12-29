<?php
require_once '../vendor/autoload.php';
require_once 'config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get member data
$member_id = $_POST['member_id'] ?? '';
$name = $_POST['name'] ?? '';
$class_level = $_POST['class_level'] ?? '';
$department = $_POST['department'] ?? '';
$roll_no = $_POST['roll_no'] ?? '';
$current_year = date('Y');

// Create PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// HTML content for the PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>BGC Science Club Membership Card</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .card {
            width: 350px;
            border: 2px solid #3b82f6;
            border-radius: 10px;
            padding: 20px;
            margin: 0 auto;
            text-align: center;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            margin: -20px -20px 20px -20px;
            border-radius: 8px 8px 0 0;
        }
        .logo {
            max-width: 80px;
            margin: 0 auto 15px;
        }
        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #3b82f6;
            margin: 0 auto 15px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-info {
            text-align: left;
            margin: 20px 0;
        }
        .member-info p {
            margin: 8px 0;
            font-size: 14px;
        }
        .member-id {
            font-weight: bold;
            font-size: 16px;
            color: #3b82f6;
            margin: 15px 0;
        }
        .footer {
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2>BGC Science Club</h2>
            <p>Member ID: ' . htmlspecialchars($member_id) . '</p>
        </div>
        
        <div class="member-photo">
            <!-- Placeholder for photo -->
            <div style="text-align: center; padding: 20px;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
        </div>
        
        <div class="member-info">
            <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
            <p><strong>Class:</strong> ' . htmlspecialchars($class_level) . '</p>
            <p><strong>Department:</strong> ' . htmlspecialchars($department) . '</p>
            <p><strong>Roll No:</strong> ' . htmlspecialchars($roll_no) . '</p>
            <p><strong>Year:</strong> ' . $current_year . '</p>
        </div>
        
        <div class="member-id">
            ' . htmlspecialchars($member_id) . '
        </div>
        
        <div class="footer">
            <p>This is an auto-generated membership card. Please keep it safe.</p>
            <p>© ' . $current_year . ' BGC Science Club. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

// Load HTML to Dompdf
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A6', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF
$dompdf->stream("bgc_member_" . $member_id . ".pdf", ["Attachment" => true]);
?>
