<?php
$slug = $_GET['slug'] ?? '';
$departments = getDepartmentOptions();
$selectedDepartment = null;
foreach ($departments as $dept) {
    if (slugifyText($dept) === $slug) {
        $selectedDepartment = $dept;
        break;
    }
}

if (!$selectedDepartment) {
    http_response_code(404);
    ?>
    <section class="py-20 bg-gray-100">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Department not found</h1>
            <p class="text-gray-600 mb-6">Please select another department from the directory.</p>
            <a href="/departments" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-md">Browse Departments</a>
        </div>
    </section>
    <?php
    return;
}

$memberStmt = $pdo->prepare("SELECT name, member_id, class_level, group_name FROM members WHERE status = 'approved' AND department = ? ORDER BY class_level, name");
$memberStmt->execute([$selectedDepartment]);
$departmentMembers = $memberStmt->fetchAll();

$projectsStmt = $pdo->query("SELECT * FROM projects ORDER BY date DESC");
$departmentProjects = [];
foreach ($projectsStmt->fetchAll() as $projectRow) {
    $project = normalizeProjectRecord($projectRow);
    if (in_array($selectedDepartment, $project['department_scope'])) {
        $departmentProjects[] = $project;
    }
}
?>

<section class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-4 flex flex-col lg:flex-row items-center gap-8">
        <div class="lg:w-2/3">
            <p class="text-sm uppercase tracking-widest text-primary-300 mb-2">Department spotlight</p>
            <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($selectedDepartment); ?></h1>
            <p class="text-gray-200">Lab experiments, workshops, symposiums and cross-class research from the <?php echo htmlspecialchars($selectedDepartment); ?> department.</p>
        </div>
        <a href="/projects?department=<?php echo urlencode($selectedDepartment); ?>" class="px-6 py-3 border border-white rounded-full text-white bg-primary-700 hover:bg-primary-800 transition text-white">
            Filter projects
        </a>
    </div>
</section>

<section class="py-12 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Members</h2>
                <span class="text-sm text-gray-500"><?php echo count($departmentMembers); ?> listed</span>
            </div>
            <?php if (!empty($departmentMembers)): ?>
                <div class="overflow-x-auto bg-gray-50 dark:bg-gray-800 rounded-2xl">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-6 py-3">Member</th>
                                <th class="px-6 py-3">Class</th>
                                <th class="px-6 py-3">Group</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($departmentMembers as $member): ?>
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold"><?php echo htmlspecialchars($member['name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($member['member_id']); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($member['class_level'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($member['group_name'] ?: 'General'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center text-gray-500">No members listed for this department yet.</div>
            <?php endif; ?>
        </div>
        <aside class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">At a glance</h3>
            <ul class="space-y-3 text-gray-600 dark:text-gray-300">
                <li><strong>Department:</strong> <?php echo htmlspecialchars($selectedDepartment); ?></li>
                <li><strong>Members listed:</strong> <?php echo count($departmentMembers); ?></li>
                <li><strong>Projects:</strong> <?php echo count($departmentProjects); ?></li>
            </ul>
        </aside>
    </div>
</section>

<section class="py-12 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Projects by <?php echo htmlspecialchars($selectedDepartment); ?></h2>
            <a href="/projects?department=<?php echo urlencode($selectedDepartment); ?>" class="text-primary-500 hover:text-primary-600 text-sm font-semibold">See all</a>
        </div>
        <?php if (!empty($departmentProjects)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($departmentProjects as $project): 
                    $imageSrc = !empty($project['image']) ? '/uploads/projects/' . htmlspecialchars($project['image']) : 'https://images.pexels.com/photos/356065/pexels-photo-356065.jpeg';
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
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 text-center text-gray-500 dark:text-gray-400">No projects tagged with this department yet.</div>
        <?php endif; ?>
    </div>
</section>

