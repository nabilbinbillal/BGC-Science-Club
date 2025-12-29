<?php
$page_title = "500 - Server Error";
$page = '500';
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col justify-center items-center px-6 py-12">
    <div class="text-center">
        <h1 class="text-9xl font-bold text-red-600 mb-4">500</h1>
        <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-4">Server Error</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md">Oops! Something went wrong on our end. We're working to fix it.</p>
        
        <div class="space-y-4">
            <a href="/" class="inline-block bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                Return Home
            </a>
            <div class="text-gray-500 dark:text-gray-400">or</div>
            <a href="/ticket" class="inline-block text-primary-600 hover:text-primary-700 font-medium">
                Report an Issue
            </a>
        </div>
    </div>
    
    <div class="mt-12 text-center">
        <p class="text-gray-500 dark:text-gray-400">
            Our team has been notified. Please try again later.
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
