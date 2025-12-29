<?php
$page_title = "Submit a Ticket - BGC Science Club";
$page = 'ticket';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Get reCAPTCHA status
$stmt = $pdo->query("SELECT recaptcha_enabled FROM settings LIMIT 1");
$settings = $stmt->fetch();
$recaptcha_enabled = $settings['recaptcha_enabled'] ?? 1;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_name = sanitize($_POST['member_name'] ?? '');
    $member_email = sanitize($_POST['member_email'] ?? '');
    $member_id = sanitize($_POST['member_id'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    // Validate reCAPTCHA if enabled
    if ($recaptcha_enabled) {
        $recaptcha_secret = "YOUR_RECAPTCHA_SECRET_KEY"; // Replace with your secret key
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        
        $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
        $response_data = json_decode($verify_response);
        
        if (!$response_data->success) {
            $error = "Please complete the reCAPTCHA verification.";
        }
    }

    if (empty($error)) {
        // Verify member exists with matching email and ID
        $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ? AND email = ?");
        $stmt->execute([$member_id, $member_email]);
        $member = $stmt->fetch();

        if (!$member) {
            $error = "Invalid member ID or email. Please check your credentials.";
        } else {
            try {
                // Create ticket
                $stmt = $pdo->prepare("INSERT INTO tickets (member_id, subject, message) VALUES (?, ?, ?)");
                $stmt->execute([$member_id, $subject, $message]);
                
                $success = "Your ticket has been submitted successfully. We will respond to your inquiry soon.";
                
                // Clear form data
                $member_name = $member_email = $member_id = $subject = $message = '';
            } catch (PDOException $e) {
                $error = "An error occurred while submitting your ticket. Please try again.";
                error_log($e->getMessage());
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Contact Support</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Need help? Submit a ticket and we'll get back to you as soon as possible.
                </p>
            </div>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="ticketForm" class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">
                <div class="space-y-6">
                    <div>
                        <label for="member_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Member Name *
                        </label>
                        <input type="text" id="member_name" name="member_name" required
                            value="<?php echo htmlspecialchars($member_name ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="member_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Member Email *
                        </label>
                        <input type="email" id="member_email" name="member_email" required
                            value="<?php echo htmlspecialchars($member_email ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Member ID *
                        </label>
                        <input type="text" id="member_id" name="member_id" required
                            value="<?php echo htmlspecialchars($member_id ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Subject *
                        </label>
                        <input type="text" id="subject" name="subject" required
                            value="<?php echo htmlspecialchars($subject ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Message *
                        </label>
                        <textarea id="message" name="message" rows="6" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>

                    <?php if ($recaptcha_enabled): ?>
                    <div class="mb-6">
                        <div class="g-recaptcha" data-sitekey="YOUR_RECAPTCHA_SITE_KEY"></div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <button type="submit"
                            class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                            Submit Ticket
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-8 text-center text-gray-600 dark:text-gray-400">
                <p>For urgent matters, please contact us directly at:</p>
                <p class="font-medium text-primary-600 dark:text-primary-400">support@bgcscienceclub.com</p>
            </div>
        </div>
    </div>
</div>

<?php if ($recaptcha_enabled): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
// Handle form submission with reCAPTCHA v3
document.getElementById('ticketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo $recaptcha_site_key; ?>', {action: 'ticket_submit'})
        .then(function(token) {
            document.getElementById('recaptchaResponse').value = token;
            e.target.submit();
        });
    });
});
</script>
<?php else: ?>
<script>
document.getElementById('ticketForm').addEventListener('submit', function(e) {
    // Form submits normally
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 