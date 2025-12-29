<?php
// Get member slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /');
    exit;
}

// Get member data
$stmt = $pdo->prepare("SELECT * FROM members WHERE slug = ? AND status = 'approved'");
$stmt->execute([$slug]);
$member = $stmt->fetch();

if (!$member) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get member's projects
$stmt = $pdo->prepare("SELECT p.* FROM projects p 
                      INNER JOIN project_members pm ON p.id = pm.project_id 
                      WHERE pm.member_id = ? AND p.status = 'published' 
                      ORDER BY p.created_at DESC");
$stmt->execute([$member['id']]);
$projects = $stmt->fetchAll();

// Set page variables
$page = 'member';
$title = $member['name'] . ' - ' . $member['department'] . ' - ' . $member['class_name'];
$description = "View profile of " . $member['name'] . " - " . $member['department'] . " student at BGC Trust University";
$canonicalUrl = "https://" . $_SERVER['HTTP_HOST'] . "/member/" . $member['slug'];

// Generate schema markup
$schemaMarkup = generateSchemaMarkup('person', [
    'name' => $member['name'],
    'description' => $member['bio'] ?? $description,
    'memberOf' => [
        'name' => $member['department'],
        'department' => [
            'name' => $member['class_name']
        ]
    ],
    'url' => $canonicalUrl,
    'image' => !empty($member['profile_pic']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/members/' . $member['profile_pic'] : ''
]);
?>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="flex flex-col md:flex-row gap-8">
                <div class="md:w-1/3">
                    <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                        <?php if (!empty($member['profile_pic'])): ?>
                            <img 
                                src="/uploads/members/<?= htmlspecialchars($member['profile_pic']) ?>" 
                                alt="<?= htmlspecialchars($member['name']) ?>"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="md:w-2/3">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        <?= htmlspecialchars($member['name']) ?>
                    </h1>
                    
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-300 mb-6">
                        <?php if (!empty($member['department'])): ?>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <?= htmlspecialchars($member['department']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($member['class_name'])): ?>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <?= htmlspecialchars($member['class_name']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($member['roll_no'])): ?>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <?= htmlspecialchars($member['roll_no']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($member['bio'])): ?>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-700 dark:text-gray-300">
                                <?= nl2br(htmlspecialchars($member['bio'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Projects Section -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Projects
                <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                    (<?= count($projects) ?>)
                </span>
            </h2>
            
            <?php if (!empty($projects)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($projects as $project): ?>
                        <a href="/project/<?= htmlspecialchars($project['slug']) ?>" class="block group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow duration-300 h-full border border-gray-200 dark:border-gray-700">
                                <?php if (!empty($project['featured_image'])): ?>
                                    <div class="h-40 overflow-hidden">
                                        <img 
                                            src="/uploads/projects/<?= htmlspecialchars($project['featured_image']) ?>" 
                                            alt="<?= htmlspecialchars($project['title']) ?>"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                        >
                                    </div>
                                <?php endif; ?>
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        <?= htmlspecialchars($project['title']) ?>
                                    </h3>
                                    <?php if (!empty($project['excerpt'])): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                                            <?= htmlspecialchars($project['excerpt']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No projects found for this member.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Add JSON-LD schema for the member
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $member['name'],
    'url' => $canonicalUrl,
    'memberOf' => [
        '@type' => 'Organization',
        'name' => $member['department']
    ]
];

if (!empty($member['bio'])) {
    $jsonLd['description'] = $member['bio'];
}

if (!empty($member['profile_pic'])) {
    $jsonLd['image'] = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/members/' . $member['profile_pic'];
}

$schemaMarkup = json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>

<script type="application/ld+json">
<?= $schemaMarkup ?>
</script>
<?php
// End of file
?>
