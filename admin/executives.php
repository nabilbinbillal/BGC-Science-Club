<?php
if (!defined('ADMIN')) {
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Ensure sanitize function is defined
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data));
    }
}

// Function to generate a unique slug
function generateUniqueSlug($name, $pdo, $excludeId = null) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'executive';
    }
    $originalSlug = $slug;
    $counter = 1;
    $query = $excludeId !== null 
        ? "SELECT COUNT(*) FROM executives WHERE slug = ? AND id != ?" 
        : "SELECT COUNT(*) FROM executives WHERE slug = ?";
    $stmt = $pdo->prepare($query);
    while (true) {
        $stmt->execute($excludeId !== null ? [$slug, $excludeId] : [$slug]);
        if ($stmt->fetchColumn() == 0) {
            return $slug;
        }
        $slug = $originalSlug . '-' . $counter++;
    }
}

// Check and create missing columns
$columnsToCheck = [
    'sort_order' => "ALTER TABLE executives ADD COLUMN sort_order INT DEFAULT 0",
    'archive_year' => "ALTER TABLE executives ADD COLUMN archive_year VARCHAR(10) DEFAULT NULL",
    'instagram' => "ALTER TABLE executives ADD COLUMN instagram VARCHAR(255) DEFAULT NULL",
    'slug' => "ALTER TABLE executives ADD COLUMN slug VARCHAR(255) DEFAULT NULL, ADD UNIQUE KEY (slug)"
];

