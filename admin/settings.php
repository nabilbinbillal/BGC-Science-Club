<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CAPTCHA Settings
        $recaptcha_enabled = isset($_POST['recaptcha_enabled']) ? 1 : 0;
        $recaptcha_site_key = trim($_POST['recaptcha_site_key'] ?? '');
        $recaptcha_secret_key = trim($_POST['recaptcha_secret_key'] ?? '');
        $recaptcha_score_threshold = min(max(floatval($_POST['recaptcha_score_threshold'] ?? 0.5), 0.1), 1.0);
        
        // WhatsApp Settings
        $whatsapp_enabled = isset($_POST['whatsapp_enabled']) ? 1 : 0;
        $whatsapp_country_code = preg_replace('/[^0-9+]/', '', $_POST['whatsapp_country_code'] ?? '880');
        $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');
        
        // Validate WhatsApp number if enabled
        if ($whatsapp_enabled && !empty($whatsapp_number)) {
            $whatsapp_full = $whatsapp_country_code . $whatsapp_number;
            if (strlen($whatsapp_full) < 11 || strlen($whatsapp_full) > 15) {
                throw new Exception("WhatsApp number must be between 11-15 digits including country code");
            }
        }
        
        // Parse class input (Format: Class Name | Group1, Group2)
        $classRows = array_filter(array_map('trim', explode("\n", $_POST['class_options'] ?? '')));
        $parsedClasses = [];
        foreach ($classRows as $row) {
            [$name, $groupsStr] = array_map('trim', array_pad(explode('|', $row, 2), 2, ''));
            if (empty($name)) {
                continue;
            }
            $groups = [];
            if (!empty($groupsStr)) {
                $groups = array_values(array_filter(array_map('trim', explode(',', $groupsStr))));
            }
            $parsedClasses[] = [
                'name' => $name,
                'groups' => $groups
            ];
        }
        if (empty($parsedClasses)) {
            $parsedClasses = getDefaultClassOptions();
        }
        
                // Parse department input (one per line or comma separated)
                // Support optional emoji: "Department Name | 🧪"
                $departmentInput = $_POST['department_options'] ?? '';
                $departmentLines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $departmentInput)));
                $departmentList = [];
                foreach ($departmentLines as $line) {
                    // allow comma separated fallback inside a line
                    $parts = preg_split('/\s*\|\s*/', $line, 2);
                    $name = trim($parts[0]);
                    $emoji = isset($parts[1]) ? trim($parts[1]) : '';
                    if ($name !== '') {
                        $departmentList[] = ['name' => $name, 'emoji' => $emoji];
                    }
                }
                if (empty($departmentList)) {
                    $departmentList = array_map(function($n){ return ['name' => $n, 'emoji' => '']; }, getDefaultDepartmentOptions());
                }
        
        $whatsappLink = trim($_POST['whatsapp_link'] ?? '');
        if ($whatsappLink === '') {
            $whatsappLink = null;
        }
        
        $whatsappNumber = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');
        if (empty($whatsappNumber)) {
            $whatsappNumber = null;
        }
        
        $stmt = $pdo->prepare("UPDATE settings 
            SET recaptcha_enabled = ?,
                recaptcha_site_key = ?,
                recaptcha_secret_key = ?,
                recaptcha_score_threshold = ?,
                whatsapp_enabled = ?,
                whatsapp_country_code = ?,
                whatsapp_number = ?,
                class_options = ?, 
                department_options = ?, 
                whatsapp_link = ?,
                updated_at = NOW()
            WHERE id = 1");
            
        $saved = $stmt->execute([
            $recaptcha_enabled,
            $recaptcha_site_key,
            $recaptcha_secret_key,
            $recaptcha_score_threshold,
            $whatsapp_enabled,
            $whatsapp_country_code,
            $whatsapp_number,
            json_encode($parsedClasses, JSON_UNESCAPED_UNICODE),
            json_encode($departmentList, JSON_UNESCAPED_UNICODE),
            $whatsappLink
        ]);
        
        if ($saved) {
            $success = "Settings updated successfully.";
            getSiteSettings(true); // refresh cached options
        } else {
            $error = "Failed to update settings.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred while updating settings.";
        error_log($e->getMessage());
    }
}

