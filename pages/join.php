<?php
// Set page title and description for SEO
$pageTitle = 'Join the BGC Science Club – Explore, Learn, Innovate';
$pageDescription = 'Become a member of Brahmanbaria Govt. College Science Club and take part in exciting science activities, workshops, and projects.';

// Add Open Graph and Twitter Card meta tags
echo '<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="' . htmlspecialchars($pageTitle) . '">
<meta property="og:description" content="' . htmlspecialchars($pageDescription) . '">
<meta property="og:url" content="https://bgcscienceclub.org/join">
<meta property="og:image" content="https://bgcscienceclub.org/pages/assets/images/JOIN.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="' . htmlspecialchars($pageTitle) . '">
<meta name="twitter:description" content="' . htmlspecialchars($pageDescription) . '">
<meta name="twitter:image" content="https://bgcscienceclub.org/pages/assets/images/JOIN.png">
';

$classOptions = getClassOptions(true);
$classNames = array_column($classOptions, 'name');
$classGroupMap = [];

// Set groups for intermediate classes
foreach ($classOptions as $option) {
    $classGroupMap[$option['name']] = [];
    if (strpos($option['name'], 'Intermediate') !== false) {
        $classGroupMap[$option['name']] = ['Science', 'Commerce', 'Arts'];
    }
}
$departmentOptions = getDepartmentOptions();
$whatsappLink = getWhatsAppLink();
$stickyTrackOption = $_POST['track_option'] ?? '';
$stickyGroupName = $_POST['group_name'] ?? '';
$trackParts = explode('|', $stickyTrackOption, 2);
$initialTrackType = $trackParts[0] ?? '';
$initialTrackValue = $trackParts[1] ?? '';
$initialClassSelected = $initialTrackType === 'class' && in_array($initialTrackValue, $classNames);
$initialGroupDisabled = !$initialClassSelected || empty($classGroupMap[$initialTrackValue]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form validation
    $error = null;
        $name = sanitize($_POST['name']); 
        $track_option_raw = $_POST['track_option'] ?? '';
        $track_option = trim($track_option_raw);
        $group_name = sanitize($_POST['group_name'] ?? '');
        $roll_no = sanitize($_POST['roll_no']); 
        $email = sanitize($_POST['email']); 
        $phone = sanitize($_POST['phone']); 
        $gender = sanitize($_POST['gender']); 
        $class_level = null;
        $department = null;
        
        if (empty($track_option)) {
            $error = "Please select your class or department.";
        } else {
            $parts = explode('|', $track_option, 2);
            if (count($parts) !== 2) {
                $error = "Invalid selection.";
            } else {
                $selectionType = trim($parts[0]);
                $selectionValue = sanitize($parts[1]);
                if ($selectionType === 'class') {
                    if (!in_array($selectionValue, $classNames)) {
                        $error = "Please select a valid class.";
                    } else {
                        $class_level = $selectionValue;
                        // Set department based on class level
                        if (strpos($selectionValue, 'Intermediate') !== false) {
                            $department = 'ICT'; // Assign intermediate students to ICT department
                        } else {
                            $department = 'ICT';
                        }
                        $allowedGroups = $classGroupMap[$class_level] ?? [];
                        if (!empty($allowedGroups)) {
                            if (empty($group_name)) {
                                $error = "Please select your group.";
                            } elseif (!in_array($group_name, $allowedGroups)) {
                                $error = "Please select a valid group for the chosen class.";
                            }
                        } else {
                            $group_name = null;
                        }
                    }
                } elseif ($selectionType === 'department') {
                    if (!in_array($selectionValue, $departmentOptions)) {
                        $error = "Please select a valid department.";
                    } else {
                        $department = $selectionValue;
                        $class_level = null;
                        $group_name = null;
                    }
                } else {
                    $error = "Invalid selection.";
                }
            }
        }
        
        if (empty($error)) {
            // Generate unique member ID 
        $member_id = generateMemberId(); 
        // Handle image upload with compression and size check 
        $maxFileSize = 10 * 1024 * 1024; // 10 MB in bytes 
        $uploadDir = 'uploads/members/'; 
        $image = null; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) { 
            if ($_FILES['image']['size'] <= $maxFileSize) { 
                $image = uploadMemberImage($_FILES['image'], $uploadDir); 
            } else { 
                $error = "Profile picture must be less than 10 MB."; 
            } 
        } 
        $idCardImage = null; 
        if (isset($_FILES['id_card_image']) && $_FILES['id_card_image']['error'] === 0) { 
            if ($_FILES['id_card_image']['size'] <= $maxFileSize) { 
                $idCardImage = uploadMemberImage($_FILES['id_card_image'], $uploadDir);
                if (!$idCardImage) {
                    $error = "Failed to upload ID card image. Please try again.";
                }
            } else { 
                $error = "ID card image must be less than 10 MB."; 
            } 
        } 
        if (!isset($error)) { 
            try {
                $stmt = $pdo->prepare("INSERT INTO members (member_id, name, class_level, group_name, department, roll_no, email, phone, gender, image, id_card_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)");
                $stmt->execute([$member_id, $name, $class_level, $group_name, $department, $roll_no, $email, $phone, $gender, $image, $idCardImage]); 
                $success = true; 
                $member_id_display = $member_id; 
            } catch (PDOException $e) { 
                $error = "An error occurred. Please try again."; 
            } 
        } 
    } 
}
?>

