<?php
// Get all activities from database
$stmt = $pdo->query("SELECT * FROM activities ORDER BY date DESC");
$activities = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
<div class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Club Activities</h1>
        <nav class="text-gray-500 text-sm">
            <a href="/" class="hover:text-purple-600">Home</a>
            <span class="mx-2">/</span>
            <span>Club Activities</span>
        </nav>
    </div>
</div><?php if (empty($activities)): ?>
<div class="text-center py-8">
    <p class="text-gray-600 dark:text-gray-400">No activities found.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($activities as $activity): ?>
    <?php
    // Build an internal URL for the activity detail page (fallback to external link if set and desired)
    $activitySlug = slugifyText($activity['title']);
    $internalUrl = '/activity/' . $activity['id'] . '-' . $activitySlug;
    $useExternal = false; // default: use internal detail pages
    $href = $internalUrl;
    ?>
    <a href="<?php echo htmlspecialchars($href); ?>" class="block">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden activity-card hover:shadow-lg transition-shadow duration-300">
            <?php if (isset($activity['image']) && !empty($activity['image'])): ?>
            <div class="relative h-48 overflow-hidden">
                <img src="uploads/activities/<?php echo htmlspecialchars($activity['image']); ?>" 
                     alt="<?php echo htmlspecialchars($activity['title']); ?>"
                     class="w-full h-full object-cover transition-transform duration-300">
            </div>
            <?php endif; ?>

            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    <?php echo htmlspecialchars($activity['title']); ?>
                </h3>

                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <time datetime="<?php echo $activity['date']; ?>">
                        <?php echo date('F j, Y', strtotime($activity['date'])); ?>
                    </time>
                </div>

                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    <?php echo nl2br(htmlspecialchars(substr($activity['description'], 0, 260))); ?>
                </p>

                <span class="inline-flex items-center text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    Read more
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