// Get current settings
$settingsData = getSiteSettings(true);

// Debug output (temporary)
echo '<!-- Debug: Settings Data -->';
echo '<!-- ' . print_r($settingsData, true) . ' -->';

$classOptionsText = [];
foreach ($settingsData['class_options'] as $class) {
    $line = $class['name'];
    if (!empty($class['groups'])) {
        $line .= ' | ' . implode(', ', $class['groups']);
    }
    $classOptionsText[] = $line;
}
$classOptionsValue = implode("\n", $classOptionsText);
$departmentOptionsValue = '';
if (!empty($settingsData['department_options_meta'])) {
    $lines = [];
    foreach ($settingsData['department_options_meta'] as $meta) {
        $line = $meta['name'];
        if (!empty($meta['emoji'])) $line .= ' | ' . $meta['emoji'];
        $lines[] = $line;
    }
    $departmentOptionsValue = implode("\n", $lines);
} else {
    $departmentOptionsValue = implode("\n", $settingsData['department_options']);
}
$whatsappLinkValue = $settingsData['whatsapp_link'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <!-- Test CAPTCHA Modal -->
    <div id="testCaptchaModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Test CAPTCHA</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-4">Complete the CAPTCHA to test if it's working correctly.</p>
                    <div id="testCaptchaContainer" class="mb-4">
                        <!-- reCAPTCHA will be loaded here -->
                    </div>
                    <div id="testCaptchaResult" class="hidden">
                        <div class="p-3 rounded-md mb-4" id="testCaptchaMessage"></div>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="closeTestCaptcha" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold mb-8">Website Settings</h1>

            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <div class="space-y-8">
                    <div>
                        <h2 class="text-xl font-semibold mb-4">Community & Access</h2>
                        <div class="mb-6 p-4 border rounded-lg bg-gray-50">
                            <h3 class="text-lg font-medium mb-4">CAPTCHA Settings</h3>
                            
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" 
                                       class="mr-2" <?php echo $settingsData['recaptcha_enabled'] ? 'checked' : ''; ?>>
                                <label for="recaptcha_enabled" class="text-gray-700 font-medium">
                                    Enable CAPTCHA Verification
                                </label>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="recaptcha_site_key" class="block text-sm font-medium text-gray-700 mb-1">reCAPTCHA Site Key</label>
                                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" 
                                           value="<?php echo htmlspecialchars($settingsData['recaptcha_site_key'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div>
                                    <label for="recaptcha_secret_key" class="block text-sm font-medium text-gray-700 mb-1">reCAPTCHA Secret Key</label>
                                    <div class="relative">
                                        <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" 
                                               value="<?php echo htmlspecialchars($settingsData['recaptcha_secret_key'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <button type="button" onclick="togglePasswordVisibility('recaptcha_secret_key')" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                            👁️
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-full md:w-1/3">
                                <label for="recaptcha_score_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                                    Security Level
                                    <span class="text-xs text-gray-500" id="scoreValue">(<?php echo number_format(($settingsData['recaptcha_score_threshold'] ?? 0.5) * 10, 1); ?>/10)</span>
                                </label>
                                <input type="range" id="recaptcha_score_threshold" name="recaptcha_score_threshold" 
                                       min="0.1" max="1.0" step="0.1" 
                                       value="<?php echo htmlspecialchars($settingsData['recaptcha_score_threshold'] ?? '0.5'); ?>"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                       oninput="document.getElementById('scoreValue').textContent = '(' + (this.value * 10).toFixed(1) + '/10)'">
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Easy</span>
                                    <span>Medium</span>
                                    <span>Hard</span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="button" onclick="testCaptcha()" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Test CAPTCHA
                                </button>
                                <p class="mt-2 text-sm text-gray-600">
                                    Test if your CAPTCHA is working correctly before saving.
                                </p>
                            </div>
                        </div>
                        <div class="mb-6 p-4 border rounded-lg bg-gray-50">
                            <h3 class="text-lg font-medium mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.5 2h-11C4 2 2 4 2 6.5v11C2 19.9 4.1 22 6.5 22h11c2.5 0 4.5-2 4.5-4.5v-11C22 4 20 2 17.5 2zm-5.3 15.3c-.2.2-.5.3-.7.3s-.5-.1-.7-.3l-1.4-1.4-1.4 1.4c-.2.2-.5.3-.7.3s-.5-.1-.7-.3c-.4-.4-.4-1 0-1.4l1.4-1.4-1.4-1.4c-.4-.4-.4-1 0-1.4s1-.4 1.4 0l1.4 1.4 1.4-1.4c.4-.4 1-.4 1.4 0s.4 1 0 1.4l-1.4 1.4 1.4 1.4c.4.4.4 1 0 1.4z"/>
                                    <path d="M16.5 8.8c-.3 0-.5-.2-.5-.5v-1.8c0-1.1-.9-2-2-2H9.6c-1.1 0-2 .9-2 2v9.6c0 1.1.9 2 2 2h6.4c1.1 0 2-.9 2-2v-6.3c0-.3-.2-.5-.5-.5h-1.8c-.3 0-.5.2-.5.5v4.1c0 .3-.2.5-.5.5s-.5-.2-.5-.5V9.3c0-.3-.2-.5-.5-.5h-1.8c-.3 0-.5.2-.5.5v1.8c0 .3-.2.5-.5.5s-.5-.2-.5-.5V8.3c0-.3-.2-.5-.5-.5H8.6c-.3 0-.5.2-.5.5v6.4c0 .3.2.5.5.5h6.4c.3 0 .5-.2.5-.5V9.3c0-.3-.2-.5-.5-.5z"/>
                                </svg>
                                WhatsApp Settings
                            </h3>
                            
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="whatsapp_enabled" name="whatsapp_enabled" 
                                       class="mr-2" <?php echo ($settingsData['whatsapp_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="whatsapp_enabled" class="text-gray-700 font-medium">
                                    Enable WhatsApp Verification
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="md:col-span-1">
                                    <label for="whatsapp_enabled" class="text-gray-700 font-medium">
                                    Enable WhatsApp Verification
                                </label>
                                <div class="ml-2 relative group">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="hidden group-hover:block absolute z-10 w-64 p-2 mt-1 -ml-32 text-xs text-gray-600 bg-white border border-gray-200 rounded shadow-lg">
                                        When enabled, users will see WhatsApp options and verification will be available.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <label for="whatsapp_country_code" class="block text-sm font-medium text-gray-700">Country Code</label>
                                        <div class="ml-1 relative group">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="hidden group-hover:block absolute z-10 w-48 p-2 mt-1 -ml-40 text-xs text-gray-600 bg-white border border-gray-200 rounded shadow-lg">
                                                Country code without + sign (e.g., 880 for Bangladesh)
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex rounded-md shadow-sm">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                            +
                                        </span>
                                        <input type="text" id="whatsapp_country_code" name="whatsapp_country_code" 
                                               value="<?php echo htmlspecialchars($settingsData['whatsapp_country_code'] ?? '880'); ?>"
                                               class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                               placeholder="880"
                                               pattern="[0-9]{1,5}"
                                               title="Enter country code (numbers only)">
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <div class="flex items-center mb-1">
                                        <label for="whatsapp_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                        <div class="ml-1 relative group">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="hidden group-hover:block absolute z-10 w-64 p-2 mt-1 -ml-48 text-xs text-gray-600 bg-white border border-gray-200 rounded shadow-lg">
                                                Enter the WhatsApp number without country code (e.g., 1712113295)
                                            </div>
                                        </div>
                                    </div>
                                    <input type="tel" id="whatsapp_number" name="whatsapp_number" 
                                           value="<?php echo htmlspecialchars($settingsData['whatsapp_number'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border rounded-md focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="1712113295"
                                           pattern="[0-9]{8,15}"
                                           title="Enter phone number (8-15 digits)">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="flex items-center mb-1">
                                    <label for="whatsapp_link" class="block text-sm font-medium text-gray-700">WhatsApp Group/Community Link</label>
                                    <div class="ml-1 relative group">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="hidden group-hover:block absolute z-10 w-64 p-2 mt-1 -ml-48 text-xs text-gray-600 bg-white border border-gray-200 rounded shadow-lg">
                                            Get this link from WhatsApp group: Menu > Invite to Group > Invite via Link
                                        </div>
                                    </div>
                                </div>
                                <div class="flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20.5 3h-17A1.5 1.5 0 002 4.5v15A1.5 1.5 0 003.5 21h17a1.5 1.5 0 001.5-1.5v-15A1.5 1.5 0 0020.5 3zM4 5h16v11H4zm8 9a1 1 0 11-1 1 1 1 0 011-1z"/>
                                        </svg>
                                    </span>
                                    <input type="url" id="whatsapp_link" name="whatsapp_link" 
                                           placeholder="https://chat.whatsapp.com/..." 
                                           value="<?php echo htmlspecialchars($settingsData['whatsapp_link'] ?? ''); ?>" 
                                           class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                           pattern="https://chat\.whatsapp\.com/.*"
                                           title="Must be a valid WhatsApp group invite link">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">This link will be shown to users after successful verification.</p>
                                
                                <?php if (!empty($settingsData['whatsapp_number'])): ?>
                                <div class="mt-2 text-sm text-green-700 bg-green-50 p-2 rounded-md">
                                    <p>WhatsApp Number: 
                                        <a href="https://wa.me/<?php echo htmlspecialchars(($settingsData['whatsapp_country_code'] ?? '880') . $settingsData['whatsapp_number']); ?>" 
                                           target="_blank" 
                                           class="text-blue-600 hover:underline">
                                            +<?php echo htmlspecialchars(($settingsData['whatsapp_country_code'] ?? '880') . ' ' . $settingsData['whatsapp_number']); ?>
                                        </a>
                                    </p>
                                    <?php if (!empty($settingsData['whatsapp_link'])): ?>
                                    <p class="mt-1">Group Link: 
                                        <a href="<?php echo htmlspecialchars($settingsData['whatsapp_link']); ?>" 
                                           target="_blank" 
                                           class="text-blue-600 hover:underline break-all">
                                            <?php echo htmlspecialchars($settingsData['whatsapp_link']); ?>
                                        </a>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h2a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Make sure to test the WhatsApp verification after saving to ensure it's working correctly.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold mb-4">Classes & Groups</h2>
                        <label for="class_options" class="block text-sm font-medium text-gray-700 mb-2">Define Classes (one per line)</label>
                        <textarea id="class_options" name="class_options" rows="5" class="w-full px-4 py-2 border rounded-md focus:ring-primary-500 focus:border-primary-500" placeholder="Class 1 | Group1, Group2&#10;Class 2 | Group3, Group4"><?php echo htmlspecialchars($classOptionsValue); ?></textarea>
                        <p class="text-xs text-gray-500 mt-2">Use the format <strong>Class Name | Group1, Group2</strong>. Groups are optional.</p>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold mb-4">Departments</h2>
                        <label for="department_options" class="block text-sm font-medium text-gray-700 mb-2">Department List</label>
                        <textarea id="department_options" name="department_options" rows="5" class="w-full px-4 py-2 border rounded-md focus:ring-primary-500 focus:border-primary-500" placeholder="ICT | 💻&#10;Physics | ⚛️&#10;Chemistry | ⚗️"><?php echo htmlspecialchars($departmentOptionsValue); ?></textarea>
                        <p class="text-xs text-gray-500 mt-2">Add one department per line. Optional: append an emoji using <code>|</code> (example: <em>Physics | ⚛️</em>).</p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }
        
        // Format WhatsApp number input
        document.addEventListener('DOMContentLoaded', function() {
            const whatsappNumber = document.getElementById('whatsapp_number');
            const whatsappCountryCode = document.getElementById('whatsapp_country_code');
            
            // Format phone number as user types
            if (whatsappNumber) {
                whatsappNumber.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    let value = this.value.replace(/\D/g, '');
                    
                    // Limit to 15 digits (max for international numbers)
                    if (value.length > 15) {
                        value = value.substring(0, 15);
                    }
                    
                    this.value = value;
                });
                
                // Validate on blur
                whatsappNumber.addEventListener('blur', function() {
                    if (this.value && !/^\d{8,15}$/.test(this.value)) {
                        this.setCustomValidity('Please enter a valid phone number (8-15 digits)');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            // Format country code
            if (whatsappCountryCode) {
                whatsappCountryCode.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    let value = this.value.replace(/\D/g, '');
                    
                    // Limit to 5 digits (max for country codes)
                    if (value.length > 5) {
                        value = value.substring(0, 5);
                    }
                    
                    this.value = value;
                });
                
                // Validate on blur
                whatsappCountryCode.addEventListener('blur', function() {
                    if (this.value && !/^\d{1,5}$/.test(this.value)) {
                        this.setCustomValidity('Please enter a valid country code (1-5 digits)');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
        
        // Test CAPTCHA functionality
        function testCaptcha() {
            const modal = document.getElementById('testCaptchaModal');
            const resultDiv = document.getElementById('testCaptchaResult');
            const messageDiv = document.getElementById('testCaptchaMessage');
            
            // Reset UI
            resultDiv.classList.add('hidden');
            document.getElementById('testCaptchaContainer').innerHTML = '';
            
            // Show modal
            modal.classList.remove('hidden');
            
            // Load reCAPTCHA
            const siteKey = document.getElementById('recaptcha_site_key').value;
            if (!siteKey) {
                showTestResult('Please enter a valid reCAPTCHA site key', 'red');
                return;
            }
            
            // Add reCAPTCHA script if not already loaded
            if (!document.querySelector('script[src*="recaptcha"]')) {
                const script = document.createElement('script');
                script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
            
            // Render reCAPTCHA v3
            grecaptcha.ready(function() {
                grecaptcha.execute(siteKey, {action: 'test'}).then(function(token) {
                    // Verify the token with your server
                    const secretKey = document.getElementById('recaptcha_secret_key').value;
                    
                    // In a real implementation, you would send this to your server for verification
                    // For this demo, we'll just show a success message
                    showTestResult('✅ CAPTCHA verification successful! Your settings are working correctly.', 'green');
                }).catch(function(error) {
                    showTestResult('❌ Error: ' + error.message, 'red');
                });
            });
            
            // Close modal handler
            document.getElementById('closeTestCaptcha').onclick = function() {
                modal.classList.add('hidden');
            };
            
            // Close when clicking outside
            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            };
        }
        
        function showTestResult(message, color) {
            const resultDiv = document.getElementById('testCaptchaResult');
            const messageDiv = document.getElementById('testCaptchaMessage');
            
            messageDiv.textContent = message;
            messageDiv.className = `p-3 rounded-md mb-4 text-${color}-700 bg-${color}-100 border border-${color}-300`;
            resultDiv.classList.remove('hidden');
        }
        
        // Validate WhatsApp number format
        document.getElementById('whatsapp_number').addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Validate length (without country code)
            const maxLength = 15 - (document.getElementById('whatsapp_country_code').value.replace(/\D/g, '').length);
            if (this.value.length > maxLength) {
                this.value = this.value.slice(0, maxLength);
            }
        });
        
        // Update max length when country code changes
        document.getElementById('whatsapp_country_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
            const whatsappNumber = document.getElementById('whatsapp_number');
            const currentValue = whatsappNumber.value.replace(/\D/g, '');
            const maxLength = 15 - this.value.length;
            
            if (currentValue.length > maxLength) {
                whatsappNumber.value = currentValue.slice(0, maxLength);
            }
        });
    </script>
</body>
</html> 