<style>
  /* Required field asterisk */
  .required-field::after {
    content: " *";
    color: red;
  }
  
  /* Progress bar styles */
  .upload-progress {
    margin-top: 15px;
    display: none;
  }
  .progress-container {
    margin-bottom: 10px;
  }
  .progress-bar {
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
  }
  .progress-fill {
    height: 100%;
    background-color: #4CAF50;
    width: 0%;
    transition: width 0.3s ease;
  }
  .progress-text {
    margin-top: 5px;
    font-size: 14px;
    color: #666;
  }
  
  /* Spinner styles */
  .spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 10px;
    vertical-align: middle;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  .btn-processing {
    pointer-events: none;
    opacity: 0.7;
  }
</style>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Application Submitted Successfully!</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                Your Member ID is: <span class="font-semibold text-xl text-indigo-600 dark:text-indigo-400"><?php echo isset($member_id_display) ? $member_id_display : ''; ?></span>
            </p>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Please take a screenshot or note down this ID for future reference.
                        </p>
                    </div>
                </div>
            </div>
            
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
                Go to the Science Club or contact any executive to get your membership approved. Thank you for joining BGC Science Club.
            </p>
            <div class="flex flex-col space-y-4">
                <?php 
                // Get WhatsApp number from settings
                $whatsappNumber = getWhatsAppNumber();
                $verificationMessage = rawurlencode("*Membership Verification Request*\n\n" . 
                    "Name: " . (isset($name) ? htmlspecialchars($name) : '') . "\n" .
                    "Class/Department: " . (isset($class_level) ? htmlspecialchars($class_level) : '') . 
                    (isset($department) ? ' (' . htmlspecialchars($department) . ')' : '') . "\n" .
                    "Roll No: " . (isset($roll_no) ? htmlspecialchars($roll_no) : '') . "\n" .
                    "Member ID: " . (isset($member_id_display) ? $member_id_display : '') . "\n\n" .
                    "Please verify my membership and add me to the BGC Science Club group.");
                
                $whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text={$verificationMessage}";
                ?>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-2">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 pt-0.5">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h2a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                To complete your membership, please verify your details with our team via WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
                
                <a href="<?php echo $whatsappUrl; ?>" 
                   target="_blank" 
                   rel="noopener" 
                   class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-md transition duration-200 shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.498 14.382v-.002c-.301-.15-1.767-.867-2.04-.966-.273-.101-.473-.15-.674.15-.197.295-.771.964-.944 1.162-.175.195-.349.21-.646.075-.3-.15-1.27-.466-2.415-1.486-1.932-1.74-2.171-3.021-2.421-3.09-.25-.085-.427-.14-.585-.136-.15.005-.35.03-.53.27-.226.3-.87 1.05-.87 2.56 0 1.5 1.09 2.97 1.245 3.18.15.209 2.1 3.195 5.085 4.5.714.3 1.27.48 1.71.615.75.21 1.425.18 1.965.11.57-.075 1.755-.715 2.001-1.41.245-.69.245-1.29.17-1.41-.05-.12-.195-.21-.42-.33m-7.845-12.18c-4.5 0-8.158 3.66-8.158 8.17 0 1.44.39 2.85 1.14 4.085L2.25 21.75l4.935-1.305c1.11.6 2.35.945 3.665.945 4.515 0 8.175-3.66 8.175-8.17 0-2.13-.83-4.13-2.34-5.64-1.5-1.5-3.51-2.33-5.64-2.33m0 1.5c3.81 0 6.915 3.105 6.915 6.915 0 3.81-3.105 6.93-6.93 6.93-1.29 0-2.535-.36-3.6-1.035l-.24-.135-3.21.855.87-3.15c-.6-1.065-.96-2.29-.96-3.51 0-3.81 3.105-6.915 6.915-6.915z"/>
                    </svg>
                    Verify & Join WhatsApp Group
                </a>
                
                <a href="index.php" class="mt-2 text-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for PDF generation -->
