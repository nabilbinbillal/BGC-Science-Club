<?php
// Get the activity slug from the URL
$slug = $_GET['slug'] ?? '';

// First, try to find the activity by slug
$stmt = $pdo->prepare("SELECT * FROM activities WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

// If not found by slug, try by ID (for backward compatibility)
if (!$activity && is_numeric($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ? LIMIT 1");
    $stmt->execute([$slug]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$activity) {
    http_response_code(404);
    ?>
    <section class="py-20 bg-gray-100">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Activity not found</h1>
            <p class="text-gray-600 mb-6">The activity you are looking for may have been moved or removed.</p>
            <a href="/activities" class="inline-block px-6 py-3 bg-primary-500 text-white rounded-md">Browse Activities</a>
        </div>
    </section>
    <?php
    return;
}

// Format the date
$formattedDate = date('F j, Y', strtotime($activity['date']));

// Get the image source
$imageSrc = !empty($activity['image']) ? '/uploads/activities/' . htmlspecialchars($activity['image']) : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000&auto=format&fit=crop';
?>

<!-- Hero Section -->
<section class="bg-gray-900 text-white py-16">
    <div class="container mx-auto px-4 flex flex-col lg:flex-row items-center gap-8">
        <div class="lg:w-1/2">
            <p class="text-sm uppercase tracking-widest text-primary-300 mb-2"><?php echo $formattedDate; ?></p>
            <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($activity['title']); ?></h1>
            <p class="text-lg text-gray-200 mb-6"><?php echo htmlspecialchars($activity['short_description'] ?? ''); ?></p>
            <?php if (!empty($activity['link'])): ?>
                <a href="<?php echo htmlspecialchars($activity['link']); ?>" target="_blank" rel="noopener" class="inline-flex items-center px-5 py-3 bg-primary-500 hover:bg-primary-600 rounded-md font-semibold">
                    View More Details
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
        <div class="lg:w-1/2">
            <img src="<?php echo $imageSrc; ?>" 
                 alt="<?php echo htmlspecialchars($activity['title']); ?>" 
                 class="rounded-3xl shadow-2xl w-full h-80 object-cover">
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section class="py-16 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Main Content -->
        <article class="lg:col-span-2 bg-gray-50 dark:bg-gray-800 rounded-3xl p-8 shadow">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Activity Details</h2>
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
            </div>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-8">
            <!-- Date & Time -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">When</h3>
                <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <svg class="h-5 w-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span><?php echo $formattedDate; ?></span>
                </div>
            </div>

            <!-- Share -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Share</h3>
                <div class="flex space-x-4">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                       target="_blank" 
                       class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                        <span class="sr-only">Share on Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&text=<?php echo urlencode(htmlspecialchars($activity['title'])); ?>" 
                       target="_blank" 
                       class="text-gray-500 hover:text-blue-400 dark:text-gray-400 dark:hover:text-blue-400">
                        <span class="sr-only">Share on Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode(htmlspecialchars($activity['title'])); ?>&body=<?php echo urlencode('Check out this activity: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                       class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <span class="sr-only">Share via Email</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Related Activities -->
            <?php
            // Get related activities (excluding current one)
            $relatedStmt = $pdo->prepare("SELECT id, title, slug, image, date FROM activities WHERE id != ? ORDER BY date DESC LIMIT 3");
            $relatedStmt->execute([$activity['id']]);
            $relatedActivities = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($relatedActivities)):
            ?>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">More Activities</h3>
                <div class="space-y-4">
                    <?php foreach ($relatedActivities as $related): 
                        $relatedImage = !empty($related['image']) ? '/uploads/activities/' . htmlspecialchars($related['image']) : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000&auto=format&fit=crop';
                        $relatedDate = date('M j, Y', strtotime($related['date']));
                    ?>
                    <a href="/activity/<?php echo !empty($related['slug']) ? htmlspecialchars($related['slug']) : $related['id']; ?>" 
                       class="group flex items-center space-x-3 hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded-lg transition-colors">
                        <div class="flex-shrink-0">
                            <img class="h-12 w-12 rounded-lg object-cover" 
                                 src="<?php echo $relatedImage; ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-primary-500">
                                <?php echo htmlspecialchars($related['title']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo $relatedDate; ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-primary-50 dark:bg-gray-800 py-12">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Want to join our next activity?</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6 max-w-2xl mx-auto">Be part of our community and stay updated with our latest activities and events.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="/join" class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-md font-medium transition-colors">
                Join Us Now
            </a>
            <a href="/activities" class="px-6 py-3 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-md font-medium transition-colors">
                View All Activities
            </a>
        </div>
    </div>
</section>
