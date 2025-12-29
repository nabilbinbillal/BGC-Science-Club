<?php
// Get available department/class options for the member add form (include classes too)
$departments = getStudentDepartments();
$departmentOptions = array_combine($departments, $departments);

// Get department filter
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : 'all';

// Pagination settings
$perPage = 10;
$currentPagePending = isset($_GET['page_pending']) ? max(1, intval($_GET['page_pending'])) : 1;
$currentPageApproved = isset($_GET['page_approved']) ? max(1, intval($_GET['page_approved'])) : 1;
$offsetPending = ($currentPagePending - 1) * $perPage;
$offsetApproved = ($currentPageApproved - 1) * $perPage;

// Get search term if any
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Check if view parameter is set for showing member details
$viewMemberId = isset($_GET['view']) ? intval($_GET['view']) : null;

// Function to normalize date formats
function normalizeDate($dateString) {
    $formats = ['d-m-Y', 'd/m/Y', 'm-d-Y', 'm/d/Y', 'Y-m-d', 'Y/m/d'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateString);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    return null;
}

// Handle member actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;
        
        switch ($_POST['action']) {
            case 'approve':
                try {
                    $stmt = $pdo->prepare("UPDATE members SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$memberId]);
                    $success = "Member approved successfully";
                } catch (PDOException $e) {
                    $error = "An error occurred while approving the member";
                }
                break;
                
            case 'delete':
                try {
                    // Get member details before deletion
                    $stmt = $pdo->prepare("SELECT image, id_card_image FROM members WHERE id = ?");
                    $stmt->execute([$memberId]);
                    $member = $stmt->fetch();
                    
                    // Delete member from database
                    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
                    $stmt->execute([$memberId]);
                    
                    // Delete associated images from storage if member data was found
                    if ($member !== false) {
                        $uploadDir = '../../uploads/members/';
                        if (!empty($member['image']) && file_exists($uploadDir . $member['image'])) {
                            unlink($uploadDir . $member['image']);
                        }
                        if (!empty($member['id_card_image']) && file_exists($uploadDir . $member['id_card_image'])) {
                            unlink($uploadDir . $member['id_card_image']);
                        }
                    }
                    
                    $success = "Member and associated images deleted successfully";
                } catch (PDOException $e) {
                    $error = "An error occurred while deleting the member";
                }
                break;
        }
    } else {
        // Add/Edit member
        $name = sanitize($_POST['name']);
        $department = sanitize($_POST['department']);
        $roll_no = sanitize($_POST['roll_no']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $gender = sanitize($_POST['gender']);
        $memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;
        
        // Generate unique member ID if new member
        if (!$memberId) {
            $uniqueId = generateMemberId();
        }
        
        // Handle profile picture upload with compression
        $profile_pic = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $uploadDir = '../uploads/members';
            $profile_pic = uploadMemberImage($_FILES['profile_pic'], $uploadDir);
        }
        
        // Handle ID card image upload with compression
        $id_card_image = null;
        if (isset($_FILES['id_card_image']) && $_FILES['id_card_image']['error'] === 0) {
            $uploadDir = '../uploads/members';
            $id_card_image = uploadMemberImage($_FILES['id_card_image'], $uploadDir);
        }
        
        try {
            if ($memberId) {
                // Update existing member
                $sql = "UPDATE members SET name = ?, department = ?, roll_no = ?, email = ?, phone = ?, gender = ?";
                $params = [$name, $department, $roll_no, $email, $phone, $gender];
                
                if ($profile_pic) {
                    $sql .= ", image = ?";
                    $params[] = $profile_pic;
                }
                
                if ($id_card_image) {
                    $sql .= ", id_card_image = ?";
                    $params[] = $id_card_image;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $memberId;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $success = "Member updated successfully";
            } else {
                // Create new member
                $stmt = $pdo->prepare("INSERT INTO members (member_id, name, department, roll_no, email, phone, gender, image, id_card_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$uniqueId, $name, $department, $roll_no, $email, $phone, $gender, $profile_pic, $id_card_image]);
                
                $success = "Member added successfully";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again.";
        }
    }
    
    // Redirect to maintain proper URL structure
    $redirectUrl = "/admin/index.php?page=members";
    if ($departmentFilter !== 'all') $redirectUrl .= "&department=" . urlencode($departmentFilter);
    if (!empty($searchTerm)) $redirectUrl .= "&search=" . urlencode($searchTerm);
    $redirectUrl .= "&page_pending=$currentPagePending&page_approved=$currentPageApproved";
    header("Location: $redirectUrl");
    exit();
}

// Get pending requests with pagination and search
$pendingSql = "SELECT * FROM members WHERE status = 'pending'";
$pendingParams = [];
$pendingCountSql = "SELECT COUNT(*) FROM members WHERE status = 'pending'";

if ($departmentFilter !== 'all') {
    $pendingSql .= " AND department = ?";
    $pendingCountSql .= " AND department = ?";
    $pendingParams[] = $departmentFilter;
}

if (!empty($searchTerm)) {
    $normalizedDate = normalizeDate($searchTerm);
    
    $searchConditions = [];
    $searchParams = [];
    
    $searchFields = ['name', 'member_id', 'department', 'roll_no', 'email', 'phone'];
    foreach ($searchFields as $field) {
        $searchConditions[] = "$field LIKE ?";
        $searchParams[] = "%$searchTerm%";
    }
    
    if ($normalizedDate) {
        $searchConditions[] = "DATE(created_at) = ?";
        $searchParams[] = $normalizedDate;
    }
    
    $pendingSql .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $pendingCountSql .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $pendingParams = array_merge($pendingParams, $searchParams);
}

$pendingSql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

// Execute count query
$stmtCount = $pdo->prepare($pendingCountSql);
$stmtCount->execute($pendingParams);
$totalPending = $stmtCount->fetchColumn();
$totalPagesPending = ceil($totalPending / $perPage);

// Execute main query
$stmt = $pdo->prepare($pendingSql);
foreach ($pendingParams as $index => $param) {
    $stmt->bindValue($index + 1, $param);
}
$stmt->bindValue(count($pendingParams) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($pendingParams) + 2, $offsetPending, PDO::PARAM_INT);
$stmt->execute();
$pendingMembers = $stmt->fetchAll();

// Get approved members with department filter and pagination
$approvedSql = "SELECT * FROM members WHERE status = 'approved'";
$approvedParams = [];
$approvedCountSql = "SELECT COUNT(*) FROM members WHERE status = 'approved'";

if ($departmentFilter !== 'all') {
    $approvedSql .= " AND department = ?";
    $approvedCountSql .= " AND department = ?";
    $approvedParams[] = $departmentFilter;
}

if (!empty($searchTerm)) {
    $normalizedDate = normalizeDate($searchTerm);
    
    $searchConditions = [];
    $searchParams = [];
    
    $searchFields = ['name', 'member_id', 'department', 'roll_no', 'email', 'phone'];
    foreach ($searchFields as $field) {
        $searchConditions[] = "$field LIKE ?";
        $searchParams[] = "%$searchTerm%";
    }
    
    if ($normalizedDate) {
        $searchConditions[] = "DATE(created_at) = ?";
        $searchParams[] = $normalizedDate;
        $searchConditions[] = "DATE(updated_at) = ?";
        $searchParams[] = $normalizedDate;
    }
    
    $approvedSql .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $approvedCountSql .= " AND (" . implode(" OR ", $searchConditions) . ")";
    $approvedParams = array_merge($approvedParams, $searchParams);
}

$approvedSql .= " ORDER BY name ASC LIMIT ? OFFSET ?";

// Execute count query
$stmtCount = $pdo->prepare($approvedCountSql);
$stmtCount->execute($approvedParams);
$totalApproved = $stmtCount->fetchColumn();
$totalPagesApproved = ceil($totalApproved / $perPage);

// Execute main query
$stmt = $pdo->prepare($approvedSql);
foreach ($approvedParams as $index => $param) {
    $stmt->bindValue($index + 1, $param);
}
$stmt->bindValue(count($approvedParams) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($approvedParams) + 2, $offsetApproved, PDO::PARAM_INT);
$stmt->execute();
$approvedMembers = $stmt->fetchAll();

// Get unique departments for filter
$deptStmt = $pdo->query("SELECT DISTINCT department FROM members ORDER BY department");
$availableDepartments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch member details for view parameter if set
$viewMember = null;
if ($viewMemberId) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$viewMemberId]);
    $viewMember = $stmt->fetch();
}
?>

