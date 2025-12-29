<?php
require_once 'config/db.php';

// Get all events ordered by date
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();

// Get current date for comparison
$today = date('Y-m-d');
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Events</h1>
    
    <!-- Upcoming Events Section -->
    <div class="mb-12">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Upcoming Events</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            $hasUpcoming = false;
            foreach ($events as $event): 
                if ($event['event_date'] >= $today):
                    $hasUpcoming = true;
            ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($event['name']); ?></h3>
                            <span class="px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded-full">Upcoming</span>
                        </div>
                        <div class="mb-4">
                            <p class="text-gray-600 dark:text-gray-400">
                                <i class="far fa-calendar-alt mr-2"></i>
                                <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                            </p>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-4"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            
            if (!$hasUpcoming):
            ?>
                <div class="col-span-full">
                    <p class="text-gray-600 dark:text-gray-400 text-center py-8">No upcoming events scheduled at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Past Events Section -->
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Past Events</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            $hasPast = false;
            foreach ($events as $event): 
                if ($event['event_date'] < $today):
                    $hasPast = true;
            ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden opacity-75">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($event['name']); ?></h3>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-800 bg-gray-200 rounded-full">Past</span>
                        </div>
                        <div class="mb-4">
                            <p class="text-gray-600 dark:text-gray-400">
                                <i class="far fa-calendar-alt mr-2"></i>
                                <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                            </p>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-4"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            
            if (!$hasPast):
            ?>
                <div class="col-span-full">
                    <p class="text-gray-600 dark:text-gray-400 text-center py-8">No past events to display.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>