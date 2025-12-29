<?php
$classOptions = getClassOptions(true);
$classNames = array_column($classOptions, 'name');
$departmentOptions = getDepartmentOptions();

$search = trim($_GET['search'] ?? '');
$classFilter = trim($_GET['class'] ?? '');
$departmentFilter = trim($_GET['department'] ?? '');
$memberFilter = strtoupper(trim($_GET['member_id'] ?? ''));
$collaborativeOnly = isset($_GET['collaborative']) && $_GET['collaborative'] === '1';

$stmt = $pdo->query("SELECT * FROM projects ORDER BY date DESC");
$projects = array_map(function ($project) {
    return normalizeProjectRecord($project);
}, $stmt->fetchAll());

$filteredProjects = array_filter($projects, function ($project) use ($search, $classFilter, $departmentFilter, $memberFilter, $collaborativeOnly) {
    if ($search) {
        $haystack = strtolower($project['title'] . ' ' . ($project['short_description'] ?? '') . ' ' . ($project['long_description'] ?? ''));
        if (strpos($haystack, strtolower($search)) === false) {
            return false;
        }
    }
    if ($classFilter && !in_array($classFilter, $project['class_scope'])) {
        return false;
    }
    if ($departmentFilter && !in_array($departmentFilter, $project['department_scope'])) {
        return false;
    }
    if ($collaborativeOnly && !$project['is_collaborative']) {
        return false;
    }
    if ($memberFilter) {
        $matched = false;
        foreach ($project['contributor_ids'] as $id) {
            if (strpos(strtoupper($id), $memberFilter) !== false) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return false;
        }
    }
    return true;
});
?>
<!-- Add JavaScript for real-time filtering -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get filter elements
    const searchInput = document.querySelector('input[name="search"]');
    const classSelect = document.querySelector('select[name="class"]');
    const departmentSelect = document.querySelector('select[name="department"]');
    const memberIdInput = document.querySelector('input[name="member_id"]');
    const collaborativeCheckbox = document.querySelector('input[name="collaborative"]');
    const projectCountElement = document.querySelector('.project-count');
    const projectsContainer = document.querySelector('.projects-grid');
    const noResultsElement = document.querySelector('.no-results');
    
    // Store original projects data for filtering
    let allProjects = <?php echo json_encode($projects); ?>;
    
    // Function to highlight matching member IDs
    function highlightMatchingIds(contributorIds, searchTerm) {
        if (!searchTerm) return '';
        
        const term = searchTerm.toUpperCase();
        const matches = contributorIds.filter(id => 
            id.toUpperCase().includes(term)
        );
        
        if (matches.length === 0) return '';
        
        return `<div class="mt-2 flex flex-wrap gap-1">
            ${matches.map(id => {
                // Highlight the matching part
                const upperId = id.toUpperCase();
                const index = upperId.indexOf(term);
                if (index === -1) return '';
                
                const before = id.substring(0, index);
                const matched = id.substring(index, index + term.length);
                const after = id.substring(index + term.length);
                
                return `<span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 text-xs rounded-full border border-blue-200 dark:border-blue-700 font-medium">
                    ${before}<span class="font-bold">${matched}</span>${after}
                </span>`;
            }).join('')}
        </div>`;
    }
    
    // Function to filter projects in real-time
    function filterProjects() {
        const searchValue = searchInput.value.trim().toLowerCase();
        const classValue = classSelect.value;
        const departmentValue = departmentSelect.value;
        const memberIdValue = memberIdInput.value.trim().toUpperCase();
        const collaborativeValue = collaborativeCheckbox.checked;
        
        // Update URL without reloading
        updateURL(searchValue, classValue, departmentValue, memberIdValue, collaborativeValue);
        
        // Filter projects
        const filtered = allProjects.filter(project => {
            // Search filter
            if (searchValue) {
                const haystack = (
                    project.title.toLowerCase() + ' ' + 
                    (project.short_description || '').toLowerCase() + ' ' + 
                    (project.long_description || '').toLowerCase()
                );
                if (!haystack.includes(searchValue)) {
                    return false;
                }
            }
            
            // Class filter
            if (classValue && !project.class_scope.includes(classValue)) {
                return false;
            }
            
            // Department filter
            if (departmentValue && !project.department_scope.includes(departmentValue)) {
                return false;
            }
            
            // Collaborative filter
            if (collaborativeValue && !project.is_collaborative) {
                return false;
            }
            
            // Member ID filter (partial matching)
            if (memberIdValue) {
                const hasMatch = project.contributor_ids.some(id => 
                    id.toUpperCase().includes(memberIdValue)
                );
                if (!hasMatch) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Update project count
        if (projectCountElement) {
            const countText = filtered.length === 1 ? 'project' : 'projects';
            projectCountElement.textContent = `${filtered.length} ${countText} found`;
            if (memberIdValue) {
                projectCountElement.textContent += ` for member ID containing "${memberIdValue}"`;
            }
        }
        
        // Render filtered projects
        renderProjects(filtered, memberIdValue);
        
        // Show/hide no results message
        if (noResultsElement) {
            if (filtered.length === 0) {
                noResultsElement.style.display = 'block';
                noResultsElement.innerHTML = `
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No projects found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Try adjusting your filters or search keyword.</p>
                    ${memberIdValue ? `<p class="text-sm text-gray-500 dark:text-gray-400">No projects found for member ID containing "${memberIdValue}"</p>` : ''}
                `;
            } else {
                noResultsElement.style.display = 'none';
            }
        }
    }
    
    // Function to update URL parameters
    function updateURL(search, classVal, dept, memberId, collaborative) {
        const url = new URL(window.location);
        const params = url.searchParams;
        
        // Update or remove parameters
        if (search) params.set('search', search);
        else params.delete('search');
        
        if (classVal) params.set('class', classVal);
        else params.delete('class');
        
        if (dept) params.set('department', dept);
        else params.delete('department');
        
        if (memberId) params.set('member_id', memberId);
        else params.delete('member_id');
        
        if (collaborative) params.set('collaborative', '1');
        else params.delete('collaborative');
        
        // Update browser URL without reloading
        history.pushState({}, '', url);
    }
    
    // Function to render projects
    function renderProjects(projects, memberIdSearch = '') {
        if (!projectsContainer) return;
        
        // Clear existing projects
        projectsContainer.innerHTML = '';
        
        if (projects.length === 0) {
            return;
        }
        
        // Add filtered projects
        projects.forEach(project => {
            const imageSrc = project.image ? '/uploads/projects/' + encodeURIComponent(project.image) : 'https://images.pexels.com/photos/271667/pexels-photo-271667.jpeg';
            const projectSummarySource = project.short_description || project.description || '';
            let projectSummary = projectSummarySource;
            
            if (!project.short_description && projectSummarySource.length > 140) {
                projectSummary = projectSummarySource.substring(0, 140) + '...';
            }
            
            // Get matching member IDs for highlighting
            const matchingIdsHtml = highlightMatchingIds(project.contributor_ids, memberIdSearch);
            
            const projectElement = document.createElement('a');
            projectElement.href = '/project/' + encodeURIComponent(project.slug);
            projectElement.className = 'block h-full rounded-2xl hover:shadow-xl transition project-item';
            projectElement.innerHTML = `
                <article class="bg-gray-50 dark:bg-gray-800 rounded-2xl shadow hover:shadow-xl transition overflow-hidden flex flex-col h-full">
                    <img src="${imageSrc}" alt="${project.title.replace(/"/g, '&quot;')}" class="w-full h-40 object-cover">
                    <div class="p-6 flex flex-col flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-xs uppercase tracking-wide text-primary-500 font-semibold">${new Date(project.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</p>
                            ${memberIdSearch ? '<span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-200 text-xs rounded-full">Contains Match</span>' : ''}
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">${project.title.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4 flex-grow">${projectSummary.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>
                        <div class="flex flex-wrap gap-2 mb-4">
                            ${project.class_scope.map(cls => `<span class="px-3 py-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300">${cls.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>`).join('')}
                            ${project.department_scope.map(dept => `<span class="px-3 py-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300">${dept.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>`).join('')}
                            ${project.is_collaborative ? '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Club-wide</span>' : ''}
                        </div>
                        ${matchingIdsHtml}
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mt-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.67 3.137l-1.285.772a1 1 0 01-1.458-.387l-2.055-4.771a1 1 0 01.316-1.152l2.59-1.88a1 1 0 011.366.194l2.366 3.047a1 1 0 01-.274 1.506z"/>
                                </svg>
                                ${project.contributor_ids.length ? project.contributor_ids.length + ' contributor' + (project.contributor_ids.length > 1 ? 's' : '') : 'Open call'}
                            </span>
                            <span class="text-primary-500 font-semibold">View details →</span>
                        </div>
                    </div>
                </article>
            `;
            
            projectsContainer.appendChild(projectElement);
        });
    }
    
    // Event listeners for real-time filtering
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterProjects, 300);
    });
    
    classSelect.addEventListener('change', filterProjects);
    departmentSelect.addEventListener('change', filterProjects);
    
    let memberIdTimeout;
    memberIdInput.addEventListener('input', function() {
        clearTimeout(memberIdTimeout);
        memberIdTimeout = setTimeout(filterProjects, 300);
    });
    
    collaborativeCheckbox.addEventListener('change', filterProjects);
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        searchInput.value = urlParams.get('search') || '';
        classSelect.value = urlParams.get('class') || '';
        departmentSelect.value = urlParams.get('department') || '';
        memberIdInput.value = urlParams.get('member_id') || '';
        collaborativeCheckbox.checked = urlParams.get('collaborative') === '1';
        
        filterProjects();
    });
    
    // Initialize on page load
    filterProjects();
});
</script>