<!-- Add Member Button -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Members Management</h2>
    <div class="space-x-2">
        <button id="showAddMemberForm" class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Member
        </button>
    </div>
</div>

<!-- Department Filter -->
<div class="mb-6 max-w-xs">
    <form method="GET" action="/admin/index.php" class="flex space-x-2">
        <input type="hidden" name="page" value="members">
        <input type="hidden" name="page_pending" value="<?= $currentPagePending ?>">
        <input type="hidden" name="page_approved" value="<?= $currentPageApproved ?>">
        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
        <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="all" <?= $departmentFilter == 'all' ? 'selected' : '' ?>>All Departments</option>
            <?php foreach ($availableDepartments as $dept): ?>
                <option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter == $dept ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600">Filter</button>
    </form>
</div>

<!-- Search Form -->
<div class="mb-6">
    <form method="GET" action="/admin/index.php" class="flex space-x-2">
        <input type="hidden" name="page" value="members">
        <input type="hidden" name="page_pending" value="<?= $currentPagePending ?>">
        <input type="hidden" name="page_approved" value="<?= $currentPageApproved ?>">
        <input type="hidden" name="department" value="<?= htmlspecialchars($departmentFilter) ?>">
        
        <div class="relative flex-1">
            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                   placeholder="Search by ID, Name, Department, or Date (dd-mm-yyyy)" 
                   class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary-500">
                <i class="fas fa-search"></i>
            </button>
        </div>
        
        <button type="button" onclick="window.location.href='/admin/index.php?page=members'" 
                class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded">
            Clear
        </button>
    </form>
