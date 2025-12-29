<?php
// Set page title and description for SEO
$pageTitle = 'BGC Science Club Members - Meet Our Community';
$pageDescription = 'Explore the diverse community of BGC Science Club members. Connect with fellow science enthusiasts and active participants.';

// Add Open Graph and Twitter Card meta tags
echo '<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="' . htmlspecialchars($pageTitle) . '">
<meta property="og:description" content="' . htmlspecialchars($pageDescription) . '">
<meta property="og:url" content="https://bgcscienceclub.org/members">
<meta property="og:image" content="https://bgcscienceclub.org/pages/assets/images/Members.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="' . htmlspecialchars($pageTitle) . '">
<meta name="twitter:description" content="' . htmlspecialchars($pageDescription) . '">
<meta name="twitter:image" content="https://bgcscienceclub.org/pages/assets/images/Members.png">
';

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

<!-- Add JavaScript for real-time filtering -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get filter elements
    const departmentSelect = document.querySelector('select[name="department"]');
    const classSelect = document.querySelector('select[name="class_level"]');
    const searchInput = document.querySelector('input[name="search"]');
    const urlParams = new URLSearchParams(window.location.search);
    
    // Function to update URL without reloading the page
    function updateQueryString(key, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        // Update browser URL without reloading
        history.pushState({}, '', url);
        
        // Trigger filtering
        filterMembers();
    }
    
    // Function to filter members in real-time
    function filterMembers() {
        const departmentValue = departmentSelect.value;
        const classValue = classSelect.value;
        const searchValue = searchInput.value.trim();
        
        // Get all department sections
        const departmentSections = document.querySelectorAll('.department-section');
        let visibleSections = 0;
        
        departmentSections.forEach(section => {
            const sectionTitle = section.querySelector('h2').textContent;
            const rows = section.querySelectorAll('tbody tr');
            let visibleRows = 0;
            
            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const memberId = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const classInfo = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                // Apply filters
                let showRow = true;
                
                // Department filter
                if (departmentValue && !sectionTitle.includes(departmentValue)) {
                    showRow = false;
                }
                
                // Class filter
                if (classValue && showRow) {
                    const rowClass = classInfo.split('(')[0].trim();
                    if (rowClass !== classValue.toLowerCase()) {
                        showRow = false;
                    }
                }
                
                // Search filter
                if (searchValue && showRow) {
                    const searchLower = searchValue.toLowerCase();
                    if (!name.includes(searchLower) && !memberId.includes(searchLower)) {
                        showRow = false;
                    }
                }
                
                // Show/hide row
                row.style.display = showRow ? '' : 'none';
                if (showRow) visibleRows++;
            });
            
            // Show/hide entire department section based on visible rows
            section.style.display = visibleRows > 0 ? '' : 'none';
            if (visibleRows > 0) visibleSections++;
        });
        
        // Show/hide no results message
        const noResultsDiv = document.getElementById('no-results-message');
        if (noResultsDiv) {
            noResultsDiv.style.display = visibleSections > 0 ? 'none' : 'block';
        }
    }
    
    // Event listeners for real-time filtering
    departmentSelect.addEventListener('change', function() {
        updateQueryString('department', this.value);
    });
    
    classSelect.addEventListener('change', function() {
        updateQueryString('class_level', this.value);
    });
    
    // Debounce function for search input
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            updateQueryString('search', this.value);
        }, 300); // 300ms delay for better performance
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        departmentSelect.value = urlParams.get('department') || '';
        classSelect.value = urlParams.get('class_level') || '';
        searchInput.value = urlParams.get('search') || '';
        filterMembers();
    });
    
    // Initial filter on page load
    filterMembers();
});
</script>

<section class="bg-white dark:bg-gray-800 py-16 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">Club Members</h1>
        
        <div class="max-w-3xl mx-auto text-center mb-12">
            <p class="text-lg text-gray-700 dark:text-gray-300">
                Meet the dedicated members of the BGC Science Club who actively participate in our events and activities.
            </p>
        </div>
        
        <!-- Dynamic Filter Form -->
        <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">
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
                <button type="button" onclick="window.location.href='/members'" class="w-full px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-md transition duration-300 ease-in-out dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- No Results Message (hidden by default) -->
        <div id="no-results-message" class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg hidden">
            <p class="text-gray-600 dark:text-gray-300">No members found matching your criteria.</p>
        </div>
        
        <?php
        // Get all approved members with explicit field selection
        $sql = "SELECT id, member_id, name, class_level, group_name, department, roll_no, email, phone, gender, image, id_card_image, status, created_at, joining_date FROM members WHERE status = 'approved' ";
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
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $members = $stmt->fetchAll();
            
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
                    <div class="mb-12 department-section">
                        <?php $deptIcons = getDepartmentMetaMap(); ?>
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6 pb-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="mr-2"><?php echo htmlspecialchars($deptIcons[$department] ?? '🔬'); ?></span>
                            <?php echo htmlspecialchars($department); ?>
                        </h2>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg overflow-hidden">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Member ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joining Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Class / Group</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <?php foreach ($membersByDepartment[$department] as $member): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($member['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars($member['member_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo !empty($member['joining_date']) ? formatDate($member['joining_date']) : formatDate($member['created_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php 
                                            $display = $member['class_level'];
                                            if (!empty($member['group_name'])) {
                                                $display .= ' (' . $member['group_name'] . ')';
                                            }
                                            echo htmlspecialchars($display);
                                            ?>
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
            // No members found message (initial load)
            echo '<div id="no-results-message" class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">';
            echo '<p class="text-gray-600 dark:text-gray-300">No members found.</p>';
            echo '</div>';
        }
        ?>
    </div>
</section>