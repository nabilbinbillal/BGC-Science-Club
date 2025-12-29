<?php
$page_title = "Club Logo - BGC Science Club";
require_once __DIR__ . '/../includes/header.php';

// Define logo dimensions and their descriptions
$logoDimensions = [
    'original' => [
        'width' => 'Original',
        'height' => 'Original',
        'description' => 'High resolution original logo',
        'file' => 'logo.png'
    ],
    'large' => [
        'width' => '1200px',
        'height' => '1200px',
        'description' => 'Large size for banners and posters',
        'file' => 'logo-1200x1200.png'
    ],
    'medium' => [
        'width' => '600px',
        'height' => '600px',
        'description' => 'Medium size for website headers',
        'file' => 'logo-600x600.png'
    ],
    'small' => [
        'width' => '300px',
        'height' => '300px',
        'description' => 'Small size for social media profiles',
        'file' => 'logo-300x300.png'
    ],
    'favicon' => [
        'width' => '32px',
        'height' => '32px',
        'description' => 'Favicon size for browser tabs',
        'file' => 'logo-32x32.png'
    ]
];
?>

<!-- Page Header -->
<div class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Club Logo</h1>
        <nav class="text-gray-500 text-sm">
            <a href="/" class="hover:text-purple-600">Home</a>
            <span class="mx-2">/</span>
            <span>Logo</span>
        </nav>
    </div>
</div>

<!-- Logo Information -->
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">About Our Logo</h2>
        <p class="text-gray-600 mb-4">
            The BGC Science Club logo was designed by <a href="https://nabilbinbillal.github.io/" target="_blank" class="text-primary-600 hover:text-primary-700">Nabil Bin Billal</a>. 
            It represents our commitment to scientific excellence and innovation.
        </p>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        When using our logo, please ensure proper attribution is given to the designer and the BGC Science Club.
                        The logo should not be modified or used in a way that could be misleading or damaging to our brand.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($logoDimensions as $key => $dimension): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <?php echo $dimension['width']; ?> × <?php echo $dimension['height']; ?>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo $dimension['description']; ?></p>
                    
                    <div class="aspect-w-1 aspect-h-1 mb-4">
                        <img 
                            src="/uploads/logo/<?php echo $dimension['file']; ?>" 
                            alt="BGC Science Club Logo - <?php echo $dimension['width']; ?> × <?php echo $dimension['height']; ?>"
                            class="object-contain w-full h-full"
                            onerror="this.src='/assets/images/default-avatar.jpg'"
                        >
                    </div>
                    
                    <div class="flex justify-center">
                        <a href="/uploads/logo/<?php echo $dimension['file']; ?>" 
                           download="<?php echo $dimension['file']; ?>"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Logo
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Download All Section -->
    <div class="mt-8 text-center">
        <a href="https://cdn.bidibo.xyz/uploads/logo/BGCSC-logos.zip" 
           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download All Logos (ZIP)
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 