<form id="pdfForm" action="generate_pdf.php" method="post" target="_blank" style="display: none;">
    <input type="hidden" name="member_id" value="<?php echo isset($member_id_display) ? $member_id_display : ''; ?>">
    <input type="hidden" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
    <input type="hidden" name="class_level" value="<?php echo isset($class_level) ? htmlspecialchars($class_level) : ''; ?>">
    <input type="hidden" name="department" value="<?php echo isset($department) ? htmlspecialchars($department) : ''; ?>">
    <input type="hidden" name="roll_no" value="<?php echo isset($roll_no) ? htmlspecialchars($roll_no) : ''; ?>">
</form>

<script>
// Show success modal after form submission
<?php if (isset($success) && $success): ?>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
});
<?php endif; ?>

function downloadMemberCard() {
    window.open('member_card.php?member_id=' + encodeURIComponent('<?php echo isset($member_id_display) ? $member_id_display : ''; ?>'), '_blank');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('successModal');
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
}
</script>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-8" id="joinForm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="name">
                Full Name
            </label>
            <input type="text" id="name" name="name" required class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="track_option">
                Class / Department
            </label>
            <select id="track_option" name="track_option" required class="form-select w-full px-4 py-2 border rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="">Select Class or Department</option>
                <optgroup label="Intermediate Classes">
                    <?php foreach ($classOptions as $option) {
                        $value = 'class|' . $option['name'];
                        $selected = ($stickyTrackOption === $value) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($option['name']) . '</option>';
                    } ?>
                </optgroup>
                <optgroup label="Departments">
                    <?php foreach ($departmentOptions as $dept) {
                        $value = 'department|' . $dept;
                        $selected = ($stickyTrackOption === $value) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($dept) . '</option>';
                    } ?>
                </optgroup>
            </select>
        </div>
        <div id="groupFieldWrapper" class="<?php echo $initialGroupDisabled ? 'hidden' : ''; ?>">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="group_name">
                Group
            </label>
            <select id="group_name" name="group_name" class="form-select w-full px-4 py-2 border rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white" <?php echo $initialGroupDisabled ? 'disabled' : 'required'; ?> data-selected="<?php echo htmlspecialchars($stickyGroupName); ?>">
                <?php if ($initialGroupDisabled): ?>
                    <option value="">Group not required</option>
                <?php else: ?>
                    <option value="">Select group</option>
                    <?php foreach ($classGroupMap[$initialTrackValue] as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>" <?php echo ($stickyGroupName === $group) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="roll_no">
                Roll Number
            </label>
            <input type="text" id="roll_no" name="roll_no" required class="form-input w-full px-4 py-2 border rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="email">
                Email Address
            </label>
            <input type="email" id="email" name="email" required class="form-input w-full px-4 py-2 border rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="phone">
                Phone Number
            </label>
            <input type="tel" id="phone" name="phone" required class="form-input w-full px-4 py-2 border rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field">
                Gender
            </label>
            <div class="flex space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="gender" value="male" checked required class="form-radio text-primary-500">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Male</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="gender" value="female" required class="form-radio text-primary-500">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Female</span>
                </label>
            </div>
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="image">
                Profile Picture
            </label>
            <div class="relative border-2 border-dashed border-blue-400 rounded-lg p-4 text-center transition-colors duration-300 hover:border-blue-600 bg-blue-50 dark:bg-blue-900/20" style="min-height: 160px; display: flex; flex-direction: column; justify-content: center;">
                <div class="pointer-events-none">
                    <svg class="mx-auto h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Click to upload profile picture</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, JPEG (Max 10MB)</p>
                </div>
                <input type="file" id="image" name="image" accept="image/*" required 
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                       onchange="updateFileName(this, 'profileFileName')">
                <p id="profileFileName" class="mt-2 text-xs font-medium text-blue-600 dark:text-blue-400"></p>
            </div>
        </div>
        <div>
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 required-field" for="id_card_image">
                ID Card Image (Front Side)
            </label>
            <div class="relative border-2 border-dashed border-blue-400 rounded-lg p-4 text-center transition-colors duration-300 hover:border-blue-600 bg-blue-50 dark:bg-blue-900/20" style="min-height: 160px; display: flex; flex-direction: column; justify-content: center;">
                <div class="pointer-events-none">
                    <svg class="mx-auto h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Click to upload ID card (Front)</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Clear photo of your ID card (Max 10MB)</p>
                </div>
                <input type="file" id="id_card_image" name="id_card_image" accept="image/*" required 
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                       onchange="updateFileName(this, 'idCardFileName')">
                <p id="idCardFileName" class="mt-2 text-xs font-medium text-blue-600 dark:text-blue-400"></p>
            </div>
        </div>
    </div>
    
    <!-- Upload progress section -->
    <div class="upload-progress" id="uploadProgress">
        <h4 class="text-lg font-medium mb-3">Uploading Files</h4>
        <div class="progress-container" id="imageProgressContainer">
            <div>Profile Picture:</div>
            <div class="progress-bar">
                <div class="progress-fill" id="imageProgressBar"></div>
            </div>
            <div class="progress-text" id="imageProgressText">0%</div>
        </div>
        <div class="progress-container" id="idCardProgressContainer">
            <div>ID Card Image:</div>
            <div class="progress-bar">
                <div class="progress-fill" id="idCardProgressBar"></div>
            </div>
            <div class="progress-text" id="idCardProgressText">0%</div>
        </div>
    </div>
    
    <div class="mt-8">
    <button type="submit" id="submitBtn"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-full shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-300"
        style="box-shadow: 0 8px 20px rgba(37, 99, 235, 0.28);"
        aria-live="polite"
        aria-label="Submit membership application">
        <span id="submitText">Submit Application</span>
    </button>
</div>

</form>

<div class="mt-8 text-center text-gray-600 dark:text-gray-400">
    <p>Already a member? Contact the club administration for any queries.</p>
</div>

<script>
// Function to update file name display
function updateFileName(input, targetId) {
    const fileName = input.files[0]?.name || 'No file selected';
    document.getElementById(targetId).textContent = fileName;
    
    // Show preview for profile image
    if (input.id === 'image' && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            if (!preview) {
                const previewElement = document.createElement('div');
                previewElement.id = 'imagePreview';
                previewElement.className = 'mt-2';
                input.parentNode.appendChild(previewElement);
            }
            document.getElementById('imagePreview').innerHTML = 
                `<img src="${e.target.result}" class="h-24 w-24 rounded-full object-cover border-2 border-blue-200">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
    
    // Show preview for ID card image
    if (input.id === 'id_card_image' && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('idCardPreview');
            if (!preview) {
                const previewElement = document.createElement('div');
                previewElement.id = 'idCardPreview';
                previewElement.className = 'mt-2';
                input.parentNode.appendChild(previewElement);
            }
            document.getElementById('idCardPreview').innerHTML = 
                `<img src="${e.target.result}" class="h-32 w-auto rounded border-2 border-blue-200">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize class group map with proper structure
window.classGroupMap = <?php echo json_encode($classGroupMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

// Debug: Log the class group map to console
console.log('Class Group Map:', window.classGroupMap);

// Function to handle class selection changes
function handleClassSelection() {
    const trackSelect = document.getElementById('track_option');
    const groupField = document.getElementById('groupFieldWrapper');
    const groupSelect = document.getElementById('group_name');
    
    if (!trackSelect || !groupField || !groupSelect) {
        console.error('Required elements not found');
        return;
    }
    
    console.log('Track select value:', trackSelect.value);
    
    // Check if a class is selected (not a department)
    if (trackSelect.value.startsWith('class|')) {
        const className = trackSelect.value.split('|')[1];
        console.log('Selected class:', className);
        
        // Check if this is an intermediate class
        if (className.includes('Intermediate')) {
            // Get groups from our map
            const groups = window.classGroupMap[className] || [];
            console.log('Groups for class:', groups);
            
            // Clear existing options
            groupSelect.innerHTML = '<option value="">Select group</option>';
            
            // Add group options
            groups.forEach(group => {
                const option = document.createElement('option');
                option.value = group;
                option.textContent = group;
                groupSelect.appendChild(option);
            });
            
            // Show and enable the group select
            groupField.style.display = 'block';
            groupSelect.required = true;
            groupSelect.disabled = false;
            
            console.log('Showing groups for:', className);
            return;
        }
    }
    
    // Hide and disable for non-intermediate classes and departments
    groupField.style.display = 'none';
    groupSelect.required = false;
    groupSelect.disabled = true;
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    const trackSelect = document.getElementById('track_option');
    const groupField = document.getElementById('groupFieldWrapper');
    
    if (!trackSelect) {
        console.error('Track select element not found!');
        return;
    }
    
    if (!groupField) {
        console.error('Group field wrapper not found!');
        return;
    }
    
    // Set initial state
    handleClassSelection();
    
    // Add change event listener
    trackSelect.addEventListener('change', handleClassSelection);
    
    console.log('Initialization complete');
});
// Handle form submission with reCAPTCHA v3 and upload progress
document.getElementById('joinForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show processing state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.setAttribute('aria-busy', 'true');
    submitBtn.classList.add('btn-processing');
    // replace the visible text with a spinner + label
    document.getElementById('submitText').innerHTML = '<span class="spinner" aria-hidden="true"></span> Processing...';
    
    // Show upload progress
    document.getElementById('uploadProgress').style.display = 'block';
    
    // Simulate upload progress (replace with actual upload progress tracking)
    simulateUploadProgress('imageProgressBar', 'imageProgressText');
    simulateUploadProgress('idCardProgressBar', 'idCardProgressText');
    
    // Submit the form after a short delay to show the progress animation
    setTimeout(() => {
        e.target.submit();
    }, 700);
});

function simulateUploadProgress(barId, textId) {
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.floor(Math.random() * 10) + 5;
        if (progress >= 100) {
            progress = 100;
            clearInterval(progressInterval);
        }
        document.getElementById(barId).style.width = progress + '%';
        document.getElementById(textId).textContent = progress + '%';
    }, 200);
}
</script>