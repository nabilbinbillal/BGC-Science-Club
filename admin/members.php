<?php
$departments = getDepartmentOptions();
$classOptions = getClassOptions();
$selectedDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$selectedClass = isset($_GET['class_level']) ? $_GET['class_level'] : '';
$searchQuery = trim($_GET['search'] ?? '');
?>
<!-- Page Header -->
<div class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Members</h1>
        <nav class="text-gray-500 text-sm">
            <a href="/" class="hover:text-purple-600">Home</a>
            <span class="mx-2">/</span>
            <span>Members</span>
        </nav>
    </div>
</div>
<section class="bg-white dark:bg-gray-800 py-16 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">Club Members</h1>
        
        <div class="max-w-3xl mx-auto text-center mb-12">
            <p class="text-lg text-gray-700 dark:text-gray-300">
                Meet the dedicated members of the BGC Science Club who actively participate in our events and activities.
            </p>
        </div>
        
        <!-- Department Filter -->
<form method="GET" action="/members" class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">
    <div>
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Department</label>
        <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Departments</option>
            <?php foreach ($departments as $department) {
                $selected = ($selectedDepartment == $department) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($department) . '" ' . $selected . '>' . htmlspecialchars($department) . '</option>';
            } ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Class</label>
        <select name="class_level" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Classes</option>
            <?php foreach ($classOptions as $class) {
                $selected = ($selectedClass == $class) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
            } ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Search</label>
        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Member name or ID" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div class="flex items-end">
        <button type="submit" class="w-full px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition duration-300 ease-in-out">
            Apply Filters
        </button>
    </div>
</form>


        
        <?php
        // Get all approved members
        $sql = "SELECT * FROM members WHERE status = 'approved' ";
        $params = [];
        
        // Apply department filter
        if (!empty($selectedDepartment)) {
            if ($selectedDepartment === 'ICT') {
                // Show all ICT department members and any students in intermediate classes
                $sql .= " AND (department = ? OR class_level LIKE 'Intermediate%')";
                $params[] = $selectedDepartment;
            } else {
                // For other departments, filter normally
                $sql .= " AND department = ?";
                $params[] = $selectedDepartment;
            }
        }
        
        if (!empty($selectedClass)) {
            $sql .= " AND class_level = ?";
            $params[] = $selectedClass;
        }
        
        if (!empty($searchQuery)) {
            $sql .= " AND (name LIKE ? OR member_id LIKE ?)";
            $like = '%' . $searchQuery . '%';
            $params[] = $like;
            $params[] = $like;
        }
        
        // Order by department and class according to the predefined order
        $sql .= " ORDER BY FIELD(department, '" . implode("','", $departments) . "'), FIELD(class_level, '" . implode("','", $classOptions) . "'), name";
        
        // Debug: Display the SQL query and parameters
        $debug_sql = $sql;
        foreach ($params as $param) {
            $debug_sql = preg_replace('/\?/', "'" . $param . "'", $debug_sql, 1);
        }
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $members = $stmt->fetchAll();
            
            // Debug output
            echo "<!-- SQL Query: " . htmlspecialchars($debug_sql) . " -->\n";
            if ($stmt->errorCode() != '00000') {
                $error = $stmt->errorInfo();
                echo "<!-- SQL Error: " . htmlspecialchars($error[2]) . " -->\n";
            }
            
        } catch (PDOException $e) {
            echo "<!-- Database Error: " . htmlspecialchars($e->getMessage()) . " -->\n";
            $members = [];
        }
        
        // Group members by department
        $membersByDepartment = [];
        foreach ($members as $member) {
            $department = $member['department'];
            if (!isset($membersByDepartment[$department])) {
                $membersByDepartment[$department] = [];
            }
            $membersByDepartment[$department][] = $member;
        }
        
        // Display members by department
        if (!empty($membersByDepartment)) {
            foreach ($departments as $department) {
                if (isset($membersByDepartment[$department]) && !empty($membersByDepartment[$department])) {
                    ?>
                    <div class="mb-12">
                        <?php $deptIcons = getDepartmentMetaMap(); ?>
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6 pb-2 border-b border-gray-200 dark:border-gray-700">
                                    <span class="mr-2"><?php echo htmlspecialchars($deptIcons[$department] ?? '🔬'); ?></span>
                                    <?php echo htmlspecialchars($department); ?>
                        </h2>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg overflow-hidden">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Member ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Class / Group</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joining Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <?php foreach ($membersByDepartment[$department] as $member): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($member['member_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars(trim($member['class_level'] . ' · ' . $member['group_name'], ' ·')); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars($member['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo formatDate($member['joining_date']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
            }
        } else {
            // No members found message
            echo '<div class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">';
            echo '<p class="text-gray-600 dark:text-gray-300">No members found.</p>';
            echo '</div>';
        }
        ?>
    </div>
</section>