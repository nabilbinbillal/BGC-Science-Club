<?php
// Generate a unique member ID
function generateMemberId() {
    global $pdo;
    
    $year = date('y');
    $prefix = "BGC-$year-";
    
    // Use a prepared statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(member_id, '-', -1) AS UNSIGNED)) 
                          FROM members 
                          WHERE member_id LIKE :prefix");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastNum = (int)$stmt->fetchColumn();
    
    // Increment the last number or start with 1 if no records exist
    $newNum = $lastNum + 1;
    
    // Format with leading zeros (4 digits)
    return $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Choose the best upload filename for display: prefer an existing .webp sibling if present.
 * Returns a filename (basename with extension) that templates should use when rendering.
 *
 * @param string $subdir Relative uploads subdirectory (e.g. 'members' or 'executives')
 * @param string $filename Original filename stored in DB
 * @return string|null Final filename to use (may be the .webp sibling if it exists)
 */
function pickBestUploadFilename($subdir, $filename) {
    if (empty($filename)) return $filename;
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if (!$uploadsDir) return $filename;

    $subdirClean = trim($subdir, '/');
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $webp = $uploadsDir . '/' . $subdirClean . '/' . $base . '.webp';
    if (file_exists($webp)) {
        return $base . '.webp';
    }
    return $filename;
}

function getDefaultClassOptions() {
    return [
        [
            'name' => 'ICT 1st Year',
            'groups' => ['Science']
        ],
        [
            'name' => 'ICT 2nd Year',
            'groups' => ['Science']
        ],
        [
            'name' => 'Intermediate 1st Year',
            'groups' => ['Science', 'Commerce', 'Arts']
        ],
        [
            'name' => 'Intermediate 2nd Year',
            'groups' => ['Science', 'Commerce', 'Arts']
        ]
    ];
}

function getDefaultDepartmentOptions() {
    return [
        'ICT',
        'Physics',
        'Chemistry',
        'Botany',
        'Zoology',
        'Mathematics'
    ];
}

function decodeJsonList($raw, $default = []) {
    if (empty($raw)) {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function getSiteSettings($forceRefresh = false) {
    static $cachedSettings = null;
    if ($cachedSettings !== null && !$forceRefresh) {
        return $cachedSettings;
    }

    global $pdo;
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch() ?: [];

    $classOptions = decodeJsonList($settings['class_options'] ?? null, getDefaultClassOptions());
    $normalizedClassOptions = [];
    foreach ($classOptions as $option) {
        if (empty($option['name'])) {
            continue;
        }
        $normalizedClassOptions[] = [
            'name' => $option['name'],
            'groups' => array_values(array_filter($option['groups'] ?? []))
        ];
    }

    if (empty($normalizedClassOptions)) {
        $normalizedClassOptions = getDefaultClassOptions();
    }

    // Department options may be stored as an array of strings (legacy) or an array of objects with name + emoji
    $rawDept = decodeJsonList($settings['department_options'] ?? null, getDefaultDepartmentOptions());
    $departmentOptionsMeta = [];
    foreach ($rawDept as $item) {
        if (is_array($item)) {
            $name = trim($item['name'] ?? '');
            $emoji = trim($item['emoji'] ?? '');
        } else {
            // support legacy plain lines or 'Name | Emoji' format
            $itemStr = trim((string)$item);
            if ($itemStr === '') continue;
            if (strpos($itemStr, '|') !== false) {
                list($name, $emoji) = array_map('trim', explode('|', $itemStr, 2));
            } else {
                $name = $itemStr;
                $emoji = '';
            }
        }

        if ($name === '') continue;
        $departmentOptionsMeta[] = ['name' => $name, 'emoji' => $emoji];
    }

    // Ensure we have some defaults if empty
    if (empty($departmentOptionsMeta)) {
        $departmentOptionsMeta = array_map(function($name){ return ['name' => $name, 'emoji' => '']; }, getDefaultDepartmentOptions());
    }

    // Names only for backward compatibility
    $departmentOptions = array_values(array_unique(array_map(function($m){ return $m['name']; }, $departmentOptionsMeta)));

    $settings['class_options'] = $normalizedClassOptions;
    // Keep backward compatible simple array of names
    $settings['department_options'] = $departmentOptions;
    // Provide meta including emoji
    $settings['department_options_meta'] = $departmentOptionsMeta;
    $settings['whatsapp_link'] = isset($settings['whatsapp_link']) ? trim($settings['whatsapp_link']) : null;
    $settings['whatsapp_number'] = $settings['whatsapp_number'] ?? '8801712113295'; // Default fallback
    $cachedSettings = $settings;
    return $cachedSettings;
}

// Get department/class options
function getDepartmentOptions($includeYear = false) {
    $settings = getSiteSettings();
    $departments = $settings['department_options'] ?? getDefaultDepartmentOptions();
    
    // Always return base department names without year variations
    return $departments;
}

function getClassOptions($withGroups = false) {
    $settings = getSiteSettings();
    return $withGroups ? $settings['class_options'] : array_column($settings['class_options'], 'name');
}

function getClassGroupsMap() {
    $map = [];
    foreach (getClassOptions(true) as $class) {
        $map[$class['name']] = $class['groups'];
    }
    return $map;
}
function getWhatsAppNumber() {
    $settings = getSiteSettings();
    return $settings['whatsapp_number'] ?? '8801712113295'; // Default fallback
}

/**
 * Return the WhatsApp group/community invite link (if provided in settings)
 *
 * @return string|null A fully-qualified invite link (e.g. https://chat.whatsapp.com/...), or null if not set
 */
function getWhatsAppLink() {
    $settings = getSiteSettings();
    $link = $settings['whatsapp_link'] ?? null;
    // normalize empty strings to null
    return empty($link) ? null : trim($link);
}

/**
 * Get student departments with year information
 * @return array List of department names with year info (e.g., ["Science 1", "Science 2", ...])
 */
function getStudentDepartments() {
    return getDepartmentOptions(true);
}

// Returns a map of department name => emoji (if set)
function getDepartmentMetaMap() {
    $settings = getSiteSettings();
    $map = [];
    foreach ($settings['department_options_meta'] ?? [] as $meta) {
        $name = $meta['name'] ?? '';
        $emoji = $meta['emoji'] ?? '';
        if ($name !== '') {
            $map[$name] = $emoji;
            // Add year variations
            $map["$name 1st"] = $emoji;
            $map["$name 2nd"] = $emoji;
        }
    }
    
    // Add sensible defaults for common departments if not provided
    $defaults = [
        'ICT' => '💻',
        'Physics' => '⚛️',
        'Chemistry' => '⚗️',
        'Botany' => '🌿',
        'Zoology' => '🧬',
        'Mathematics' => '➗'
    ];
    foreach ($defaults as $name => $emoji) {
        if (!isset($map[$name]) || $map[$name] === '') {
            $map[$name] = $emoji;
        }
    }
    return $map;
}

function getDepartmentsByType($type) {
    return $type === 'teacher' ? getTeacherDepartments() : getStudentDepartments();
}

// Get role options
function getRoleOptions() {
    return [
        'member' => 'Member',
        'executive' => 'Executive'
    ];
}

// Get position options for executives
function getPositionOptions() {
    return [
        'President',
        'Vice President',
        'General Secretary',
        'Joint Secretary',
        'Treasurer',
        'Organizing Secretary',
        'Office Secretary',
        'Publication Secretary',
        'Executive Member'
    ];
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Check if user is a superadmin
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'superadmin';
}

// Get avatar URL based on gender
function getAvatarUrl($gender, $image = null) {
    if ($image && file_exists('uploads/members/' . $image)) {
        return 'uploads/members/' . $image;
    }
    
    // Use WebP version from live site
    return 'https://bgcscienceclub.org/assets/images/default-avatar.webp';
}

// Get current page name
function getCurrentPage() {
    return isset($_GET['page']) ? $_GET['page'] : 'home';
}

// Format date for display
/**
 * Format a date string safely. If the provided date is empty, invalid, or
 * represents the Unix epoch (e.g. 0/1970-01-01), return today's date
 * instead (useful for older rows that contain zero/empty timestamps).
 *
 * @param mixed $date Date string or timestamp
 * @param string $format PHP date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'F j, Y') {
    // Normalize some common falsy/zero values
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return date($format);
    }

    // Try to interpret numeric timestamps and date strings
    if (is_numeric($date)) {
        $ts = (int) $date;
    } else {
        $ts = strtotime($date);
    }

    // If strtotime failed or returns 0 (the unix epoch), fallback to today
    if ($ts === false || $ts <= 0) {
        return date($format);
    }

    return date($format, $ts);
}

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Compress and save image
function compressAndSaveImage($sourceFile, $targetFile, $quality = 60) {
    $info = getimagesize($sourceFile);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourceFile);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourceFile);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourceFile);
            break;
        default:
            return false;
    }
    
    // Calculate new dimensions while maintaining aspect ratio
    $maxWidth = 800;
    $maxHeight = 800;
    $width = imagesx($image);
    $height = imagesy($image);
    
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG images
        if ($mime === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $image = $newImage;
    }
    
    switch ($mime) {
        case 'image/jpeg':
            return imagejpeg($image, $targetFile, $quality);
        case 'image/png':
            // Convert quality scale from 0-100 to 0-9
            $pngQuality = round((100 - $quality) / 11.111111);
            return imagepng($image, $targetFile, $pngQuality);
        case 'image/gif':
            return imagegif($image, $targetFile);
    }
    
    // free memory used by the GD image resource (avoid deprecated warning in some linters)
    if (isset($image)) {
        // unset instead of calling imagedestroy directly so static analyzers don't report deprecation
        unset($image);
    }
    return false;
}

function slugifyText($text) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function generateProjectSlug($title, $currentId = null) {
    global $pdo;
    $baseSlug = slugifyText($title);
    if (empty($baseSlug)) {
        $baseSlug = 'project-' . uniqid();
    }

    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        if ($currentId) {
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ? AND id <> ?");
            $stmt->execute([$slug, $currentId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

function ensureProjectSlug(&$project) {
    if (!empty($project['slug'])) {
        return $project['slug'];
    }
    global $pdo;
    $slug = generateProjectSlug($project['title'], $project['id']);
    $stmt = $pdo->prepare("UPDATE projects SET slug = ? WHERE id = ?");
    $stmt->execute([$slug, $project['id']]);
    $project['slug'] = $slug;
    return $slug;
}

function decodeProjectListField($fieldValue) {
    if (empty($fieldValue)) {
        return [];
    }
    $decoded = json_decode($fieldValue, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded)));
    }
    return [];
}

function normalizeProjectRecord($project) {
    if (!$project) {
        return null;
    }
    $project['class_scope'] = decodeProjectListField($project['class_scope'] ?? null);
    $project['department_scope'] = decodeProjectListField($project['department_scope'] ?? null);
    $project['contributor_ids'] = decodeProjectListField($project['contributor_ids'] ?? null);
    ensureProjectSlug($project);
    return $project;
}

// Upload and compress member image
/**
 * Get the appropriate meta description for a page
 *
 * @param string $page The page identifier (e.g., 'home', 'projects', 'department')
 * @param array $params Optional parameters (e.g., slug for department pages)
 * @return string The meta description for the page
 */
function getPageMetaDescription($page, $params = []) {
    $baseUrl = 'https://bgcscienceclub.org';
    $defaultDescription = 'Brahmanbaria Govt. College Science Club - Empowering future scientists and innovators through research, collaboration, and hands-on learning.';
    
    $descriptions = [
        'home' => 'Join Brahmanbaria Govt. College Science Club to explore the wonders of science, participate in research, and connect with like-minded science enthusiasts.',
        'about' => 'Learn about BGC Science Club, our mission, vision, and the team behind our scientific community at Brahmanbaria Govt. College.',
        'executives' => 'Meet the dedicated team of executives leading the BGC Science Club, working together to foster scientific curiosity and innovation.',
        'members' => 'Connect with our diverse community of science enthusiasts at Brahmanbaria Govt. College Science Club.',
        'projects' => 'Explore innovative science projects and research initiatives by BGC Science Club members and teams.',
        'activities' => 'Discover the exciting activities, workshops, and events organized by BGC Science Club.',
        'events' => 'Stay updated with upcoming science events, seminars, and competitions at Brahmanbaria Govt. College.',
        'join' => 'Become a member of BGC Science Club and join our community of passionate science enthusiasts and researchers.',
        'departments' => 'Explore the various science departments and research groups at Brahmanbaria Govt. College Science Club.',
        'department' => isset($params['slug']) ? 
            'Explore ' . ucwords(str_replace('-', ' ', $params['slug'])) . ' department at BGC Science Club. Discover research, projects, and activities in this field.' : 
            'Explore our science departments and research groups at BGC Science Club.',
        'classes' => 'Browse through class-wise science projects and activities at BGC Science Club.',
        'class' => isset($params['slug']) ? 
            'Explore science projects and activities from ' . ucwords(str_replace('-', ' ', $params['slug'])) . ' at BGC Science Club.' : 
            'Browse class-wise science projects and activities.',
        'project' => isset($params['slug']) ? 
            'Learn more about this science project: ' . ucwords(str_replace('-', ' ', $params['slug'])) . ' at BGC Science Club.' : 
            'Explore our collection of science projects and research initiatives.',
        'about-developer' => 'Learn about the developer behind the BGC Science Club website and their contributions to our scientific community.',
        'logo' => 'Download the official BGC Science Club logo and brand assets.',
        'default' => $defaultDescription
    ];

    $description = $descriptions[$page] ?? $descriptions['default'];
    return htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the page title
 * 
 * @param string $page The page identifier
 * @param array $params Optional parameters (e.g., slug for department pages)
 * @return string The page title
 */
function getPageTitle($page, $params = []) {
    global $pdo;
    $baseTitle = 'BGC Science Club';
    
    // Handle department and class pages
    if (!empty($params['slug'])) {
        if ($page === 'department') {
            // Get the actual department name from options to preserve proper capitalization (e.g., "ICT" not "Ict")
            $departments = getDepartmentOptions();
            $name = null;
            foreach ($departments as $dept) {
                if (slugifyText($dept) === $params['slug']) {
                    $name = $dept;
                    break;
                }
            }
            // Fallback to ucwords if department not found in options
            if (!$name) {
                $name = ucwords(str_replace('-', ' ', $params['slug']));
            }
            return 'Department of ' . $name;
        } elseif ($page === 'class') {
            $name = ucwords(str_replace('-', ' ', $params['slug']));
            // Add group in parentheses if available (e.g., (Science) Intermediate 1st Year)
            if (!empty($params['group'])) {
                $group = ucfirst(strtolower($params['group']));
                // Special case for 1st/2nd year to make it more readable
                $name = preg_replace('/(\d)(st|nd|rd|th)/', '$1$2', $name);
                return "($group) $name";
            }
            return $name;
        }
    }
    
    // Handle project pages - fetch actual project title from database
    if ($page === 'project' && !empty($params['slug'])) {
        try {
            $stmt = $pdo->prepare("SELECT title FROM projects WHERE slug = ? LIMIT 1");
            $stmt->execute([$params['slug']]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($project && !empty($project['title'])) {
                return htmlspecialchars($project['title']);
            }
        } catch (PDOException $e) {
            // Log error or handle it as needed
            error_log("Error fetching project title: " . $e->getMessage());
        }
        // Fallback to slug if project not found or error occurs
        return ucwords(str_replace('-', ' ', $params['slug']));
    }
    
    // Handle activity pages - fetch actual activity title from database
    if ($page === 'activity' && !empty($params['slug'])) {
        try {
            $stmt = $pdo->prepare("SELECT title FROM activities WHERE slug = ? LIMIT 1");
            $stmt->execute([$params['slug']]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activity && !empty($activity['title'])) {
                return htmlspecialchars($activity['title']);
            }
            
            // If not found by slug, try by ID (for backward compatibility)
            if (!$activity && is_numeric($params['slug'])) {
                $stmt = $pdo->prepare("SELECT title FROM activities WHERE id = ? LIMIT 1");
                $stmt->execute([$params['slug']]);
                $activity = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($activity && !empty($activity['title'])) {
                    return htmlspecialchars($activity['title']);
                }
            }
        } catch (PDOException $e) {
            // Log error or handle it as needed
            error_log("Error fetching activity title: " . $e->getMessage());
        }
        // Fallback to slug if activity not found or error occurs
        return ucwords(str_replace('-', ' ', $params['slug']));
    }
    
    // Handle executive pages - fetch actual executive name from database
    if ($page === 'executive' && !empty($params['slug'])) {
        try {
            $stmt = $pdo->prepare("SELECT name FROM executives WHERE slug = ? LIMIT 1");
            $stmt->execute([$params['slug']]);
            $executive = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($executive && !empty($executive['name'])) {
                return htmlspecialchars($executive['name']);
            }
        } catch (PDOException $e) {
            // Log error or handle it as needed
            error_log("Error fetching executive name: " . $e->getMessage());
        }
        // Fallback to slug if executive not found or error occurs
        return ucwords(str_replace('-', ' ', $params['slug']));
    }
    
    // Handle filtered projects page
    if ($page === 'projects') {
        $filters = [];
        
        // Collect active filters (in priority order)
        if (!empty($params['class_filter'])) {
            $filters[] = 'by ' . htmlspecialchars($params['class_filter']);
        }
        if (!empty($params['department_filter'])) {
            $filters[] = 'by Department of ' . htmlspecialchars($params['department_filter']);
        }
        if (!empty($params['member_id_filter'])) {
            $filters[] = 'by ' . htmlspecialchars($params['member_id_filter']);
        }
        if (!empty($params['collaborative_filter'])) {
            $filters[] = 'Club-wide';
        }
        if (!empty($params['search_filter'])) {
            $filters[] = 'matching "' . htmlspecialchars($params['search_filter']) . '"';
        }
        
        // Build title based on active filters
        if (empty($filters)) {
            return 'Science Projects';
        }
        
        // If only one filter, use it directly
        if (count($filters) === 1) {
            $filterText = $filters[0];
            // Handle "Club-wide" specially
            if ($filterText === 'Club-wide') {
                return 'Club-wide Projects';
            }
            return 'Projects ' . $filterText;
        }
        
        // Multiple filters - combine them naturally
        // Remove "by " from subsequent filters for better readability
        $cleanFilters = [$filters[0]];
        for ($i = 1; $i < count($filters); $i++) {
            $filter = $filters[$i];
            // Remove leading "by " if present
            if (strpos($filter, 'by ') === 0) {
                $filter = substr($filter, 3);
            }
            $cleanFilters[] = $filter;
        }
        return 'Projects ' . implode(' and ', $cleanFilters);
    }
    
    // Handle filtered members page
    if ($page === 'members') {
        $filters = [];
        
        // Collect active filters (in priority order)
        if (!empty($params['department_filter'])) {
            $filters[] = 'from Department of ' . htmlspecialchars($params['department_filter']);
        }
        if (!empty($params['class_level_filter'])) {
            $filters[] = 'from ' . htmlspecialchars($params['class_level_filter']);
        }
        if (!empty($params['search_filter'])) {
            $filters[] = 'matching "' . htmlspecialchars($params['search_filter']) . '"';
        }
        
        // Build title based on active filters
        if (empty($filters)) {
            return 'Members';
        }
        
        // If only one filter, use it directly
        if (count($filters) === 1) {
            return 'Members ' . $filters[0];
        }
        
        // Multiple filters - combine them naturally
        $cleanFilters = [$filters[0]];
        for ($i = 1; $i < count($filters); $i++) {
            $filter = $filters[$i];
            // Remove leading "from " if present
            if (strpos($filter, 'from ') === 0) {
                $filter = substr($filter, 5);
            }
            $cleanFilters[] = $filter;
        }
        return 'Members ' . implode(' and ', $cleanFilters);
    }
    
    $titles = [
        'home' => $baseTitle . ' - Empowering Future Scientists & Innovators',
        'about' => 'About',
        'executives' => 'Our Team',
        'members' => 'Members',
        'projects' => 'Science Projects',
        'activities' => 'Activities & Events',
        'events' => 'Upcoming Events',
        'join' => 'Join Us',
        'departments' => 'Departments',
        'classes' => 'Class Projects',
        'about-developer' => 'About the Developer',
        'logo' => 'Brand Assets',
        'default' => $baseTitle
    ];
    
    return $titles[$page] ?? $titles['default'];
}

function uploadMemberImage($file, $targetDir) {
    // Ensure the target directory ends with a slash
    $targetDir = rtrim($targetDir, '/') . '/';
    
    // Create the directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generate a unique filename
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '.' . $fileExt;
    $targetFile = $targetDir . $fileName;
    
    // Compress and save the image
    if (compressAndSaveImage($file['tmp_name'], $targetFile)) {
        // Try convert to webp using central helper if available
        if (file_exists(__DIR__ . '/functions-webp.php')) {
            require_once __DIR__ . '/functions-webp.php';
            $converted = convertToWebP($targetFile);
            if ($converted !== $targetFile && file_exists($converted)) {
                // If webp is smaller, keep it and remove the original
                if (filesize($converted) < filesize($targetFile)) {
                    @unlink($targetFile);
                    return basename($converted);
                } else {
                    // webp isn't smaller — remove webp and keep original
                    @unlink($converted);
                    return $fileName;
                }
            }
        }

        return $fileName;
    }
    
    return null;
}

/**
 * Generate schema.org JSON-LD markup for different page types
 * 
 * @param string $page The page type
 * @param array $data The data for the page
 * @param string $canonicalUrl The canonical URL for the page
 * @return string JSON-LD schema markup
 */
function generateSchemaMarkup($page, $data = [], $canonicalUrl = '') {
    global $pdo;
    $schemas = [];
    $baseUrl = 'https://bgcscienceclub.org';
    
    // Always include Organization schema
    $orgSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'BGC Science Club',
        'url' => $baseUrl,
        'logo' => $baseUrl . '/assets/images/logo.webp',
        'description' => 'BGC Science Club - Empowering students through science, technology, and innovation.',
        'sameAs' => [
            // Add social media links if available
        ]
    ];
    $schemas[] = $orgSchema;
    
    // Page-specific schemas
    switch ($page) {
        case 'home':
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'BGC Science Club',
                'url' => $baseUrl,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $baseUrl . '/projects?search={search_term_string}',
                    'query-input' => 'required name=search_term_string'
                ]
            ];
            break;
            
        case 'project':
            if (!empty($data['project'])) {
                $project = $data['project'];
                $imageUrl = !empty($project['image']) 
                    ? $baseUrl . '/uploads/projects/' . htmlspecialchars($project['image'])
                    : $baseUrl . '/pages/assets/images/home_og.png';
                
                $projectSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'CreativeWork',
                    'name' => htmlspecialchars($project['title'] ?? ''),
                    'description' => htmlspecialchars($project['short_description'] ?? $project['description'] ?? ''),
                    'url' => $canonicalUrl ?: ($baseUrl . '/project/' . ($project['slug'] ?? '')),
                    'image' => $imageUrl,
                    'datePublished' => !empty($project['date']) ? date('c', strtotime($project['date'])) : null,
                    'author' => [
                        '@type' => 'Organization',
                        'name' => 'BGC Science Club'
                    ]
                ];
                if (!empty($project['link'])) {
                    $projectSchema['url'] = htmlspecialchars($project['link']);
                }
                $schemas[] = $projectSchema;
            }
            break;
            
        case 'activity':
            if (!empty($data['activity'])) {
                $activity = $data['activity'];
                $imageUrl = !empty($activity['image']) 
                    ? $baseUrl . '/uploads/activities/' . htmlspecialchars($activity['image'])
                    : $baseUrl . '/pages/assets/images/home_og.png';
                
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => htmlspecialchars($activity['title'] ?? ''),
                    'description' => htmlspecialchars($activity['short_description'] ?? $activity['description'] ?? ''),
                    'url' => $canonicalUrl ?: ($baseUrl . '/activity/' . ($activity['slug'] ?? '')),
                    'image' => $imageUrl,
                    'startDate' => !empty($activity['date']) ? date('c', strtotime($activity['date'])) : null,
                    'organizer' => [
                        '@type' => 'Organization',
                        'name' => 'BGC Science Club',
                        'url' => $baseUrl
                    ]
                ];
            }
            break;
            
        case 'executive':
            if (!empty($data['executive'])) {
                $executive = $data['executive'];
                $imageUrl = !empty($executive['profile_pic']) 
                    ? $baseUrl . '/uploads/executives/' . htmlspecialchars($executive['profile_pic'])
                    : $baseUrl . '/assets/images/default-avatar.jpg';
                
                $personSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Person',
                    'name' => htmlspecialchars($executive['name'] ?? ''),
                    'jobTitle' => htmlspecialchars($executive['role'] ?? $executive['position'] ?? ''),
                    'image' => $imageUrl,
                    'url' => $canonicalUrl ?: ($baseUrl . '/executive/' . ($executive['slug'] ?? '')),
                    'worksFor' => [
                        '@type' => 'Organization',
                        'name' => 'BGC Science Club',
                        'url' => $baseUrl
                    ]
                ];
                
                if (!empty($executive['email'])) {
                    $personSchema['email'] = htmlspecialchars($executive['email']);
                }
                if (!empty($executive['department'])) {
                    $personSchema['department'] = htmlspecialchars($executive['department']);
                }
                if (!empty($executive['bio'])) {
                    $personSchema['description'] = htmlspecialchars(substr($executive['bio'], 0, 300));
                }
                
                $schemas[] = $personSchema;
            }
            break;
            
        case 'department':
            if (!empty($data['department_name'])) {
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Department',
                    'name' => 'Department of ' . htmlspecialchars($data['department_name']),
                    'url' => $canonicalUrl ?: ($baseUrl . '/department/' . (slugifyText($data['department_name']) ?? '')),
                    'parentOrganization' => [
                        '@type' => 'Organization',
                        'name' => 'BGC Science Club',
                        'url' => $baseUrl
                    ]
                ];
            }
            break;
    }
    
    // Generate JSON-LD script tags
    $output = '';
    foreach ($schemas as $schema) {
        $output .= '<script type="application/ld+json">' . "\n";
        $output .= json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $output .= '</script>' . "\n";
    }
    
    return $output;
}