foreach ($columnsToCheck as $column => $alterQuery) {
    $check = $pdo->query("SHOW COLUMNS FROM executives LIKE '$column'");
    if ($check->rowCount() == 0) {
        $pdo->exec($alterQuery);
        if ($column == 'sort_order') {
            $pdo->exec("UPDATE executives SET sort_order = id WHERE sort_order = 0");
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action == 'add' || $action == 'edit') {
                $name = sanitize($_POST['name']);
                $type = sanitize($_POST['type']);
                $department = sanitize($_POST['department']);
                $position = sanitize($_POST['role']); // Map 'role' from form to 'position' in DB
                $bio = sanitize($_POST['bio'] ?? '');
                $email = sanitize($_POST['email'] ?? '');
                $phone = sanitize($_POST['phone'] ?? '');
                $facebook = sanitize($_POST['facebook'] ?? '');
                $twitter = sanitize($_POST['twitter'] ?? '');
                $linkedin = sanitize($_POST['linkedin'] ?? '');
                $instagram = sanitize($_POST['instagram'] ?? '');
                $website = sanitize($_POST['website'] ?? '');
                $roll_no = sanitize($_POST['roll_no'] ?? '');
                $social_links = sanitize($_POST['social_links'] ?? '');

                $slug = generateUniqueSlug($name, $pdo, $action == 'edit' ? ($_POST['id'] ?? null) : null);

                $profilePic = '';
                if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                    $target_dir = "../uploads/executives/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
                    $profilePic = uniqid() . '.' . $extension;
                    $target_file = $target_dir . $profilePic;
                    if (!move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                        throw new Exception("Error uploading profile picture");
                    }
                }

                if ($action == 'add') {
                    $stmt = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS new_sort_order FROM executives");
                    $sortOrder = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("INSERT INTO executives (name, type, department, position, bio, email, phone, facebook, twitter, linkedin, instagram, profile_pic, slug, sort_order, archive_year, website, roll_no, social_links, session) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, NULL)");
                    $stmt->execute([$name, $type, $department, $position, $bio, $email, $phone, $facebook, $twitter, $linkedin, $instagram, $profilePic, $slug, $sortOrder, $website, $roll_no, $social_links]);
                    $_SESSION['success'] = "Executive added successfully!";
                } else {
                    $id = $_POST['id'];
                    $updateQuery = "UPDATE executives SET name = ?, type = ?, department = ?, position = ?, bio = ?, email = ?, phone = ?, facebook = ?, twitter = ?, linkedin = ?, instagram = ?, slug = ?, website = ?, roll_no = ?, social_links = ?";
                    $params = [$name, $type, $department, $position, $bio, $email, $phone, $facebook, $twitter, $linkedin, $instagram, $slug, $website, $roll_no, $social_links];

                    if ($profilePic) {
                        $updateQuery .= ", profile_pic = ?";
                        $params[] = $profilePic;
                    }

                    $updateQuery .= " WHERE id = ?";
                    $params[] = $id;

                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute($params);
                    $_SESSION['success'] = "Executive updated successfully!";
                }
            } elseif ($action == 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM executives WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Executive deleted successfully!";
            } elseif ($action == 'reorder') {
                $order = json_decode($_POST['order'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid order data");
                }
                $pdo->beginTransaction();
                foreach ($order as $item) {
                    if (!isset($item['id']) || !isset($item['sort_order'])) {
                        throw new Exception("Invalid order item");
                    }
                    $stmt = $pdo->prepare("UPDATE executives SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$item['sort_order'], $item['id']]);
                }
                $pdo->commit();
                exit('Order updated successfully');
            } elseif ($action == 'archive_committee') {
                $archiveYear = sanitize($_POST['archive_year']);
                $selectedMembers = isset($_POST['selected_members']) ? json_decode($_POST['selected_members'], true) : [];

                if (!$archiveYear) {
                    throw new Exception("Archive year is required");
                }

                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE executives SET archive_year = ? WHERE archive_year IS NULL");
                $stmt->execute([$archiveYear]);

                if (!empty($selectedMembers)) {
                    $placeholders = implode(',', array_fill(0, count($selectedMembers), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM executives WHERE id IN ($placeholders)");
                    $stmt->execute($selectedMembers);
                    $membersToCopy = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($membersToCopy as $member) {
                        $stmt = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS new_sort_order FROM executives");
                        $sortOrder = $stmt->fetchColumn();
                        $newSlug = generateUniqueSlug($member['name'], $pdo);

                        $stmt = $pdo->prepare("INSERT INTO executives (name, type, department, position, bio, email, phone, facebook, twitter, linkedin, instagram, profile_pic, slug, sort_order, archive_year, website, roll_no, social_links, session) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, NULL)");
                        $stmt->execute([
                            $member['name'],
                            $member['type'],
                            $member['department'],
                            $member['position'],
                            $member['bio'],
                            $member['email'],
                            $member['phone'],
                            $member['facebook'],
                            $member['twitter'],
                            $member['linkedin'],
                            $member['instagram'],
                            $member['profile_pic'],
                            $newSlug,
                            $sortOrder,
                            $member['website'],
                            $member['roll_no'],
                            $member['social_links']
                        ]);
                    }
                }
                $pdo->commit();
                $_SESSION['success'] = "Current committee archived for year $archiveYear";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } elseif ($action == 'restore_archives') {
                $stmt = $pdo->prepare("SELECT DISTINCT archive_year FROM executives WHERE archive_year IS NOT NULL ORDER BY archive_year DESC LIMIT 1");
                $stmt->execute();
                $lastYear = $stmt->fetchColumn();
                if ($lastYear) {
                    $updateStmt = $pdo->prepare("UPDATE executives SET archive_year = NULL WHERE archive_year = ?");
                    $updateStmt->execute([$lastYear]);
                    $_SESSION['success'] = "Last archived committee restored successfully.";
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    throw new Exception("No archived committees to restore.");
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Get archive year from URL parameter
$archiveYearFilter = isset($_GET['year']) ? $_GET['year'] : 'current';

// Search and filter functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR position LIKE ? OR department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($typeFilter)) {
    $whereConditions[] = "type = ?";
    $params[] = $typeFilter;
}

if (!empty($departmentFilter)) {
    $whereConditions[] = "department = ?";
    $params[] = $departmentFilter;
}

if ($archiveYearFilter === 'current') {
    $whereConditions[] = "archive_year IS NULL";
} else {
    $whereConditions[] = "archive_year = ?";
    $params[] = $archiveYearFilter;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$items_per_page = 20;
$page_number = isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : 1;
$offset = ($page_number - 1) * $items_per_page;

$count_query = "SELECT COUNT(*) FROM executives $whereClause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_executives = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_executives / $items_per_page));

$query = "SELECT * FROM executives $whereClause ORDER BY sort_order ASC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
foreach ($params as $k => $param) {
    $stmt->bindValue($k + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$executives = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("SELECT DISTINCT department FROM executives ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$types = $pdo->query("SELECT DISTINCT type FROM executives ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
$archiveYears = $pdo->query("SELECT DISTINCT archive_year FROM executives WHERE archive_year IS NOT NULL ORDER BY archive_year DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executives Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Notification: explicit styles (don't rely on Tailwind @apply here) */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            background: #ffffff; /* readable white */
            color: #0f172a; /* dark text for contrast */
            border-radius: 0.5rem; /* rounded-lg */
            box-shadow: 0 8px 24px rgba(2,6,23,0.12); /* shadow-lg */
            padding: 0.75rem 1rem; /* p-4-ish */
            margin-bottom: 0.5rem; /* mb-2 */
            border: 1px solid rgba(15,23,42,0.06);
            max-width: 22rem; /* max-w-sm */
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease-out;
        }

        /* Clearer colored borders for notification variants */
        .notification.success { border-color: #10b981; }
        .notification.error { border-color: #ef4444; }

        /* Dark-mode fallback: invert for readability */
        body.dark .notification {
            background: #0b1220; /* dark surface */
            color: #f8fafc; /* light text */
            border-color: rgba(255,255,255,0.06);
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php
    // Display session-based alerts using the JavaScript notification system
    if (isset($_SESSION['success'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => showNotification('" . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . "', 'success'));</script>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => showNotification('" . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . "', 'error'));</script>";
        unset($_SESSION['error']);
    }
    ?>

    <div class="container px-4 py-6 mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <label for="committeeFilter" class="mr-2 font-semibold">Select Committee:</label>
                <select id="committeeFilter" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    <option value="current" <?= $archiveYearFilter === 'current' ? 'selected' : '' ?>>Current Committee</option>
                    <?php foreach ($archiveYears as $year): ?>
                        <option value="<?= htmlspecialchars($year) ?>" <?= $archiveYearFilter == $year ? 'selected' : '' ?>>
                            Archived - <?= htmlspecialchars($year) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button id="restoreArchivedBtn" class="ml-4 px-3 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600" title="Restore last archived committee">Restore Last Committee</button>
            </div>
            <div>
                <button onclick="showModal('add')" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                    Add Executive
                </button>
                <button id="addNewCommitteeBtn" class="ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Archive Current Committee
                </button>
            </div>
        </div>

        <div class="mb-6 bg-white p-4 rounded-lg shadow">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="page" value="executives">
                <input type="hidden" name="year" value="<?= htmlspecialchars($archiveYearFilter) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Name, position or department" 
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">All Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $typeFilter == $type ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($type)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter == $dept ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Filter</button>
                    <a href="?page=executives" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Reset</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (empty($executives)): ?>
                <div class="p-8 text-center text-gray-500">No executives found. Try adjusting your search filters.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profile</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="executivesList">
                            <?php foreach ($executives as $executive): ?>
                            <tr class="hover:bg-gray-50" data-id="<?= $executive['id'] ?>">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <img src="<?= $executive['profile_pic'] ? '../uploads/executives/' . htmlspecialchars($executive['profile_pic']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHBhdGggZmlsbD0iI2Q4ZDhkOCIgZD0iTTUwIDUwYzE0LjE0IDAgMjUuNS0xMS4zNiAyNS41LTI1LjUgMC0xNC4xNC0xMS4zNi0yNS41LTI1LjUtMjUuNVMyNC41IDEwLjM2IDI0LjUgMjQuNSAyNS41IDUwIDUwIDUwem0wIDEwQzM0LjY2IDYwIDIxIDcyLjA4IDIxIDg3LjVINzljMCAxNS4wOC0xMy42NiAyNy4xNi0yOSAyNy4xNi01LjY3IDAtMTEuMDUtMS44My0xNS44My00Ljk3QzE5LjU3IDk4LjEzIDExIDI3LjYxIDExIDVhNCA0IDAgMCAwIDggMGMwIDIyLjA3IDcuNDMgODcuNzMgMTYuNDMgOTYuNzNDNDAuNDIgMTA0LjE3IDQ1LjE0IDEwNiA1MCAxMDZzOS41OC0xLjgzIDE0LjU3LTQuMzNDNzMuNTcgOTIuNzMgODEgMjcuMDcgODEgNWE0IDAgMCAwIDgtMGMwIDIyLjYxLTguNTcgOTMuMTMtMjMuNDMgMTA0LjE3QzYxLjA1IDEyNS4zMyA1NS42NyAxMjcuMTYgNTAgMTI3LjE2Yy0xNS4zNCAwLTI5LTEyLjA4LTI5LTI3LjE2SDc5Qz79IDcyLjA4IDY1LjM0IDYwIDUwIDYwWiIvPjwvc3ZnPg==' ?>" 
                                         alt="Profile" class="w-10 h-10 rounded-full object-cover">
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($executive['name']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= ucfirst(htmlspecialchars($executive['type'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($executive['department']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($executive['position']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($executive['slug'] ?? 'N/A') ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick='showViewModal(<?= json_encode($executive, JSON_HEX_QUOT | JSON_HEX_APOS) ?>)' 
                                                class="text-blue-600 hover:text-blue-900">View</button>
                                        <?php if (empty($executive['archive_year'])): ?>
                                            <button onclick='showModal("edit", <?= json_encode($executive, JSON_HEX_QUOT | JSON_HEX_APOS) ?>)' 
                                                    class="text-green-600 hover:text-green-900">Edit</button>
                                            <button onclick="confirmDelete(<?= $executive['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-900">Delete</button>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic text-sm">Archived</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page_number > 1): ?>
                        <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=<?= $page_number-1 ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                        <?php endif; ?>
                        <?php if ($page_number < $total_pages): ?>
                        <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=<?= $page_number+1 ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">Page <?= $page_number ?> of <?= $total_pages ?></p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=1" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?= $page_number == 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">First</span><i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=<?= max(1, $page_number-1) ?>" 
                                   class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?= $page_number == 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Previous</span><i class="fas fa-angle-left"></i>
                                </a>
                                <?php 
                                $start = max(1, $page_number - 2);
                                $end = min($total_pages, $page_number + 2);
                                for ($i = $start; $i <= $end; $i++): ?>
                                <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=<?= $i ?>" 
                                   class="<?= $i == $page_number ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>
                                <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=<?= min($total_pages, $page_number+1) ?>" 
                                   class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?= $page_number == $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Next</span><i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=executives&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&department=<?= urlencode($departmentFilter) ?>&year=<?= urlencode($archiveYearFilter) ?>&page_no=<?= $total_pages ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?= $page_number == $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Last</span><i class="fas fa-angle-double-right"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="archiveConfirmModal" class="fixed inset-0 z-40 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Archive Current Committee</h3>
                <p class="mb-4">Are you sure you want to archive the current committee?</p>
                <label class="inline-flex items-center mb-4">
                    <input type="checkbox" id="confirmArchiveCheckbox" class="form-checkbox h-5 w-5 text-green-600">
                    <span class="ml-2">I confirm that I want to archive the current committee.</span>
                </label>
                <div class="flex justify-end space-x-4">
                    <button id="archiveCancelBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button id="archiveNextBtn" class="px-4 py-2 bg-green-600 text-white rounded disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>

    <div id="archiveYearModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Enter Archive Year</h3>
                <input type="number" id="archiveYearInput" min="1900" max="2100" placeholder="e.g. 2025" class="w-full px-3 py-2 border rounded mb-4" />
                <label for="archiveConfirmText" class="block mb-2 font-medium text-gray-700">Type "archive {year}" to confirm</label>
                <input type="text" id="archiveConfirmText" placeholder='e.g. archive 2025' class="w-full px-3 py-2 border rounded mb-4" disabled />
                <div class="flex justify-end space-x-4">
                    <button id="archiveYearCancelBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button id="archiveYearConfirmBtn" class="px-4 py-2 bg-green-600 text-white rounded disabled:opacity-50" disabled>Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <div id="memberSelectionModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Select Members to Carry Over</h3>
                <div class="max-h-96 overflow-y-auto mb-4">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2">Select</th>
                                <th class="py-2">Profile</th>
                                <th class="py-2">Name</th>
                                <th class="py-2">Type</th>
                            </tr>
                        </thead>
                        <tbody id="memberSelectionList">
                            <?php foreach ($executives as $executive): ?>
                            <?php if (empty($executive['archive_year'])): ?>
                            <tr>
                                <td class="py-2">
                                    <input type="checkbox" name="selected_members[]" value="<?= $executive['id'] ?>" class="member-checkbox">
                                </td>
                                <td class="py-2">
                                    <img src="<?= $executive['profile_pic'] ? '../uploads/executives/' . htmlspecialchars($executive['profile_pic']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHBhdGggZmlsbD0iI2Q4ZDhkOCIgZD0iTTUwIDUwYzE0LjE0IDAgMjUuNS0xMS4zNiAyNS41LTI1LjUgMC0xNC4xNC0xMS4zNi0yNS41LTI1LjUtMjUuNVMyNC41IDEwLjM2IDI0LjUgMjQuNSAyNS41IDUwIDUwIDUwem0wIDEwQzM0LjY2IDYwIDIxIDcyLjA4IDIxIDg3LjVINzljMCAxNS4wOC0xMy42NiAyNy4xNi0yOSAyNy4xNi01LjY3IDAtMTEuMDUtMS44My0xNS44My00Ljk3QzE5LjU3IDk4LjEzIDExIDI3LjYxIDExIDVhNCA0IDAgMCAwIDggMGMwIDIyLjA3IDcuNDMgODcuNzMgMTYuNDMgOTYuNzNDNDAuNDIgMTA0LjE3IDQ1LjE0IDEwNiA1MCAxMDZzOS41OC0xLjgzIDE0LjU3LTQuMzNDNzMuNTcgOTIuNzMgODEgMjcuMDcgODEgNWE0IDAgMCAwIDgtMGMwIDIyLjYxLTguNTcgOTMuMTMtMjMuNDMgMTA0LjE3QzYxLjA1IDEyNS4zMyA1NS42NyAxMjcuMTYgNTAgMTI3LjE2Yy0xNS4zNCAwLTI5LTEyLjA4LTI5LTI3LjE2SDc5Qz79IDcyLjA4IDY1LjM0IDYwIDUwIDYwWiIvPjwvc3ZnPg==' ?>" 
                                         alt="Profile" class="w-8 h-8 rounded-full">
                                </td>
                                <td class="py-2"><?= htmlspecialchars($executive['name']) ?></td>
                                <td class="py-2"><?= ucfirst(htmlspecialchars($executive['type'])) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end space-x-4">
                    <button id="memberSelectionCancelBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button id="memberSelectionConfirmBtn" class="px-4 py-2 bg-green-600 text-white rounded">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <div id="restoreConfirmModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
                <div class="flex items-center justify-center mb-4">
                    <svg class="h-12 w-12 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center mb-4">Confirm Restore</h3>
                <p class="text-gray-600 text-center mb-4">Type "restore committee" to confirm</p>
                <input type="text" id="restoreConfirmText" placeholder="Type 'restore committee'" 
                       class="w-full px-3 py-2 border rounded mb-4 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                <div class="flex justify-center space-x-4">
                    <button id="restoreConfirmCancelBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                    <button id="restoreConfirmBtn" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 disabled:opacity-50" disabled>Restore</button>
                </div>
            </div>
        </div>
    </div>

    <div id="executiveModal" class="fixed inset-0 z-30 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="executiveForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id" id="executiveId">
                    <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modalTitle">Add Executive</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" name="name" id="name" required 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Type *</label>
                                <select name="type" id="type" required 
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="teacher">Teacher</option>
                                    <option value="student">Student</option>
                                    <option value="OK">OK</option>
                                </select>
                            </div>
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700">Department *</label>
                                <input type="text" name="department" id="department" required 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">Position *</label>
                                <input type="text" name="role" id="role" required 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                                <textarea name="bio" id="bio" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="email" id="email"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="tel" name="phone" id="phone"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                <input type="url" name="website" id="website" placeholder="https://example.com"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="roll_no" class="block text-sm font-medium text-gray-700">Roll No</label>
                                <input type="text" name="roll_no" id="roll_no"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="social_links" class="block text-sm font-medium text-gray-700">Social Links (JSON)</label>
                                <textarea name="social_links" id="social_links" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="facebook" class="block text-sm font-medium text-gray-700">Facebook</label>
                                    <input type="url" name="facebook" id="facebook" placeholder="https://facebook.com/username"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="twitter" class="block text-sm font-medium text-gray-700">Twitter</label>
                                    <input type="url" name="twitter" id="twitter" placeholder="https://twitter.com/username"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="linkedin" class="block text-sm font-medium text-gray-700">LinkedIn</label>
                                    <input type="url" name="linkedin" id="linkedin" placeholder="https://linkedin.com/in/username"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="instagram" class="block text-sm font-medium text-gray-700">Instagram</label>
                                    <input type="url" name="instagram" id="instagram" placeholder="https://instagram.com/username"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <div>
                                <label for="profile_pic" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                                <input type="file" name="profile_pic" id="profile_pic" accept="image/*"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" onclick="hideModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="viewModal" class="fixed inset-0 z-40 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-semibold" id="viewName"></h3>
                    <button onclick="hideViewModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
                </div>
                <div class="flex flex-col items-center mb-4">
                    <img id="viewProfilePic" src="" alt="Profile" class="w-24 h-24 rounded-full object-cover mb-3">
                    <div class="text-center">
                        <p id="viewPosition" class="text-lg font-medium"></p>
                        <p id="viewType" class="text-sm text-gray-600"></p>
                        <p id="viewDepartment" class="text-sm text-gray-600"></p>
                        <p id="viewSlug" class="text-sm text-gray-600"></p>
                    </div>
                </div>
                <div class="mb-4">
                    <h4 class="font-medium text-gray-700">Bio</h4>
                    <p id="viewBio" class="text-gray-600"></p>
                </div>
                <div class="mb-4">
                    <h4 class="font-medium text-gray-700">Contact Information</h4>
                    <p id="viewEmail" class="text-gray-600"></p>
                    <p id="viewPhone" class="text-gray-600"></p>
                    <p id="viewWebsite" class="text-gray-600"></p>
                    <p id="viewRollNo" class="text-gray-600"></p>
                </div>
                <div class="mb-4">
                    <h4 class="font-medium text-gray-700">Social Media</h4>
                    <div class="flex space-x-4 mt-2" id="socialLinks"></div>
                </div>
                <div class="mb-4">
                    <h4 class="font-medium text-gray-700">Additional Social Links</h4>
                    <p id="viewSocialLinks" class="text-gray-600"></p>
                </div>
                <div class="flex justify-end">
                    <button id="editFromViewBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</button>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="fixed inset-0 z-40 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
                <div class="flex items-center justify-center mb-4">
                    <svg class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center mb-4">Confirm Delete</h3>
                <p class="text-gray-600 text-center mb-6">Are you sure you want to delete this executive?</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <div class="flex justify-center space-x-4">
                        <button type="button" onclick="hideDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="notification-container" class="fixed top-4 right-4 z-50"></div>

    <script>
    try {
        function showNotification(message, type = 'success') {
            console.log(`Showing notification: ${message}, type: ${type}`);
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        ${type === 'success' ? '<svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' :
                          type === 'error' ? '<svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' :
                          '<svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'}
                    </div>
                    <div class="ml-3"><p class="text-sm font-medium">${message}</p></div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        document.getElementById('committeeFilter').addEventListener('change', function() {
            console.log('Committee filter changed:', this.value);
            const year = this.value;
            let url = window.location.pathname;
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'executives');
            if (year !== 'current') {
                params.set('year', year);
            } else {
                params.delete('year');
            }
            window.location.href = url + '?' + params.toString();
        });

        document.getElementById('addNewCommitteeBtn').addEventListener('click', function() {
            console.log('Archive committee button clicked');
            document.getElementById('archiveConfirmModal').classList.remove('hidden');
        });

        document.getElementById('archiveCancelBtn').addEventListener('click', function() {
            console.log('Archive cancel button clicked');
            document.getElementById('archiveConfirmModal').classList.add('hidden');
            document.getElementById('confirmArchiveCheckbox').checked = false;
            document.getElementById('archiveNextBtn').disabled = true;
        });

        document.getElementById('confirmArchiveCheckbox').addEventListener('change', function() {
            console.log('Archive checkbox changed:', this.checked);
            document.getElementById('archiveNextBtn').disabled = !this.checked;
        });

        document.getElementById('archiveNextBtn').addEventListener('click', function() {
            console.log('Archive next button clicked');
            document.getElementById('archiveConfirmModal').classList.add('hidden');
            document.getElementById('archiveYearModal').classList.remove('hidden');
        });

        document.getElementById('archiveYearCancelBtn').addEventListener('click', function() {
            console.log('Archive year cancel button clicked');
            document.getElementById('archiveYearModal').classList.add('hidden');
            document.getElementById('archiveYearInput').value = '';
            document.getElementById('archiveConfirmText').value = '';
            document.getElementById('archiveYearConfirmBtn').disabled = true;
        });

        document.getElementById('archiveYearInput').addEventListener('input', function() {
            console.log('Archive year input:', this.value);
            const val = this.value;
            document.getElementById('archiveConfirmText').value = '';
            document.getElementById('archiveConfirmText').disabled = !(val && val.length === 4 && !isNaN(val));
            document.getElementById('archiveYearConfirmBtn').disabled = true;
        });

        document.getElementById('archiveConfirmText').addEventListener('input', function() {
            console.log('Archive confirm text input:', this.value);
            const year = document.getElementById('archiveYearInput').value;
            const expectedText = `archive ${year}`;
            document.getElementById('archiveYearConfirmBtn').disabled = this.value.trim().toLowerCase() !== expectedText.toLowerCase();
        });

        document.getElementById('archiveYearConfirmBtn').addEventListener('click', function() {
            console.log('Archive year confirm button clicked');
            const year = document.getElementById('archiveYearInput').value;
            if (!year) return;
            document.getElementById('archiveYearModal').classList.add('hidden');
            document.getElementById('memberSelectionModal').classList.remove('hidden');
        });

        document.getElementById('memberSelectionCancelBtn').addEventListener('click', function() {
            console.log('Member selection cancel button clicked');
            document.getElementById('memberSelectionModal').classList.add('hidden');
        });

        document.getElementById('memberSelectionConfirmBtn').addEventListener('click', function() {
            console.log('Member selection confirm button clicked');
            const year = document.getElementById('archiveYearInput').value;
            const selectedMembers = Array.from(document.querySelectorAll('.member-checkbox:checked'))
                .map(checkbox => checkbox.value);
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=archive_committee&archive_year=${encodeURIComponent(year)}&selected_members=${encodeURIComponent(JSON.stringify(selectedMembers))}`
            }).then(response => {
                console.log('Archive committee response:', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            }).then(data => {
                showNotification(`Current committee archived for year ${year}. Selected members have been carried over to the new committee.`, 'success');
                setTimeout(() => location.reload(), 1500);
            }).catch(err => {
                console.error('Archive committee error:', err);
                showNotification('Error archiving committee: ' + err.message, 'error');
            });
        });

        document.getElementById('restoreArchivedBtn').addEventListener('click', function() {
            console.log('Restore archived button clicked');
            document.getElementById('restoreConfirmModal').classList.remove('hidden');
        });

        document.getElementById('restoreConfirmCancelBtn').addEventListener('click', function() {
            console.log('Restore confirm cancel button clicked');
            document.getElementById('restoreConfirmModal').classList.add('hidden');
            document.getElementById('restoreConfirmText').value = '';
            document.getElementById('restoreConfirmBtn').disabled = true;
        });

        document.getElementById('restoreConfirmText').addEventListener('input', function() {
            console.log('Restore confirm text input:', this.value);
            const expectedText = 'restore committee';
            document.getElementById('restoreConfirmBtn').disabled = this.value.trim().toLowerCase() !== expectedText.toLowerCase();
        });

        document.getElementById('restoreConfirmBtn').addEventListener('click', function() {
            console.log('Restore confirm button clicked');
            document.getElementById('restoreConfirmModal').classList.add('hidden');
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=restore_archives'
            }).then(response => {
                console.log('Restore archives response:', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            }).then(data => {
                showNotification('Last archived committee restored successfully.', 'success');
                setTimeout(() => location.reload(), 1500);
            }).catch(err => {
                console.error('Restore archives error:', err);
                showNotification('Error restoring archive: ' + err.message, 'error');
            });
        });

        function showModal(action, executive = null) {
            console.log('showModal called with action:', action, 'executive:', executive);
            try {
                const modal = document.getElementById('executiveModal');
                const form = document.getElementById('executiveForm');
                const title = document.getElementById('modalTitle');
                document.getElementById('formAction').value = action;
                title.textContent = action === 'add' ? 'Add Executive' : 'Edit Executive';
                if (action === 'edit' && executive) {
                    document.getElementById('executiveId').value = executive.id;
                    document.getElementById('name').value = executive.name || '';
                    document.getElementById('type').value = executive.type || 'teacher';
                    document.getElementById('department').value = executive.department || '';
                    document.getElementById('role').value = executive.position || '';
                    document.getElementById('bio').value = executive.bio || '';
                    document.getElementById('email').value = executive.email || '';
                    document.getElementById('phone').value = executive.phone || '';
                    document.getElementById('facebook').value = executive.facebook || '';
                    document.getElementById('twitter').value = executive.twitter || '';
                    document.getElementById('linkedin').value = executive.linkedin || '';
                    document.getElementById('instagram').value = executive.instagram || '';
                    document.getElementById('website').value = executive.website || '';
                    document.getElementById('roll_no').value = executive.roll_no || '';
                    document.getElementById('social_links').value = executive.social_links || '';
                } else {
                    form.reset();
                    document.getElementById('executiveId').value = '';
                }
                modal.classList.remove('hidden');
            } catch (err) {
                console.error('Error in showModal:', err);
                showNotification('Error opening modal: ' + err.message, 'error');
            }
        }

        function hideModal() {
            console.log('hideModal called');
            document.getElementById('executiveModal').classList.add('hidden');
        }

        function showViewModal(executive) {
            console.log('showViewModal called with executive:', executive);
            try {
                document.getElementById('viewName').textContent = executive.name || 'N/A';
                document.getElementById('viewProfilePic').src = executive.profile_pic ? 
                    '../uploads/executives/' + encodeURIComponent(executive.profile_pic) : 
                    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHBhdGggZmlsbD0iI2Q4ZDhkOCIgZD0iTTUwIDUwYzE0LjE0IDAgMjUuNS0xMS4zNiAyNS41LTI1LjUgMC0xNC4xNC0xMS4zNi0yNS41LTI1LjUtMjUuNVMyNC41IDEwLjM2IDI0LjUgMjQuNSAyNS41IDUwIDUwIDUwem0wIDEwQzM0LjY2IDYwIDIxIDcyLjA4IDIxIDg3LjVINzljMCAxNS4wOC0xMy42NiAyNy4xNi0yOSAyNy4xNi01LjY3IDAtMTEuMDUtMS44My0xNS44My00Ljk3QzE5LjU3IDk4LjEzIDExIDI3LjYxIDExIDVhNCA0IDAgMCAwIDggMGMwIDIyLjA3IDcuNDMgODcuNzMgMTYuNDMgOTYuNzNDNDAuNDIgMTA0LjE3IDQ1LjE0IDEwNiA1MCAxMDZzOS41OC0xLjgzIDE0LjU3LTQuMzNDNzMuNTcgOTIuNzMgODEgMjcuMDcgODEgNWE0IDAgMCAwIDgtMGMwIDIyLjYxLTguNTcgOTMuMTMtMjMuNDMgMTA0LjE3QzYxLjA1IDEyNS4zMyA1NS42NyAxMjcuMTYgNTAgMTI3LjE2Yy0xNS4zNCAwLTI5LTEyLjA4LTI5LTI3LjE2SDc5Qz79IDcyLjA4IDY1LjM0IDYwIDUwIDYwWiIvPjwvc3ZnPg==';
                document.getElementById('viewPosition').textContent = executive.position || 'N/A';
                document.getElementById('viewType').textContent = 'Type: ' + (executive.type ? executive.type.charAt(0).toUpperCase() + executive.type.slice(1) : 'N/A');
                document.getElementById('viewDepartment').textContent = 'Department: ' + (executive.department || 'N/A');
                document.getElementById('viewSlug').textContent = 'Slug: ' + (executive.slug || 'N/A');
                document.getElementById('viewBio').textContent = executive.bio || 'No bio available';
                document.getElementById('viewEmail').textContent = 'Email: ' + (executive.email || 'N/A');
                document.getElementById('viewPhone').textContent = 'Phone: ' + (executive.phone || 'N/A');
                document.getElementById('viewWebsite').textContent = 'Website: ' + (executive.website || 'N/A');
                document.getElementById('viewRollNo').textContent = 'Roll No: ' + (executive.roll_no || 'N/A');
                document.getElementById('viewSocialLinks').textContent = executive.social_links || 'No additional social links';
                const socialLinksContainer = document.getElementById('socialLinks');
                socialLinksContainer.innerHTML = '';
                const socialPlatforms = [
                    { name: 'facebook', icon: 'fab fa-facebook', color: 'text-blue-600' },
                    { name: 'twitter', icon: 'fab fa-twitter', color: 'text-blue-400' },
                    { name: 'linkedin', icon: 'fab fa-linkedin', color: 'text-blue-700' },
                    { name: 'instagram', icon: 'fab fa-instagram', color: 'text-pink-600' }
                ];
                socialPlatforms.forEach(platform => {
                    if (executive[platform.name]) {
                        const link = document.createElement('a');
                        link.href = executive[platform.name];
                        link.target = '_blank';
                        link.className = `${platform.color} text-2xl hover:opacity-75`;
                        link.innerHTML = `<i class="${platform.icon}"></i>`;
                        socialLinksContainer.appendChild(link);
                    }
                });
                if (socialLinksContainer.children.length === 0) {
                    socialLinksContainer.innerHTML = '<p class="text-gray-500">No social links available</p>';
                }
                document.getElementById('editFromViewBtn').onclick = function() {
                    console.log('Edit from view button clicked');
                    hideViewModal();
                    showModal('edit', executive);
                };
                document.getElementById('viewModal').classList.remove('hidden');
            } catch (err) {
                console.error('Error in showViewModal:', err);
                showNotification('Error opening view modal: ' + err.message, 'error');
            }
        }

        function hideViewModal() {
            console.log('hideViewModal called');
            document.getElementById('viewModal').classList.add('hidden');
        }

        function confirmDelete(id) {
            console.log('confirmDelete called with id:', id);
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            console.log('hideDeleteModal called');
            document.getElementById('deleteModal').classList.add('hidden');
        }

        window.onclick = function(event) {
            console.log('Window click event, target:', event.target);
            const modals = ['executiveModal', 'viewModal', 'archiveConfirmModal', 'archiveYearModal', 'memberSelectionModal', 'restoreConfirmModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        };

        document.getElementById('executiveForm').addEventListener('submit', function(e) {
            console.log('executiveForm submit event');
            e.preventDefault();
            const formData = new FormData(this);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => {
                console.log('Form submission response:', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.text();
            }).then(data => {
                const action = document.getElementById('formAction').value;
                showNotification(action === 'add' ? 'Executive added successfully!' : 'Executive updated successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            }).catch(err => {
                console.error('Form submission error:', err);
                showNotification('Error saving executive: ' + err.message, 'error');
            });
        });

        console.log('JavaScript initialized successfully');
    } catch (err) {
        console.error('Global JavaScript error:', err);
        showNotification('Error initializing page: ' + err.message, 'error');
    }
    </script>
</body>
</html>