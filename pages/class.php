<?php
$slug = $_GET['slug'] ?? '';
$classOptions = getClassOptions(true);
$selectedClass = null;
foreach ($classOptions as $class) {
    if (slugifyText($class['name']) === $slug) {
        $selectedClass = $class;
        break;
    }
}

if (!$selectedClass) {
    http_response_code(404);
    ?>
    <section class="py-20 bg-gray-100">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Class not found</h1>
            <p class="text-gray-600 mb-6">Please head back and choose another class.</p>
            <a href="/classes" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-md">Browse Classes</a>
        </div>
    </section>
    <?php
    return;
}

$groupOptions = $selectedClass['groups'] ?? [];
$groupFilter = $_GET['group'] ?? '';
if (!in_array($groupFilter, $groupOptions)) {
    $groupFilter = '';
}

$memberSql = "SELECT name, member_id, department, group_name, image FROM members WHERE status = 'approved' AND class_level = ?";
$memberParams = [$selectedClass['name']];
if ($groupFilter) {
    $memberSql .= " AND group_name = ?";
    $memberParams[] = $groupFilter;
}
$memberSql .= " ORDER BY group_name, name";
$memberStmt = $pdo->prepare($memberSql);
$memberStmt->execute($memberParams);
$classMembers = $memberStmt->fetchAll();

$projectsStmt = $pdo->query("SELECT * FROM projects ORDER BY date DESC");
$classProjects = [];
foreach ($projectsStmt->fetchAll() as $projectRow) {
    $project = normalizeProjectRecord($projectRow);
    if (in_array($selectedClass['name'], $project['class_scope'])) {
        if ($groupFilter) {
            // optional: only show if group-specific; not tracked so show all
        }
        $classProjects[] = $project;
    }
}
?>

<section class="bg-gray-100 py-10">
    <div class="container mx-auto px-4">
        <a href="/classes" class="text-sm text-primary-500 hover:text-primary-600">← Back to classes</a>
        <h1 class="text-4xl font-bold text-gray-800 mt-2"><?php echo htmlspecialchars($selectedClass['name']); ?></h1>
        <p class="text-gray-600 mt-2">Discover members, groups, and flagship projects from this class cohort.</p>
    </div>
</section>

<section class="py-12 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($groupOptions)): ?>
            <div class="flex flex-wrap gap-3 mb-8">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Groups:</span>

                <!-- ALL -->
                <a href="/class/<?php echo htmlspecialchars($slug); ?>"
                   class="px-4 py-2 rounded-full border text-sm <?php echo $groupFilter === '' ? '' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'; ?>"
                   style="<?php echo $groupFilter === '' ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : ''; ?>">
                    All
                </a>

                <!-- Dynamic Groups -->
                <?php foreach ($groupOptions as $group): ?>
                    <a href="/class/<?php echo htmlspecialchars($slug); ?>?group=<?php echo urlencode($group); ?>"
                       class="px-4 py-2 rounded-full border text-sm <?php echo $groupFilter === $group ? '' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'; ?>"
                       style="<?php echo $groupFilter === $group ? 'background:#2563eb;color:#fff;border-color:#2563eb;' : ''; ?>">
                        <?php echo htmlspecialchars($group); ?>
                    </a>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>


        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Members</h2>
                    <span class="text-sm text-gray-500"><?php echo count($classMembers); ?> listed</span>
                </div>
                <?php if (!empty($classMembers)): ?>
                    <div class="overflow-x-auto bg-gray-50 dark:bg-gray-800 rounded-2xl">
                        <table class="min-w-full">
                            <thead>
                                <tr class="text-left text-sm uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-6 py-3">Member</th>
                                    <th class="px-6 py-3">Group</th>
                                    <th class="px-6 py-3">Department</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($classMembers as $member): ?>
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="px-6 py-4">
                                            <p class="font-semibold"><?php echo htmlspecialchars($member['name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($member['member_id']); ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($member['group_name'] ?: 'General'); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($member['department']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center text-gray-500">Not Available</div>
                <?php endif; ?>
            </div>
            <aside class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Stats</h3>
                <ul class="space-y-3 text-gray-600 dark:text-gray-300">
                    <li><strong>Groups:</strong> <?php echo !empty($groupOptions) ? htmlspecialchars(implode(', ', $groupOptions)) : 'Not specified'; ?></li>
                    <li><strong>Members listed:</strong> <?php echo count($classMembers); ?></li>
                    <li><strong>Projects:</strong> <?php echo count($classProjects); ?></li>
                </ul>
                <div class="mt-6">
    <a href="/projects?class=<?php echo urlencode($selectedClass['name']); ?>" 
       class="inline-flex items-center px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-full shadow-xl transform hover:-translate-y-0.5 transition-all duration-150 font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-300">
        Filter projects →
    </a>
</div>

            </aside>
        </div>
    </div>
</section>

<section class="py-12 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Projects from this class</h2>
            <a href="/projects?class=<?php echo urlencode($selectedClass['name']); ?>" class="text-primary-500 hover:text-primary-600 text-sm font-semibold">See all</a>
        </div>
        <?php if (!empty($classProjects)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($classProjects as $project): 
                    $imageSrc = !empty($project['image']) ? '/uploads/projects/' . htmlspecialchars($project['image']) : 'https://images.pexels.com/photos/414860/pexels-photo-414860.jpeg';
                ?>
                    <article class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden flex flex-col">
                        <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-40 object-cover">
                        <div class="p-6 flex flex-col flex-grow">
                            <p class="text-xs uppercase text-primary-500 mb-1"><?php echo formatDate($project['date']); ?></p>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4 flex-grow"><?php echo htmlspecialchars($project['short_description']); ?></p>
                            <a href="/project/<?php echo htmlspecialchars($project['slug']); ?>" class="text-primary-500 hover:text-primary-600 text-sm font-semibold">Read more →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 text-center text-gray-500 dark:text-gray-400">No projects tagged with this class yet.</div>
        <?php endif; ?>
    </div>
</section>

