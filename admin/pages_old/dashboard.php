<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php
    // Get statistics
    $memberCount = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'approved'")->fetchColumn();
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'pending'")->fetchColumn();
    $activityCount = $pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
    $adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

    $stats = [
        [
            'title' => 'Total Members',
            'value' => $memberCount,
            'icon' => 'fas fa-users',
            'color' => 'bg-blue-500'
        ],
        [
            'title' => 'Pending Applications',
            'value' => $pendingCount,
            'icon' => 'fas fa-user-clock',
            'color' => 'bg-yellow-500',
            'link' => '?page=members&status=pending'
        ],
        [
            'title' => 'Total Activities',
            'value' => $activityCount,
            'icon' => 'fas fa-calendar-alt',
            'color' => 'bg-green-500'
        ],
        [
            'title' => 'Admin Users',
            'value' => $adminCount,
            'icon' => 'fas fa-user-shield',
            'color' => 'bg-purple-500'
        ]
    ];

    foreach ($stats as $stat):
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <div class="flex items-center">
                <div class="<?php echo $stat['color']; ?> rounded-full p-3">
                    <i class="<?php echo $stat['icon']; ?> text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                        <?php if (isset($stat['link'])): ?>
                            <a href="<?php echo $stat['link']; ?>" class="hover:text-primary-500">
                                <?php echo $stat['title']; ?>
                            </a>
                        <?php else: ?>
                            <?php echo $stat['title']; ?>
                        <?php endif; ?>
                    </h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?php echo $stat['value']; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Members -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Members</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php
                        // Fetch last 3 approved members
                        $approvedStmt = $pdo->query("SELECT * FROM members WHERE status = 'approved' ORDER BY created_at DESC LIMIT 3");
                        while ($member = $approvedStmt->fetch()):
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <a href="?page=members&view=<?php echo $member['id']; ?>" class="hover:text-primary-500">
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($member['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <?php
                        // Fetch all pending members
                        $pendingStmt = $pdo->query("SELECT * FROM members WHERE status = 'pending' ORDER BY created_at DESC");
                        while ($member = $pendingStmt->fetch()):
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <a href="?page=members&view=<?php echo $member['id']; ?>" class="hover:text-primary-500">
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($member['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activities</h3>
            <div class="space-y-4">
                <?php
                $stmt = $pdo->query("SELECT * FROM activities ORDER BY date DESC LIMIT 3");
                while ($activity = $stmt->fetch()):
                ?>
                <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <?php if ($activity['image']): ?>
                    <img src="../uploads/activities/<?php echo $activity['image']; ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>" 
                        class="h-16 w-16 object-cover rounded-lg">
                    <?php else: ?>
                    <div class="h-16 w-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar text-gray-400 text-2xl"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ml-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($activity['title']); ?>
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?php echo formatDate($activity['date']); ?>
                        </p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="mt-4 text-center">
                <a href="index.php?page=activities" class="inline-block bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600 transition">
                    View All Activities
                </a>
            </div>
        </div>
    </div>
</div>
