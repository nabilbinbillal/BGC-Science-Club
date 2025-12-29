
<!-- Dedicated to my beloved parents, my dear brother Nafis, and my grandparents from both sides. And to that one special friend — if you ever find this, even by chance, know that you have a place in my story. -->

<?php
echo '<div style="display:none;">
Dedicated to my beloved parents, my dear brother Nafis, and my grandparents from both sides.
And to that one special friend — if you ever find this, even by chance, know that you have a place in my story.
</div>';
?>

<section class="hero flex items-center justify-center">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
<h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4 slide-in-up">
  Welcome to 
  <span class="inline-block relative">
    <span class="absolute inset-0 bg-yellow-300/60 -skew-x-6 rounded-md"></span>
    <span class="relative break-words">
      Brahmanbaria Govt. College <br>Science Club
    </span>
  </span>
</h1>








        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto slide-in-up">Explore the wonders of science with us and be a part of our scientific community.</p>

<style>
/* animate custom angle without @property */
:root {
    --border-angle: 0deg;
}

@keyframes spinBorder {
    from { --border-angle: 0deg; }
    to   { --border-angle: 360deg; }
}

/* wrapper that draws the animated outline */
.animated-outline {
    position: relative;
    display: inline-block;
    padding: 2px; /* border thickness */
    border-radius: 12px;
    background: conic-gradient(
        from var(--border-angle),
        #22c55e,
        #16a34a,
        #22c55e
    );
    animation: spinBorder 4s linear infinite;
}

/* mask so inside stays clean */
.animated-outline > .inner-btn {
    display: block;
    background: #16a34a;
    border-radius: 10px;
    padding: 14px 32px;
}

/* glow */
.animated-outline {
    box-shadow: 0 0 18px rgba(34,197,94,0.35);
}
</style>

<div class="animated-outline">
    <a href="/join"
       class="inner-btn text-white font-semibold transition-all duration-300 hover:scale-105 hover:bg-green-700">
        Join Us Today
    </a>
</div>



    </div>
</section>

<section class="py-16 bg-white dark:bg-gray-800 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">What We Do</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">Our mission is to foster scientific knowledge, innovation, and research among the students of Brahmanbaria Government College.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-8 text-center card animate-on-scroll">
                <div class="text-primary-500 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Scientific Workshops</h3>
                <p class="text-gray-600 dark:text-gray-300">We organize regular workshops on various scientific topics to enhance practical knowledge.</p>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-8 text-center card animate-on-scroll">
                <div class="text-primary-500 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Research Projects</h3>
                <p class="text-gray-600 dark:text-gray-300">We encourage and support student research projects in various scientific disciplines.</p>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-8 text-center card animate-on-scroll">
                <div class="text-primary-500 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Knowledge Sharing</h3>
                <p class="text-gray-600 dark:text-gray-300">We publish scientific journals and organize seminars to share knowledge and discoveries.</p>
            </div>
        </div>
    </div>
</section>