</div>

<!-- Member Form (Show if adding or editing) -->
<div id="memberFormContainer" class="mb-8<?= isset($_GET['edit']) ? '' : ' hidden' ?>">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <?= isset($_GET['edit']) ? 'Edit Member' : 'Add New Member' ?>
            </h2>
            
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $success ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php
                if (isset($_GET['edit'])) {
                    $editId = $_GET['edit'];
                    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
                    $stmt->execute([$editId]);
                    $member = $stmt->fetch();
                }
                ?>
                
                <?php if (isset($member)): ?>
                <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="name">
                            Name
                        </label>
                        <input type="text" id="name" name="name" required
                            value="<?= isset($member) ? htmlspecialchars($member['name']) : '' ?>"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="department">
                            Department/Class
                        </label>
                        <select id="department" name="department" required 
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out" onchange="toggleGroupField(this.value)">
                            <option value="">Select Department/Class</option>
                            <?php foreach ($departmentOptions as $key => $value): ?>
                            <option value="<?= $key ?>" <?= (isset($member) && $member['department'] === $key) ? 'selected' : '' ?>>
                                <?= $value ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="groupField" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="group_name">
                            Group
                        </label>
                        <select id="group_name" name="group_name"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                            <option value="">Select Group</option>
                            <option value="Science" <?= (isset($member) && $member['group_name'] === 'Science') ? 'selected' : '' ?>>Science</option>
                            <option value="Commerce" <?= (isset($member) && $member['group_name'] === 'Commerce') ? 'selected' : '' ?>>Commerce</option>
                            <option value="Arts" <?= (isset($member) && $member['group_name'] === 'Arts') ? 'selected' : '' ?>>Arts</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="roll_no">
                            Roll Number
                        </label>
                        <input type="text" id="roll_no" name="roll_no" required
                            value="<?= isset($member) ? htmlspecialchars($member['roll_no']) : '' ?>"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="email">
                            Email
                        </label>
                        <input type="email" id="email" name="email" required
                            value="<?= isset($member) ? htmlspecialchars($member['email']) : '' ?>"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="phone">
                            Phone
                        </label>
                        <input type="tel" id="phone" name="phone" required
                            value="<?= isset($member) ? htmlspecialchars($member['phone']) : '' ?>"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="gender">
                            Gender
                        </label>
                        <select id="gender" name="gender" required 
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                            <option value="">Select Gender</option>
                            <option value="male" <?= (isset($member) && $member['gender'] === 'male') ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (isset($member) && $member['gender'] === 'female') ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="profile_pic">
                            Profile Picture
                        </label>
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="id_card_image">
                            ID Card Image
                        </label>
                        <input type="file" id="id_card_image" name="id_card_image" accept="image/*"
                            class="w-full px-4 py-2 rounded-md border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <a href="/admin/index.php?page=members" id="cancelAddMember"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        Cancel
                    </a>
                    <button type="submit"
                        class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        <?= isset($member) ? 'Update Member' : 'Add Member' ?>
                    </button>
                </div>
            </form>
            
            <script>
            // Function to toggle group field based on selected class
            function toggleGroupField(selectedValue) {
                const groupField = document.getElementById('groupField');
                const groupSelect = document.getElementById('group_name');
                
                // Check if the selected value is an intermediate class
                if (selectedValue.includes('Intermediate')) {
                    groupField.classList.remove('hidden');
                    groupSelect.required = true;
                } else {
                    groupField.classList.add('hidden');
                    groupSelect.required = false;
                    groupSelect.value = ''; // Clear the value when hidden
                }
            }
            
            // Initialize the group field visibility on page load
            document.addEventListener('DOMContentLoaded', function() {
                const departmentSelect = document.getElementById('department');
                if (departmentSelect) {
                    // Trigger the change event to set initial state
                    toggleGroupField(departmentSelect.value);
                    
                    // Also handle the case when editing an existing member
                    <?php if (isset($member) && $member['group_name'] && strpos($member['department'], 'Intermediate') !== false): ?>
                    document.getElementById('groupField').classList.remove('hidden');
                    <?php endif; ?>
                }
            });
            </script>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-8">
    <!-- Pending Requests -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Pending Requests</h2>
            
            <?php if (!empty($searchTerm) && count($pendingMembers) === 0): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <p>No pending members found matching your search criteria.</p>
            </div>
            <?php endif; ?>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Roll No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($pendingMembers as $member): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if (isset($member['image']) && $member['image']): ?>
                                    <img src="../uploads/members/<?= $member['image'] ?>" 
                                        alt="<?= htmlspecialchars($member['name']) ?>"
                                        class="h-10 w-10 rounded-full">
                                    <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($member['name']) ?>&background=random" 
                                        alt="<?= htmlspecialchars($member['name']) ?>"
                                        class="h-10 w-10 rounded-full">
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($member['name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($member['email']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($member['department']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($member['roll_no']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button" onclick="showMemberDetails(<?= htmlspecialchars(json_encode($member)) ?>)" 
                                    class="text-blue-500 hover:text-blue-600 mr-3">
                                    View
                                </button>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="text-green-500 hover:text-green-600">Approve</button>
                                </form>
                                <span class="px-2 text-gray-400">|</span>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="text-red-500 hover:text-red-600"
                                        onclick="return confirm('Are you sure you want to delete this request?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pending Requests Pagination -->
            <?php if ($totalPagesPending > 1): ?>
            <div class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <?= ($offsetPending + 1) ?> to <?= min($offsetPending + $perPage, $totalPending) ?> of <?= $totalPending ?> entries
                </div>
                <div class="flex space-x-1">
                    <a href="/admin/index.php?page=members&page_pending=1<?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPagePending == 1 ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        First
                    </a>
                    <a href="/admin/index.php?page=members&page_pending=<?= max(1, $currentPagePending - 1) ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPagePending == 1 ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        Previous
                    </a>
                    
                    <?php 
                    $startPage = max(1, $currentPagePending - 2);
                    $endPage = min($totalPagesPending, $currentPagePending + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="/admin/index.php?page=members&page_pending=<?= $i ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                           class="px-3 py-1 rounded border <?= $i == $currentPagePending ? 'bg-primary-500 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="/admin/index.php?page=members&page_pending=<?= min($totalPagesPending, $currentPagePending + 1) ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPagePending == $totalPagesPending ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        Next
                    </a>
                    <a href="/admin/index.php?page=members&page_pending=<?= $totalPagesPending ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPagePending == $totalPagesPending ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        Last
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Approved Members -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Approved Members</h2>
            
            <?php if (!empty($searchTerm) && count($approvedMembers) === 0): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <p>No approved members found matching your search criteria.</p>
            </div>
            <?php endif; ?>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Member ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($approvedMembers as $member): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if (isset($member['image']) && $member['image']): ?>
                                    <img src="../uploads/members/<?= $member['image'] ?>" 
                                        alt="<?= htmlspecialchars($member['name']) ?>"
                                        class="h-10 w-10 rounded-full">
                                    <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($member['name']) ?>&background=random" 
                                        alt="<?= htmlspecialchars($member['name']) ?>"
                                        class="h-10 w-10 rounded-full">
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($member['name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($member['email']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($member['department']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($member['member_id']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button type="button" onclick="showMemberDetails(<?= htmlspecialchars(json_encode($member)) ?>)" 
                                    class="text-blue-500 hover:text-blue-600">
                                    View
                                </button>
                                <a href="/admin/index.php?page=members&edit=<?= $member['id'] ?>" class="text-primary-500 hover:text-primary-600">Edit</a>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="text-red-500 hover:text-red-600"
                                        onclick="return confirm('Are you sure you want to delete this member?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Approved Members Pagination -->
            <?php if ($totalPagesApproved > 1): ?>
            <div class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <?= ($offsetApproved + 1) ?> to <?= min($offsetApproved + $perPage, $totalApproved) ?> of <?= $totalApproved ?> entries
                </div>
                <div class="flex space-x-1">
                    <a href="/admin/index.php?page=members&page_approved=1<?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPageApproved == 1 ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        First
                    </a>
                    <a href="/admin/index.php?page=members&page_approved=<?= max(1, $currentPageApproved - 1) ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPageApproved == 1 ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        Previous
                    </a>
                    
                    <?php 
                    $startPage = max(1, $currentPageApproved - 2);
                    $endPage = min($totalPagesApproved, $currentPageApproved + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="/admin/index.php?page=members&page_approved=<?= $i ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                           class="px-3 py-1 rounded border <?= $i == $currentPageApproved ? 'bg-primary-500 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="/admin/index.php?page=members&page_approved=<?= min($totalPagesApproved, $currentPageApproved + 1) ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPageApproved == $totalPagesApproved ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        Next
                    </a>
                    <a href="/admin/index.php?page=members&page_approved=<?= $totalPagesApproved ?><?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border <?= $currentPageApproved == $totalPagesApproved ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        Last
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Member Details Modal -->
<div id="memberDetailsModal" class="fixed inset-0 z-50 <?= $viewMember ? '' : 'hidden' ?> overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-2xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Member Details</h3>
                <button type="button" onclick="hideMemberDetails()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div id="memberImage" class="w-24 h-24 rounded-full overflow-hidden">
                        <!-- Profile image will be inserted here -->
                    </div>
                    <div>
                        <h4 id="memberName" class="text-xl font-semibold text-gray-900 dark:text-white"></h4>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Member ID</p>
                        <p id="memberId" class="text-gray-900 dark:text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Department</p>
                        <p id="memberDepartment" class="text-gray-900 dark:text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Roll No</p>
                        <p id="memberRollNo" class="text-gray-900 dark:text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <p id="memberStatus" class="text-gray-900 dark:text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                        <p id="memberEmail" class="text-gray-900 dark:text-white"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                        <p id="memberPhone" class="text-gray-900 dark:text-white"></p>
                    </div>
                </div>
                <!-- Add image gallery section -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Submitted Images</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profile Picture</h5>
                            <div id="profileImageContainer" class="relative group">
                                <!-- Profile image will be inserted here -->
                            </div>
                        </div>
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ID Card Image</h5>
                            <div id="idCardImageContainer" class="relative group">
                                <!-- ID card image will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
                <div id="memberActions" class="flex justify-end space-x-2 mt-4">
                    <!-- Action buttons will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add fullscreen image viewer -->
<div id="fullscreenViewer" class="fixed inset-0 z-50 hidden bg-black bg-opacity-90">
    <div class="relative w-full h-full flex flex-col items-center justify-center">
        <button id="closeButton" class="absolute top-4 right-4 text-white hover:text-gray-300 focus:outline-none">
            <i class="fas fa-times text-3xl"></i>
        </button>
        <img id="fullscreenImage" src="" alt="Fullscreen view" class="max-w-full max-h-[80vh] object-contain">
        <button id="exitButton" class="mt-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
            Exit Fullscreen
        </button>
    </div>
</div>

<!-- Add Delete Confirmation Modal -->
<div id="deleteConfirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Deletion</h3>
                <button type="button" onclick="hideDeleteConfirmation()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600 dark:text-gray-400">Are you sure you want to delete this member? This action cannot be undone and will also delete all associated images.</p>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideDeleteConfirmation()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Cancel</button>
                    <form id="deleteForm" method="POST" class="inline-block">
                        <input type="hidden" name="member_id" id="deleteMemberId">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showFullscreenImage(imageSrc) {
    const fullscreenViewer = document.getElementById('fullscreenViewer');
    const fullscreenImage = document.getElementById('fullscreenImage');
    
    fullscreenImage.src = imageSrc;
    fullscreenViewer.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function exitFullscreen() {
    const fullscreenViewer = document.getElementById('fullscreenViewer');
    fullscreenViewer.classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Close button in top-right
    document.getElementById('closeButton').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        exitFullscreen();
    });

    // Exit button below image
    document.getElementById('exitButton').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        exitFullscreen();
    });

    // Close when clicking outside
    document.getElementById('fullscreenViewer').addEventListener('click', function(e) {
        if (e.target === this) {
            exitFullscreen();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            exitFullscreen();
            hideMemberDetails();
            hideDeleteConfirmation();
        }
    });

    // Make image containers clickable
    const profileImageContainer = document.getElementById('profileImageContainer');
    const idCardImageContainer = document.getElementById('idCardImageContainer');

    if (profileImageContainer) {
        profileImageContainer.addEventListener('click', function(e) {
            const img = this.querySelector('img');
            if (img) {
                showFullscreenImage(img.src);
            }
        });
    }

    if (idCardImageContainer) {
        idCardImageContainer.addEventListener('click', function(e) {
            const img = this.querySelector('img');
            if (img) {
                showFullscreenImage(img.src);
            }
        });
    }

    // Toggle add member form
    document.getElementById('showAddMemberForm').addEventListener('click', function() {
        document.getElementById('memberFormContainer').classList.toggle('hidden');
    });

    // Show member details if view parameter is set
    <?php if ($viewMember): ?>
        showMemberDetails(<?= htmlspecialchars(json_encode($viewMember)) ?>);
    <?php endif; ?>
});

function showMemberDetails(member) {
    const modal = document.getElementById('memberDetailsModal');
    const imageContainer = document.getElementById('memberImage');
    const profileImageContainer = document.getElementById('profileImageContainer');
    const idCardImageContainer = document.getElementById('idCardImageContainer');
    const actionsContainer = document.getElementById('memberActions');
    
    // Set member details
    document.getElementById('memberName').textContent = member.name || 'N/A';
    document.getElementById('memberId').textContent = member.member_id || 'N/A';
    document.getElementById('memberDepartment').textContent = member.department || 'N/A';
    document.getElementById('memberRollNo').textContent = member.roll_no || 'N/A';
    document.getElementById('memberStatus').textContent = member.status ? member.status.charAt(0).toUpperCase() + member.status.slice(1) : 'N/A';
    document.getElementById('memberEmail').textContent = member.email || 'N/A';
    document.getElementById('memberPhone').textContent = member.phone || 'N/A';
    
    // Set member profile image
    if (member.image) {
        const profileImageSrc = `../uploads/members/${member.image}`;
        imageContainer.innerHTML = `<img src="${profileImageSrc}" alt="${member.name || 'Member'}" class="w-full h-full object-cover">`;
        profileImageContainer.innerHTML = `
            <div class="w-full h-48 relative cursor-pointer">
                <img src="${profileImageSrc}" alt="Profile Picture" class="w-full h-full object-cover rounded-lg">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center">
                    <button class="text-white hover:text-primary-500">
                        <i class="fas fa-expand text-xl"></i>
                    </button>
                </div>
            </div>
        `;
    } else {
        imageContainer.innerHTML = `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(member.name || 'Member')}&background=random" alt="${member.name || 'Member'}" class="w-full h-full object-cover">`;
        profileImageContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400">No profile picture submitted</p>';
    }

    // Set ID card image
    if (member.id_card_image) {
        const idCardImageSrc = `../uploads/members/${member.id_card_image}`;
        idCardImageContainer.innerHTML = `
            <div class="w-full h-48 relative cursor-pointer">
                <img src="${idCardImageSrc}" alt="ID Card" class="w-full h-full object-cover rounded-lg">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center">
                    <button class="text-white hover:text-primary-500">
                        <i class="fas fa-expand text-xl"></i>
                    </button>
                </div>
            </div>
        `;
    } else {
        idCardImageContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400">No ID card image submitted</p>';
    }
    
    // Set action buttons based on member status
    actionsContainer.innerHTML = '';
    if (member.status === 'pending') {
        actionsContainer.innerHTML = `
            <form method="POST" class="inline-block">
                <input type="hidden" name="member_id" value="${member.id}">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Approve</button>
            </form>
            <button type="button" onclick="showDeleteConfirmation(${member.id})" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete</button>
            <button type="button" onclick="hideMemberDetails()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Cancel</button>
        `;
    } else {
        actionsContainer.innerHTML = `
            <a href="/admin/index.php?page=members&edit=${member.id}" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded">Edit</a>
            <button type="button" onclick="showDeleteConfirmation(${member.id})" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete</button>
            <button type="button" onclick="hideMemberDetails()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Close</button>
        `;
    }
    
    modal.classList.remove('hidden');
}

function hideMemberDetails() {
    document.getElementById('memberDetailsModal').classList.add('hidden');
    // Update URL to remove view parameter
    window.history.pushState({}, '', '/admin/index.php?page=members<?= $departmentFilter !== 'all' ? '&department=' . urlencode($departmentFilter) : '' ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>');
}

// Close modal when clicking outside
document.getElementById('memberDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideMemberDetails();
    }
});

function showDeleteConfirmation(memberId) {
    document.getElementById('deleteMemberId').value = memberId;
    document.getElementById('deleteConfirmationModal').classList.remove('hidden');
}

function hideDeleteConfirmation() {
    document.getElementById('deleteConfirmationModal').classList.add('hidden');
}

// Add event listener for delete form submission
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
        this.submit();
    }
});
</script>