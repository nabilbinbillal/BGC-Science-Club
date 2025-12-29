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
            <a href="/activities" class="inline-block px-6 py-3 bg-primary-500 text-black rounded-md">Browse Activities</a>
        </div>
    </section>
    <?php
    return;
}

// Format the date
$formattedDate = date('F j, Y', strtotime($activity['date']));

// Get the image source
$imageSrc = !empty($activity['image']) ? '/uploads/activities/' . htmlspecialchars($activity['image']) : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000&auto=format&fit=crop';

// Get related activities
$stmt = $pdo->prepare("
    SELECT * FROM activities 
    WHERE id != ? 
    ORDER BY date DESC 
    LIMIT 3
");
$stmt->execute([$activity['id']]);
$relatedActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Date</h3>
                <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <svg class="h-5 w-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span><?php echo $formattedDate; ?></span>
                </div>
            </div>

            <?php if (!empty($activity['link'])): ?>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Related Links</h3>
                <a href="<?php echo htmlspecialchars($activity['link']); ?>" 
                   target="_blank" 
                   class="inline-flex items-center text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    Visit Activity Page
                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
            <?php endif; ?>

            <!-- More Activities -->
            <?php if (!empty($relatedActivities)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        More Activities
                    </h3>
                    <a href="/activities" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                        View All
                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($relatedActivities as $related): 
                        $relatedImage = !empty($related['image']) ? '/uploads/activities/' . htmlspecialchars($related['image']) : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000&auto=format&fit=crop';
                        $relatedDate = date('M j, Y', strtotime($related['date']));
                        $shortTitle = strlen($related['title']) > 60 ? substr($related['title'], 0, 57) . '...' : $related['title'];
                    ?>
                        <a href="/activity/<?php echo htmlspecialchars($related['slug']); ?>" class="block group">
                            <div class="flex items-start space-x-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-600">
                                <div class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <img src="<?php echo $relatedImage; ?>" 
                                         alt="<?php echo htmlspecialchars($related['title']); ?>"
                                         class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                        <?php echo htmlspecialchars($shortTitle); ?>
                                    </h4>
                                    <div class="flex items-center mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <?php echo $relatedDate; ?>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 transition-colors mt-1">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
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
<section class="bg-gradient-to-r from-primary-500 to-primary-600 py-16 shadow-lg">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-900 rounded-xl shadow-xl p-8 md:p-10 transform -translate-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-300 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Ready to join our next activity?</h2>
            <p class="text-lg text-gray-700 dark:text-gray-300 mb-8 max-w-2xl mx-auto">Be part of our growing community and participate in exciting activities that spark innovation and learning.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/join" class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-black font-semibold rounded-lg transition-all duration-200 transform hover:-translate-y-1 hover:shadow-lg">
    Join Now
    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
    </svg>
</a>
                <a href="/activities" class="inline-flex items-center justify-center px-8 py-4 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-white border border-gray-200 dark:border-gray-700 font-medium rounded-lg transition-all duration-200 transform hover:-translate-y-1 hover:shadow-lg">
                    View All Activities
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Share Activity -->
<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 mb-8">
    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92c0-1.61-1.31-2.92-2.92-2.92zM18 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM6 13c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm12 7.02c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
        </svg>
        Share This Activity
    </h3>
    
    <div class="flex flex-wrap gap-3 mb-4">
        <!-- WhatsApp -->
        <a href="https://wa.me/?text=<?php echo urlencode(htmlspecialchars($activity['title']) . ' - ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all transform hover:-translate-y-0.5 hover:shadow-md">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.498 14.382l-1.998-1.953c-.29-.284-.773-.517-1.182-.517s-.803.15-1.124.5l-.5.5c-.22.226-.5.336-.812.336-.312 0-.59-.11-.81-.336l-.5-.5c-.32-.35-.81-.5-1.19-.5s-.89.15-1.21.5l-1.5 1.5c-.5.5-.77 1.77-.89 2.17s-.12 1.07 1.16 1.07c1.17 0 3.39-.15 5.16-1.43 1.96-1.41 2.91-3.6 2.96-3.7.04-.16-.06-.28-.11-.33zM12 20.5c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/>
            </svg>
            <span class="font-medium">WhatsApp</span>
        </a>

        <!-- Facebook -->
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all transform hover:-translate-y-0.5 hover:shadow-md">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
            </svg>
            <span class="font-medium">Facebook</span>
        </a>

        <!-- Twitter -->
        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&text=<?php echo urlencode(htmlspecialchars($activity['title'])); ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-4 py-3 bg-sky-500 hover:bg-sky-600 text-black rounded-lg transition-all transform hover:-translate-y-0.5 hover:shadow-md">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
            </svg>
            <span class="font-medium">Twitter</span>
        </a>
    </div>

    <!-- Email & Copy Link -->
    <div class="flex flex-col sm:flex-row gap-3">
        <!-- Email -->
        <a href="mailto:?subject=<?php echo urlencode('Check out: ' . htmlspecialchars($activity['title'])); ?>&body=<?php echo urlencode(htmlspecialchars($activity['title']) . '%0A%0A' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]%0A%0A"); ?>" 
           class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg transition-all transform hover:-translate-y-0.5 hover:shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="font-medium">Email</span>
        </a>

        <!-- Copy Link -->
        <div class="flex-1 relative">
            <input type="text" 
                   id="share-url" 
                   value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" 
                   class="w-full px-4 py-3 pr-20 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                   readonly>
            <button onclick="copyToClipboard()" 
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Copy
            </button>
        </div>
    </div>
    
    <div id="copy-message" class="mt-2 text-center text-sm text-green-600 dark:text-green-400 opacity-0 transition-opacity duration-200">Link copied to clipboard!</div>
</div>

<script>
function copyToClipboard() {
    const copyText = document.getElementById("share-url");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    const message = document.getElementById("copy-message");
    message.classList.remove("opacity-0");
    message.classList.add("opacity-100");
    setTimeout(() => {
        message.classList.remove("opacity-100");
        message.classList.add("opacity-0");
    }, 2000);
}
</script>

<!-- Related Activities Section -->
<?php
// Get related activities (excluding current one)
$relatedStmt = $pdo->prepare("SELECT id, title, slug, image, date FROM activities WHERE id != ? ORDER BY date DESC LIMIT 3");
$relatedStmt->execute([$activity['id']]);
$relatedActivities = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($relatedActivities)): 
?>
<section class="py-12 bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">More Activities</h2>
            <a href="/activities" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                View All Activities
                <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($relatedActivities as $related): 
                $relatedImage = !empty($related['image']) ? '/uploads/activities/' . htmlspecialchars($related['image']) : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000&auto=format&fit=crop';
                $relatedDate = date('M j, Y', strtotime($related['date']));
                $shortTitle = strlen($related['title']) > 50 ? substr($related['title'], 0, 47) . '...' : $related['title'];
            ?>
            <a href="/activity/<?php echo !empty($related['slug']) ? htmlspecialchars($related['slug']) : $related['id']; ?>" 
               class="group block bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg overflow-hidden transition-shadow duration-300">
                <div class="h-48 overflow-hidden">
                    <img class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" 
                         src="<?php echo $relatedImage; ?>" 
                         alt="<?php echo htmlspecialchars($related['title']); ?>">
                </div>
                <div class="p-5">
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?php echo $relatedDate; ?>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-500 transition-colors">
                        <?php echo htmlspecialchars($shortTitle); ?>
                    </h3>
                    <div class="mt-3 flex items-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                        Read more
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
