<?php
$classOptions = getClassOptions(true);
?>
<section class="bg-gray-100 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Academic Classes</h1>
        <p class="text-gray-600 max-w-3xl">Select a class to browse its dedicated groups, members, and featured projects.</p>
    </div>
</section>

<section class="py-16 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($classOptions as $class): ?>
            <article class="border border-gray-200 dark:border-gray-700 rounded-2xl p-8 shadow-sm hover:shadow-lg transition bg-gray-50 dark:bg-gray-800 flex flex-col justify-between">
                <div>
                    <p class="text-sm uppercase tracking-wide text-primary-500 mb-2">Intermediate Program</p>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-3"><?php echo htmlspecialchars($class['name']); ?></h2>
                    <?php if (!empty($class['groups'])): ?>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Available groups: <?php echo htmlspecialchars(implode(', ', $class['groups'])); ?></p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Explore members, signature projects and departmental collaborations powered by this class.</p>
                </div>
                <div class="mt-6">
                    <a href="/class/<?php echo htmlspecialchars(slugifyText($class['name'])); ?>" class="inline-flex items-center px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-300" aria-label="View <?php echo htmlspecialchars($class['name']); ?> hub">
                        View class hub →
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