<?php
$classOptionsHome = getClassOptions(true);
$departmentOptionsHome = getDepartmentOptions();
$departmentIconMap = getDepartmentMetaMap();
?>
<section class="py-16 bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Classes & Departments</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">We proudly collaborate across Intermediate classes, academic groups, and science departments.</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Our Classes</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Select a class to explore projects, members, and dedicated groups.</p>
                <div class="space-y-4">
                    <?php foreach ($classOptionsHome as $class): ?>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-primary-500 transition">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($class['name']); ?></p>
                                    <?php if (!empty($class['groups'])): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Groups: <?php echo htmlspecialchars(implode(', ', $class['groups'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <a href="/class/<?php echo htmlspecialchars(slugifyText($class['name'])); ?>" class="text-primary-500 hover:text-primary-600 text-sm font-semibold">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Departments</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Dedicated labs and classrooms powering our research culture.</p>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($departmentOptionsHome as $dept): ?>
                        <?php $deptIcon = $departmentIconMap[$dept] ?? '🔬'; ?>
                        <a href="/department/<?php echo htmlspecialchars(slugifyText($dept)); ?>" class="inline-flex items-center space-x-3 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-sm font-medium hover:bg-primary-500 hover:text-white transition">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/80 dark:bg-gray-800 text-base">
                                <?php echo $deptIcon; ?>
                            </span>
                            <span><?php echo htmlspecialchars($dept); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>









<?php
require_once 'config/db.php';

// Get all events ordered by date DESC (latest first)
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();

$today = date('Y-m-d');

// Separate events into upcoming and past
$upcomingEvents = [];
$pastEvents = [];

foreach ($events as $event) {
    if ($event['event_date'] >= $today) {
        $upcomingEvents[] = $event;
    } else {
        $pastEvents[] = $event;
    }
}

// Limit to 3 each
$upcomingEvents = array_slice($upcomingEvents, 0, 3);
$pastEvents = array_slice($pastEvents, 0, 3);

$projectStmt = $pdo->query("SELECT * FROM projects ORDER BY date DESC LIMIT 6");
$recentProjects = array_map(function ($project) {
    return normalizeProjectRecord($project);
}, $projectStmt->fetchAll());
?>

<?php if (!empty($recentProjects)): ?>
<section class="py-16 bg-white dark:bg-gray-800 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-10">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Recent Projects</h2>
                <p class="text-gray-600 dark:text-gray-300">Showcasing analytical minds across ICT, Physics, Chemistry, Botany, Zoology, Mathematics and beyond.</p>
            </div>
            <a href="/projects" class="mt-4 md:mt-0 inline-flex items-center px-5 py-2 border border-primary-500 text-primary-500 rounded-full hover:bg-primary-500 hover:text-white transition">
                View All Projects
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($recentProjects as $index => $project): 
                $hiddenClass = '';
                $imageSrc = !empty($project['image']) ? '/uploads/projects/' . htmlspecialchars($project['image']) : 'https://images.pexels.com/photos/256381/pexels-photo-256381.jpeg';
            ?>
            <article class="bg-gray-50 dark:bg-gray-900 rounded-2xl shadow hover:shadow-lg transition overflow-hidden <?php echo $hiddenClass; ?>" data-home-project-card>
                <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                <div class="p-6">
                    <p class="text-sm text-primary-500 font-semibold mb-2"><?php echo formatDate($project['date']); ?></p>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                    <?php
                    $summarySource = $project['short_description'] ?: ($project['description'] ?? '');
                    $summaryText = $summarySource;
                    if (!$project['short_description'] && strlen($summarySource) > 120) {
                        $summaryText = substr($summarySource, 0, 120) . '...';
                    }
                    ?>
                    <p class="text-gray-600 dark:text-gray-300 mb-4"><?php echo htmlspecialchars($summaryText); ?></p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php
                        $allClassNames = getClassOptions();
                        $allDeptNames = getDepartmentOptions();

                        $classScope = $project['class_scope'] ?? [];
                        $departmentScope = $project['department_scope'] ?? [];

                        // Show 'All Classes' if class scope covers every class in the system
                        if (!empty($classScope) && count(array_intersect($classScope, $allClassNames)) === count($allClassNames)) {
                            echo '<span class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300">All Classes</span>';
                        } else {
                            foreach ($classScope as $classTag) {
                                echo '<span class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300">' . htmlspecialchars($classTag) . '</span>';
                            }
                        }

                        // Show 'All Departments' if department scope covers every department in the system
                        if (!empty($departmentScope) && count(array_intersect($departmentScope, $allDeptNames)) === count($allDeptNames)) {
                            echo '<span class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300">All Departments</span>';
                        } else {
                            foreach ($departmentScope as $deptTag) {
                                $icon = htmlspecialchars($departmentIconMap[$deptTag] ?? '🔬');
                                echo '<span class="inline-flex items-center space-x-2 px-3 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300">';
                                echo '<span class="text-sm">' . $icon . '</span>';
                                echo '<span>' . htmlspecialchars($deptTag) . '</span>';
                                echo '</span>';
                            }
                        }
                        ?>
                        <?php if ($project['is_collaborative']): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Club-wide</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <?php $count = count($project['contributor_ids']); echo $count ? $count . ' contributor' . ($count > 1 ? 's' : '') : 'Open call'; ?>
                        </span>
                        <a href="/project/<?php echo htmlspecialchars($project['slug']); ?>" class="text-primary-500 hover:text-primary-600 text-sm font-semibold">Read more →</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <!-- Showing latest 6 projects only -->
    </div>
</section>
<?php endif; ?>

<div class="container mx-auto px-4 py-8">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">Events</h2>
    <div class="w-full h-1 bg-gray-300 dark:bg-gray-700 rounded mb-8"></div>

    
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Past Events -->
        <div class="lg:w-1/2">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4 text-center lg:text-left">Past Events</h2>
            <div class="w-24 h-1 bg-gray-400 mx-auto lg:mx-0 mb-4 rounded"></div>

            <div class="grid grid-cols-1 gap-6 max-h-[500px] overflow-y-auto pr-2 custom-scroll">
                <?php if (count($pastEvents) > 0): ?>
                    <?php foreach ($pastEvents as $event): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden opacity-75">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($event['name']); ?>
                                    </h3>
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 dark:text-gray-400 text-center py-8">No past events to display.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="lg:w-1/2">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4 text-center lg:text-left">Upcoming Events</h2>
            <div class="w-24 h-1 bg-gray-400 mx-auto lg:mx-0 mb-4 rounded"></div>
            <div class="grid grid-cols-1 gap-6 max-h-[500px] overflow-y-auto pr-2 custom-scroll">
                <?php if (count($upcomingEvents) > 0): ?>
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($event['name']); ?>
                                    </h3>
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 dark:text-gray-400 text-center py-8">No upcoming events scheduled at the moment.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>


        <div class="text-center mt-8">
            <a href="events" class="inline-block px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition duration-300 ease-in-out md-10">
                View All Events
            </a>
        </div>
        
<style>
/* Optional: Custom Scrollbar for better look */
.custom-scroll::-webkit-scrollbar {
    width: 8px;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(100, 100, 100, 0.4);
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
</style>








<section class="py-16 bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Activities</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                Explore our latest activities and initiatives that promote science and innovation.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            // Fetch the latest 3 activities (no date filter)
            $stmt = $pdo->query("SELECT * FROM activities ORDER BY date DESC LIMIT 3");
            $activities = $stmt->fetchAll();

            if (count($activities) > 0) {
                foreach ($activities as $activity) {
                    ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-md activity-card animate-on-scroll">
                        <?php if (!empty($activity['image'])): ?>
                            <img src="<?php echo 'uploads/activities/' . $activity['image']; ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>" class="w-full h-48 object-cover">
                        <?php else: ?>
                            <img src="https://images.pexels.com/photos/3862130/pexels-photo-3862130.jpeg" alt="Activity" class="w-full h-48 object-cover">
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="text-sm text-primary-500 mb-2"><?php echo formatDate($activity['date']); ?></div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($activity['title']); ?></h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                <?php echo substr(htmlspecialchars($activity['description']), 0, 100) . '...'; ?>
                            </p>
                            <?php if (!empty($activity['link'])): ?>
                                <a href="<?php echo htmlspecialchars($activity['link']); ?>" class="text-primary-500 hover:text-primary-600 font-medium">
                                    Learn More →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-span-3 text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">';
                echo '<p class="text-gray-600 dark:text-gray-300">No activities available at the moment.</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="activities" class="inline-block px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition duration-300 ease-in-out">
                View All Activities
            </a>
        </div>
    </div>
</section>











<section class="py-16 bg-white dark:bg-gray-800 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
        <h2 class="text-4xl font-extrabold text-black-400 mb-6" style="text-shadow: 0px 0px 8px rgba(255,255,0,0.9), 0px 0px 16px rgba(255,255,0,0.6);">
  Executive Committee
</h2>






            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">Meet the leaders who guide our science club and lead various initiatives.</p>
        </div>
        
        <div class="relative group">
            <!-- Navigation Buttons (Visible on all devices) -->
            <div class="flex justify-between absolute inset-0 z-10 items-center pointer-events-none">
                <button class="executive-prev bg-white dark:bg-gray-700 p-2 rounded-full shadow-md hover:bg-gray-100 dark:hover:bg-gray-600 pointer-events-auto opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button class="executive-next bg-white dark:bg-gray-700 p-2 rounded-full shadow-md hover:bg-gray-100 dark:hover:bg-gray-600 pointer-events-auto opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Carousel Container -->
            <div class="executive-carousel overflow-hidden">
                <div class="flex">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM executives ORDER BY id ASC");
                    $executives = $stmt->fetchAll();
                    $totalExecutives = count($executives);

                    if ($totalExecutives > 0) {
                        // Quadruple items for perfect looping
                        foreach (array_merge($executives, $executives, $executives, $executives) as $executive) {
                            $photoSrc = !empty($executive['profile_pic']) ? '/uploads/executives/' . htmlspecialchars($executive['profile_pic']) : 'assets/images/default-avatar.jpg';
                            ?>
                            <div class="flex-shrink-0 w-64 px-2">
                                <a href="/executive/<?php echo htmlspecialchars($executive['slug']); ?>" class="block">
                                    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:scale-105 h-full">
                                        <img 
                                            src="<?php echo $photoSrc; ?>" 
                                            alt="<?php echo htmlspecialchars($executive['name']); ?>" 
                                            class="w-full h-48 object-cover"
                                            onerror="this.src='assets/images/default-avatar.jpg'"
                                        >
                                        <div class="p-4">
                                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-1"><?php echo htmlspecialchars($executive['name']); ?></h3>
                                            <p class="text-purple-600 dark:text-purple-400 font-medium mb-1"><?php echo htmlspecialchars($executive['role']); ?></p>
                                            <p class="text-gray-600 dark:text-gray-300 text-sm">
                                                <?php echo ucfirst($executive['type']); ?> • 
                                                <?php echo htmlspecialchars($executive['department']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="w-full text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-gray-600 dark:text-gray-300">No executive members found.</p>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .executive-carousel {
        -ms-overflow-style: none;
        scrollbar-width: none;
        padding: 0 40px;
    }
    .executive-carousel::-webkit-scrollbar {
        display: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.executive-carousel');
    const track = carousel.querySelector('div');
    const items = track.querySelectorAll('div');
    const prevBtn = document.querySelector('.executive-prev');
    const nextBtn = document.querySelector('.executive-next');
    
    const itemWidth = 288; // w-64 + px-2
    let currentPosition = 0;
    let animationId;
    let lastTimestamp = 0;
    const normalSpeed = 1.5; // Faster base speed
    const hoverSpeed = 0.5; // Slower hover speed
    let currentSpeed = normalSpeed;
    const totalExecutives = <?php echo $totalExecutives; ?>;
    const segmentWidth = totalExecutives * itemWidth;
    let isScrolling = true;
    let isAnimating = false;
    
    // Ultra-smooth animation with requestAnimationFrame
    function autoScroll(timestamp) {
        if (!lastTimestamp) lastTimestamp = timestamp;
        const delta = timestamp - lastTimestamp;
        lastTimestamp = timestamp;
        
        if (isScrolling) {
            currentPosition -= currentSpeed * (delta / 16); // Normalized to 60fps
            
            // Seamless looping - quadruple items ensure no visible gaps
            if (currentPosition < -segmentWidth * 3) {
                currentPosition += segmentWidth * 2;
            }
            
            track.style.transform = `translateX(${currentPosition}px)`;
        }
        
        animationId = requestAnimationFrame(autoScroll);
    }
    
    // Initialize carousel
    function initCarousel() {
        cancelAnimationFrame(animationId);
        isScrolling = true;
        animationId = requestAnimationFrame(autoScroll);
    }
    
    // Speed control
    carousel.addEventListener('mouseenter', () => {
        currentSpeed = hoverSpeed;
    });
    
    carousel.addEventListener('mouseleave', () => {
        currentSpeed = normalSpeed;
    });
    
    // Manual navigation with debounce
    const navigate = (direction) => {
        if (isAnimating) return;
        isAnimating = true;
        isScrolling = false;
        
        const scrollAmount = itemWidth * 3 * direction;
        currentPosition += scrollAmount;
        
        // Boundary checks with quadruple items
        if (currentPosition > 0) {
            currentPosition = -segmentWidth * 3 + itemWidth;
        } else if (currentPosition < -segmentWidth * 3) {
            currentPosition = -segmentWidth + itemWidth;
        }
        
        track.style.transition = 'transform 0.4s cubic-bezier(0.33,0,0.67,1)';
        track.style.transform = `translateX(${currentPosition}px)`;
        
        setTimeout(() => {
            track.style.transition = '';
            isAnimating = false;
            isScrolling = true;
        }, 400);
    };
    
    // Event listeners with debounce
    prevBtn?.addEventListener('click', () => navigate(1));
    nextBtn?.addEventListener('click', () => navigate(-1));
    
    // Touch events for mobile
    let touchStartX = 0;
    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        isScrolling = false;
    }, { passive: true });
    
    carousel.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > 50) { // Minimum swipe distance
            navigate(diff > 0 ? -1 : 1);
        } else {
            isScrolling = true;
        }
    }, { passive: true });
    
    // Initialize
    initCarousel();
    
    // Pause on window blur (tab switch)
    window.addEventListener('blur', () => isScrolling = false);
    window.addEventListener('focus', () => isScrolling = true);
    
    // Cleanup
    window.addEventListener('beforeunload', () => {
        cancelAnimationFrame(animationId);
    });
});
</script>
<div class="text-center mt-0 mb-8">
    <a href="/executives" class="inline-block px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition duration-300 ease-in-out hover:drop-shadow-[0px_0px_10px_rgba(255,215,0,1)]">
        View All Executives
    </a>
</div>















<section class="py-16 bg-white dark:bg-gray-800 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <?php
            // Fetch total number of members
            $memberCountStmt = $pdo->query("SELECT COUNT(*) FROM members WHERE role = 'member' AND status = 'approved'");
            $memberCount = $memberCountStmt->fetchColumn();
            ?>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Members (<?php echo $memberCount; ?>+)
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                Meet our passionate members who make our club vibrant and energetic.
            </p>
        </div>

        <div class="relative group">
            <!-- Navigation Buttons -->
            <div class="flex justify-between absolute inset-0 z-10 items-center pointer-events-none">
                <button class="member-prev bg-white dark:bg-gray-700 p-2 rounded-full shadow-md hover:bg-gray-100 dark:hover:bg-gray-600 pointer-events-auto opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button class="member-next bg-white dark:bg-gray-700 p-2 rounded-full shadow-md hover:bg-gray-100 dark:hover:bg-gray-600 pointer-events-auto opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Carousel Container -->
            <div class="member-carousel overflow-hidden">
                <div class="flex">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM members WHERE role = 'member' AND status = 'approved' ORDER BY id ASC");
                    $members = $stmt->fetchAll();
                    $totalMembers = count($members);

                    if ($totalMembers > 0) {
                        foreach (array_merge($members, $members, $members, $members) as $member) {
                            $photoSrc = !empty($member['profile_pic']) ? 'uploads/members/' . htmlspecialchars($member['profile_pic']) : 'assets/images/default-avatar.jpg';
                            ?>
                            <div class="flex-shrink-0 w-64 px-2">
                                <a href="/member/" class="block">
                                    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:scale-105 h-full">
                                        <img 
                                            src="<?php echo $photoSrc; ?>" 
                                            alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                            class="w-full h-48 object-cover"
                                            onerror="this.src='assets/images/default-avatar.jpg'"
                                        >
                                        <div class="p-4">
                                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                                                <?php echo htmlspecialchars($member['name']); ?>
                                            </h3>
                                            <p class="text-purple-600 dark:text-purple-400 font-medium mb-1">
                                                <?php echo htmlspecialchars($member['department']); ?>
                                            </p>
                                            <p class="text-gray-600 dark:text-gray-300 text-sm">
                                                Member ID: <?php echo htmlspecialchars($member['member_id']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="w-full text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-gray-600 dark:text-gray-300">No members found.</p>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="/members" class="inline-block px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition duration-300 ease-in-out">
                View All Members
            </a>
        </div>
    </div>
</section>

<style>
    .member-carousel {
        -ms-overflow-style: none;
        scrollbar-width: none;
        padding: 0 40px;
    }
    .member-carousel::-webkit-scrollbar {
        display: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.member-carousel');
    const track = carousel.querySelector('div');
    const items = track.querySelectorAll('div');
    const prevBtn = document.querySelector('.member-prev');
    const nextBtn = document.querySelector('.member-next');
    
    const itemWidth = 288;
    let currentPosition = 0;
    let animationId;
    let lastTimestamp = 0;
    const normalSpeed = 1.5;
    const hoverSpeed = 0.5;
    let currentSpeed = normalSpeed;
    const totalMembers = <?php echo $totalMembers; ?>;
    const segmentWidth = totalMembers * itemWidth;
    let isScrolling = true;
    let isAnimating = false;
    
    function autoScroll(timestamp) {
        if (!lastTimestamp) lastTimestamp = timestamp;
        const delta = timestamp - lastTimestamp;
        lastTimestamp = timestamp;
        
        if (isScrolling) {
            currentPosition -= currentSpeed * (delta / 16);
            if (currentPosition < -segmentWidth * 3) {
                currentPosition += segmentWidth * 2;
            }
            track.style.transform = `translateX(${currentPosition}px)`;
        }
        
        animationId = requestAnimationFrame(autoScroll);
    }
    
    function initCarousel() {
        cancelAnimationFrame(animationId);
        isScrolling = true;
        animationId = requestAnimationFrame(autoScroll);
    }
    
    carousel.addEventListener('mouseenter', () => {
        currentSpeed = hoverSpeed;
    });
    
    carousel.addEventListener('mouseleave', () => {
        currentSpeed = normalSpeed;
    });
    
    const navigate = (direction) => {
        if (isAnimating) return;
        isAnimating = true;
        isScrolling = false;
        
        const scrollAmount = itemWidth * 3 * direction;
        currentPosition += scrollAmount;
        
        if (currentPosition > 0) {
            currentPosition = -segmentWidth * 3 + itemWidth;
        } else if (currentPosition < -segmentWidth * 3) {
            currentPosition = -segmentWidth + itemWidth;
        }
        
        track.style.transition = 'transform 0.4s cubic-bezier(0.33,0,0.67,1)';
        track.style.transform = `translateX(${currentPosition}px)`;
        
        setTimeout(() => {
            track.style.transition = '';
            isAnimating = false;
            isScrolling = true;
        }, 400);
    };
    
    prevBtn?.addEventListener('click', () => navigate(1));
    nextBtn?.addEventListener('click', () => navigate(-1));
    
    let touchStartX = 0;
    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        isScrolling = false;
    }, { passive: true });
    
    carousel.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > 50) {
            navigate(diff > 0 ? -1 : 1);
        } else {
            isScrolling = true;
        }
    }, { passive: true });
    
    initCarousel();
    
    window.addEventListener('blur', () => isScrolling = false);
    window.addEventListener('focus', () => isScrolling = true);
    
    window.addEventListener('beforeunload', () => {
        cancelAnimationFrame(animationId);
    });
});
</script>
