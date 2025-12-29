<?php
// NOTE: This file is now an admin panel fragment included by admin/index.php.
// admin/index.php already starts the session and includes db/functions, and
// handles authentication, so we should not re-include header/footer or re-run
// session checks here.

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
        $whatsapp_link = trim($_POST['whatsapp_link'] ?? '');
        
        // Validate WhatsApp number if enabled
        if ($whatsapp_enabled && !empty($whatsapp_number)) {
            $whatsapp_full = $whatsapp_country_code . $whatsapp_number;
            if (strlen($whatsapp_full) < 11 || strlen($whatsapp_full) > 15) {
                throw new Exception("WhatsApp number must be between 11-15 digits including country code");
            }
        }
        
        $stmt = $pdo->prepare("UPDATE settings 
            SET recaptcha_enabled = ?,
                recaptcha_site_key = ?,
                recaptcha_secret_key = ?,
                recaptcha_score_threshold = ?,
                whatsapp_enabled = ?,
                whatsapp_country_code = ?,
                whatsapp_number = ?,
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
            $whatsapp_link
        ]);
        
        if ($saved) {
            $success = "Settings updated successfully.";
            // Refresh settings
            $settingsData = getSiteSettings(true);
        } else {
            $error = "Failed to update settings.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred while updating settings.";
        error_log($e->getMessage());
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current settings
$settingsData = getSiteSettings();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold">System Variables</h1>
        </div>

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

        <form method="POST" class="space-y-6">
            <!-- CAPTCHA Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">CAPTCHA Settings</h2>
                
                <div class="flex items-center mb-4">
                    <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                           <?php echo ($settingsData['recaptcha_enabled'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="recaptcha_enabled" class="ml-2 block text-sm text-gray-700">
                        Enable reCAPTCHA
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="recaptcha_site_key" class="block text-sm font-medium text-gray-700 mb-1">Site Key</label>
                        <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" 
                               value="<?php echo htmlspecialchars($settingsData['recaptcha_site_key'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="recaptcha_secret_key" class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                        <div class="relative">
                            <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" 
                                   value="<?php echo htmlspecialchars($settingsData['recaptcha_secret_key'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('recaptcha_secret_key')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                👁️
                            </button>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-1/2">
                    <label for="recaptcha_score_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                        Security Level
                        <span class="text-xs text-gray-500" id="scoreValue">(<?php echo number_format(($settingsData['recaptcha_score_threshold'] ?? 0.5) * 10, 1); ?>/10)</span>
                    </label>
                    <input type="range" id="recaptcha_score_threshold" name="recaptcha_score_threshold" 
                           min="0.1" max="1.0" step="0.1" 
                           value="<?php echo htmlspecialchars($settingsData['recaptcha_score_threshold'] ?? '0.5'); ?>"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                           oninput="document.getElementById('scoreValue').textContent = '(' + (this.value * 10).toFixed(1) + '/10)'">
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Easy</span>
                        <span>Medium</span>
                        <span>Hard</span>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.5 2h-11C4 2 2 4 2 6.5v11C2 19.9 4.1 22 6.5 22h11c2.5 0 4.5-2 4.5-4.5v-11C22 4 20 2 17.5 2zm-5.3 15.3c-.2.2-.5.3-.7.3s-.5-.1-.7-.3l-1.4-1.4-1.4 1.4c-.2.2-.5.3-.7.3s-.5-.1-.7-.3c-.4-.4-.4-1 0-1.4l1.4-1.4-1.4-1.4c-.4-.4-.4-1 0-1.4s1-.4 1.4 0l1.4 1.4 1.4-1.4c.4-.4 1-.4 1.4 0s.4 1 0 1.4l-1.4 1.4 1.4 1.4c.4.4.4 1 0 1.4z"/>
                        <path d="M16.5 8.8c-.3 0-.5-.2-.5-.5v-1.8c0-1.1-.9-2-2-2H9.6c-1.1 0-2 .9-2 2v9.6c0 1.1.9 2 2 2h6.4c1.1 0 2-.9 2-2v-6.3c0-.3-.2-.5-.5-.5h-1.8c-.3 0-.5.2-.5.5v4.1c0 .3-.2.5-.5.5s-.5-.2-.5-.5V9.3c0-.3-.2-.5-.5-.5h-1.8c-.3 0-.5.2-.5.5v1.8c0 .3-.2.5-.5.5s-.5-.2-.5-.5V8.3c0-.3-.2-.5-.5-.5H8.6c-.3 0-.5.2-.5.5v6.4c0 .3.2.5.5.5h6.4c.3 0 .5-.2.5-.5V9.3c0-.3-.2-.5-.5-.5z"/>
                    </svg>
                    WhatsApp Settings
                </h2>
                
                <div class="flex items-center mb-4">
                    <input type="checkbox" id="whatsapp_enabled" name="whatsapp_enabled" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                           <?php echo ($settingsData['whatsapp_enabled'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="whatsapp_enabled" class="ml-2 block text-sm text-gray-700">
                        Enable WhatsApp Integration
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="whatsapp_country_code" class="block text-sm font-medium text-gray-700 mb-1">Country Code</label>
                        <div class="flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                +
                            </span>
                            <input type="text" id="whatsapp_country_code" name="whatsapp_country_code" 
                                   value="<?php echo htmlspecialchars($settingsData['whatsapp_country_code'] ?? '880'); ?>"
                                   class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="880"
                                   pattern="[0-9]{1,5}"
                                   title="Enter country code (numbers only)">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" id="whatsapp_number" name="whatsapp_number" 
                               value="<?php echo htmlspecialchars($settingsData['whatsapp_number'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="1712113295"
                               pattern="[0-9]{8,15}"
                               title="Enter phone number (8-15 digits)">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="whatsapp_link" class="block text-sm font-medium text-gray-700 mb-1">
                        WhatsApp Group/Community Link
                        <span class="text-xs text-gray-500">(optional)</span>
                    </label>
                    <input type="url" id="whatsapp_link" name="whatsapp_link" 
                           placeholder="https://chat.whatsapp.com/..." 
                           value="<?php echo htmlspecialchars($settingsData['whatsapp_link'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"
                           pattern="https://chat\.whatsapp\.com/.*"
                           title="Must be a valid WhatsApp group invite link">
                    <p class="text-xs text-gray-500 mt-1">
                        Get this link from WhatsApp group: Menu > Invite to Group > Invite via Link
                    </p>
                </div>

                <?php if (!empty($settingsData['whatsapp_number'])): ?>
                <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-md">
                    <p class="text-sm text-blue-800">
                        <span class="font-medium">Test WhatsApp:</span> 
                        <a href="https://wa.me/<?php echo htmlspecialchars(($settingsData['whatsapp_country_code'] ?? '880') . $settingsData['whatsapp_number']); ?>" 
                           target="_blank" 
                           class="text-blue-600 hover:underline">
                            +<?php echo htmlspecialchars(($settingsData['whatsapp_country_code'] ?? '880') . ' ' . $settingsData['whatsapp_number']); ?>
                        </a>
                    </p>
                    <?php if (!empty($settingsData['whatsapp_link'])): ?>
                    <p class="text-sm text-blue-800 mt-1">
                        <span class="font-medium">Group Link:</span> 
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

            <div class="flex justify-end">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

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
    }
});
</script>

<!-- page fragment included by admin/index.php -->
