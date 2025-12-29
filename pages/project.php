<?php
$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$project = normalizeProjectRecord($stmt->fetch());

if (!$project) {
    http_response_code(404);
    ?>
    <section class="py-20 bg-gray-100">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Project not found</h1>
            <p class="text-gray-600 mb-6">The project you are looking for may have been moved or removed.</p>
            <a href="/projects" class="inline-block px-6 py-3 bg-primary-500 text-white rounded-md">Browse Projects</a>
        </div>
    </section>
    <?php
    return;
}

$contributors = [];
if (!empty($project['contributor_ids'])) {
    $placeholders = implode(',', array_fill(0, count($project['contributor_ids']), '?'));
    $memberStmt = $pdo->prepare("SELECT member_id, name, department, class_level FROM members WHERE member_id IN ($placeholders)");
    $memberStmt->execute($project['contributor_ids']);
    $memberRows = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
    $memberMap = [];
    foreach ($memberRows as $row) {
        $memberMap[$row['member_id']] = $row;
    }
    foreach ($project['contributor_ids'] as $memberId) {
        if (isset($memberMap[$memberId])) {
            $contributors[] = $memberMap[$memberId];
        }
    }
}

$imageSrc = !empty($project['image']) ? '/uploads/projects/' . htmlspecialchars($project['image']) : 'https://images.pexels.com/photos/50711/writing-notes-idea-conference-50711.jpeg';
?>

<section class="bg-gray-900 text-white py-16">
    <div class="container mx-auto px-4 flex flex-col lg:flex-row items-center gap-8">
        <div class="lg:w-1/2">
            <p class="text-sm uppercase tracking-widest text-primary-300 mb-2"><?php echo formatDate($project['date']); ?></p>
            <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($project['title']); ?></h1>
            <p class="text-lg text-gray-200 mb-6"><?php echo htmlspecialchars($project['short_description']); ?></p>
            <div class="flex flex-wrap gap-3 mb-6">
                <?php foreach ($project['class_scope'] as $classTag): ?>
                    <span class="px-3 py-1 bg-white/10 rounded-full text-sm"><?php echo htmlspecialchars($classTag); ?></span>
                <?php endforeach; ?>
                <?php foreach ($project['department_scope'] as $deptTag): ?>
                    <span class="px-3 py-1 bg-white/10 rounded-full text-sm"><?php echo htmlspecialchars($deptTag); ?></span>
                <?php endforeach; ?>
                <?php if ($project['is_collaborative']): ?>
                    <span class="px-3 py-1 bg-green-500 text-white rounded-full text-sm">Club-wide</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($project['link'])): ?>
                <a href="<?php echo htmlspecialchars($project['link']); ?>" target="_blank" rel="noopener" class="inline-flex items-center px-5 py-3 bg-primary-500 hover:bg-primary-600 rounded-md font-semibold">
                    View Prototype / Report
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
        <div class="lg:w-1/2">
            <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="rounded-3xl shadow-2xl w-full h-80 object-cover">
        </div>
    </div>
</section>

<section class="py-16 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-10">
        <article class="lg:col-span-2 bg-gray-50 dark:bg-gray-800 rounded-3xl p-8 shadow">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Project Narrative</h2>
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <?php echo nl2br(htmlspecialchars($project['long_description'] ?: $project['description'])); ?>
            </div>
        </article>
        <aside class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-8 shadow space-y-8">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Classification</h3>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li><strong>Date:</strong> <?php echo formatDate($project['date']); ?></li>
                    <li><strong>Classes:</strong> <?php echo !empty($project['class_scope']) ? htmlspecialchars(implode(', ', $project['class_scope'])) : 'General'; ?></li>
                    <li><strong>Departments:</strong> <?php echo !empty($project['department_scope']) ? htmlspecialchars(implode(', ', $project['department_scope'])) : 'General'; ?></li>
                    <li><strong>Collaborative:</strong> <?php echo $project['is_collaborative'] ? 'Yes' : 'No'; ?></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Contributors</h3>
                <?php if (!empty($contributors)): ?>
                    <ul class="space-y-3">
                        <?php foreach ($contributors as $contributor): ?>
                            <li class="p-3 border border-gray-200 dark:border-gray-700 rounded-xl">
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($contributor['name']); ?></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($contributor['member_id']); ?></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars(trim($contributor['class_level'] . ' · ' . $contributor['department'], ' ·')); ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contributor list is being curated. Stay tuned!</p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<section class="pb-16">
    <div class="container mx-auto px-4 text-center">
        <a href="/projects" class="inline-flex items-center px-6 py-3 border border-primary-500 text-primary-500 rounded-full hover:bg-primary-500 hover:text-white transition">
            Browse more projects
        </a>
    </div>
</section>