<section class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Projects</h1>
        <p class="text-gray-600 max-w-2xl">Dive into scientific explorations contributed by Intermediate classes, science departments, and collaborative club teams.</p>
    </div>
</section>

<section class="py-12 bg-white dark:bg-gray-900 transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filter Form (now triggers real-time filtering) -->
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 mb-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Project title or keyword" class="w-full px-4 py-2 border rounded-md text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Class</label>
                <select name="class" class="w-full px-4 py-2 border rounded-md text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                    <option value="">All Classes</option>
                    <?php foreach ($classNames as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $classFilter === $class ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <select name="department" class="w-full px-4 py-2 border rounded-md text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                    <option value="">All Departments</option>
                    <?php foreach ($departmentOptions as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Member ID</label>
                <input type="text" name="member_id" value="<?php echo htmlspecialchars($memberFilter); ?>" placeholder="Search by ID (e.g., 001, 0001)" class="w-full px-4 py-2 border rounded-md text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-white uppercase">
                <p class="text-xs text-gray-500 mt-1">Search partial ID like "001" or "BGC-25"</p>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" id="collaborative" name="collaborative" value="1" class="h-4 w-4 text-primary-500" <?php echo $collaborativeOnly ? 'checked' : ''; ?>>
                <label for="collaborative" class="text-sm text-gray-700 dark:text-gray-300 font-semibold">Club-wide only</label>
            </div>
            <div class="md:col-span-2 lg:col-span-4 flex flex-wrap gap-4 mt-4">
                <button type="button" onclick="window.location.href='/projects'" class="px-6 py-2 border border-gray-300 hover:border-primary-500 text-gray-700 hover:text-primary-500 rounded-md transition">Reset Filters</button>
            </div>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 project-count">
            <?php 
            echo count($filteredProjects) . ' project' . (count($filteredProjects) !== 1 ? 's' : '') . ' found';
            if ($memberFilter) {
                echo ' for member ID containing "' . htmlspecialchars($memberFilter) . '"';
            }
            ?>
        </p>

        <!-- Projects Grid Container -->
        <div class="projects-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (!empty($filteredProjects)): ?>
                <?php foreach ($filteredProjects as $project): 
                    $imageSrc = !empty($project['image']) ? '/uploads/projects/' . htmlspecialchars($project['image']) : 'https://images.pexels.com/photos/271667/pexels-photo-271667.jpeg';
                    
                    // Get matching contributor IDs for highlighting
                    $matchingIds = [];
                    if ($memberFilter) {
                        foreach ($project['contributor_ids'] as $id) {
                            if (strpos(strtoupper($id), $memberFilter) !== false) {
                                $matchingIds[] = $id;
                            }
                        }
                    }
                ?>
                    <a href="/project/<?php echo htmlspecialchars($project['slug']); ?>" class="block h-full rounded-2xl hover:shadow-xl transition project-item">
                        <article class="bg-gray-50 dark:bg-gray-800 rounded-2xl shadow hover:shadow-xl transition overflow-hidden flex flex-col h-full">
                            <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-40 object-cover">
                            <div class="p-6 flex flex-col flex-grow">
                                <div class="flex justify-between items-start mb-2">
                                    <p class="text-xs uppercase tracking-wide text-primary-500 font-semibold"><?php echo formatDate($project['date']); ?></p>
                                    <?php if ($memberFilter && !empty($matchingIds)): ?>
                                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-200 text-xs rounded-full">Contains Match</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <?php
                                $projectSummarySource = $project['short_description'] ?: ($project['description'] ?? '');
                                $projectSummary = $projectSummarySource;
                                if (!$project['short_description'] && strlen($projectSummarySource) > 140) {
                                    $projectSummary = substr($projectSummarySource, 0, 140) . '...';
                                }
                                ?>
                                <p class="text-gray-600 dark:text-gray-300 mb-4 flex-grow"><?php echo htmlspecialchars($projectSummary); ?></p>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php foreach ($project['class_scope'] as $classTag): ?>
                                        <span class="px-3 py-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($classTag); ?></span>
                                    <?php endforeach; ?>
                                    <?php foreach ($project['department_scope'] as $deptTag): ?>
                                        <span class="px-3 py-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($deptTag); ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($project['is_collaborative']): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Club-wide</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Display matching member IDs as bubbles -->
                                <?php if ($memberFilter && !empty($matchingIds)): ?>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <?php foreach ($matchingIds as $id): 
                                            $upperId = strtoupper($id);
                                            $upperFilter = strtoupper($memberFilter);
                                            $index = strpos($upperId, $upperFilter);
                                            if ($index !== false):
                                                $before = htmlspecialchars(substr($id, 0, $index));
                                                $matched = htmlspecialchars(substr($id, $index, strlen($memberFilter)));
                                                $after = htmlspecialchars(substr($id, $index + strlen($memberFilter)));
                                        ?>
                                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 text-xs rounded-full border border-blue-200 dark:border-blue-700 font-medium">
                                                <?php echo $before; ?><span class="font-bold"><?php echo $matched; ?></span><?php echo $after; ?>
                                            </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mt-4">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.67 3.137l-1.285.772a1 1 0 01-1.458-.387l-2.055-4.771a1 1 0 01.316-1.152l2.59-1.88a1 1 0 011.366.194l2.366 3.047a1 1 0 01-.274 1.506z"/>
                                        </svg>
                                        <?php $count = count($project['contributor_ids']); echo $count ? $count . ' contributor' . ($count > 1 ? 's' : '') : 'Open call'; ?>
                                    </span>
                                    <span class="text-primary-500 font-semibold">View details →</span>
                                </div>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- No Results Message (hidden by default) -->
        <div class="no-results bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-200 dark:border-gray-700 rounded-2xl p-12 text-center <?php echo empty($filteredProjects) ? '' : 'hidden'; ?>">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No projects found</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Try adjusting your filters or search keyword.</p>
            <?php if ($memberFilter): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">No projects found for member ID containing "<?php echo htmlspecialchars($memberFilter); ?>"</p>
            <?php endif; ?>
        </div>
    </div>
</section>