<?php
$departments = getDepartmentOptions();
$deptIcons = getDepartmentMetaMap();
?>
<section class="bg-gray-100 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Departments</h1>
        <p class="text-gray-600 max-w-3xl">Physics, Chemistry, Botany, Zoology, ICT, Mathematics — every department contributes to our vibrant science culture.</p>
    </div>
</section>

<section class="py-16 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($departments as $dept): ?>
            <article class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6 bg-gray-50 dark:bg-gray-800 hover:shadow-lg transition flex flex-col justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wider text-primary-500">Department</p>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                        <span class="mr-2"><?php echo htmlspecialchars($deptIcons[$dept] ?? '🔬'); ?></span>
                        <?php echo htmlspecialchars($dept); ?>
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Research circles, lab activities and specialized mentoring aligned with <?php echo htmlspecialchars($dept); ?>.</p>
                </div>
                <div class="mt-6">
                    <a href="/department/<?php echo htmlspecialchars(slugifyText($dept)); ?>" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-full text-sm font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-300" aria-label="Explore <?php echo htmlspecialchars($dept); ?>">
                        Explore department →
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

