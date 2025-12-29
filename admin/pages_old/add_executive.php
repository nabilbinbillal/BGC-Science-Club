<?php
// Start output buffering
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable display errors and log them instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../config/auth.php';
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$page = 'executives';
$page_title = 'Add Executive';

$errors = [];
$formData = [
    'name' => '',
    'type' => 'teacher',
    'department' => '',
    'role' => '',
    'bio' => '',
    'website' => '',
    'roll_no' => '',
    'session' => '',
    'email' => '',
    'phone' => '',
    'facebook' => '',
    'twitter' => '',
    'linkedin' => '',
    'instagram' => '',
    'sort_order' => 0
];

// Define sanitize function
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate unique slug for executives
function generateExecutiveSlug($name, $pdo) {
    $baseSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    $baseSlug = preg_replace('/^-+|-+$/', '', $baseSlug);
    $slug = $baseSlug;
    $counter = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM executives WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    return $slug;
}

// Mock department functions (replace with actual logic)
function getTeacherDepartments() {
    return ['Mathematics', 'Science', 'English', 'History'];
}
function getStudentDepartments() {
    return ['Class of 2023', 'Class of 2024', 'Class of 2025'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'type' => trim($_POST['type'] ?? 'teacher'),
        'department' => trim($_POST['department'] ?? ''),
        'role' => trim($_POST['role'] ?? ''),
        'bio' => trim($_POST['bio'] ?? ''),
        'website' => trim($_POST['website'] ?? ''),
        'roll_no' => trim($_POST['roll_no'] ?? ''),
        'session' => trim($_POST['session'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'facebook' => trim($_POST['facebook'] ?? ''),
        'twitter' => trim($_POST['twitter'] ?? ''),
        'linkedin' => trim($_POST['linkedin'] ?? ''),
        'instagram' => trim($_POST['instagram'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0)
    ];

    // Validate required fields
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required';
    }
    if (empty($formData['type'])) {
        $errors['type'] = 'Type is required';
    }
    if (empty($formData['department'])) {
        $errors['department'] = 'Department is required';
    }
    if (empty($formData['role'])) {
        $errors['role'] = 'Role is required';
    }
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    if ($formData['type'] === 'student') {
        if (empty($formData['roll_no'])) {
            $errors['roll_no'] = 'Roll No is required for students';
        }
        if (empty($formData['session'])) {
            $errors['session'] = 'Session is required for students';
        }
    }

    // Validate social media URLs
    foreach (['facebook', 'twitter', 'linkedin', 'instagram'] as $social) {
        if (!empty($formData[$social]) && !filter_var($formData[$social], FILTER_VALIDATE_URL)) {
            $errors[$social] = ucfirst($social) . ' URL is invalid';
        }
    }

    // Handle file upload
    $profile_pic = null;
    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if ($_FILES['profile_pic']['size'] > $max_size) {
            $errors['profile_pic'] = 'Image size must be less than 2MB';
        } elseif (!in_array($_FILES['profile_pic']['type'], $allowed_types)) {
            $errors['profile_pic'] = 'Only JPG, PNG, and GIF images are allowed';
        } else {
            $upload_dir = __DIR__ . '/../Uploads/executives/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $file_name = 'exec_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
                $profile_pic = $file_name;
            } else {
                $errors['profile_pic'] = 'Failed to upload image';
            }
        }
    }

    // If no errors, save to database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $slug = generateExecutiveSlug($formData['name'], $pdo);
            $socialLinks = [
                'facebook' => $formData['facebook'],
                'twitter' => $formData['twitter'],
                'linkedin' => $formData['linkedin'],
                'instagram' => $formData['instagram']
            ];

            $stmt = $pdo->prepare("INSERT INTO executives 
                                 (name, slug, type, department, role, bio, website, social_links, roll_no, session, email, phone, profile_pic, sort_order, archive_year) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM executives), NULL)");
            
            $success = $stmt->execute([
                $formData['name'],
                $slug,
                $formData['type'],
                $formData['department'],
                $formData['role'],
                $formData['bio'],
                $formData['website'],
                json_encode($socialLinks),
                $formData['type'] === 'student' ? $formData['roll_no'] : null,
                $formData['type'] === 'student' ? $formData['session'] : null,
                $formData['email'],
                $formData['phone'],
                $profile_pic
            ]);

            if ($success) {
                $pdo->commit();
                $_SESSION['success'] = "Executive added successfully";
                header("Location: executives.php");
                exit();
            } else {
                throw new Exception("Failed to insert executive");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            // Delete uploaded file if database insert failed
            if ($profile_pic && file_exists($upload_dir . $profile_pic)) {
                unlink($upload_dir . $profile_pic);
            }
            $errors['database'] = "Error saving executive: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container px-4 py-6 mx-auto">
    <div class="mb-6">
        <a href="executives.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
            ← Back to Executives
        </a>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mt-2">Add New Executive</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <h2 class="font-bold">Please fix the following errors:</h2>
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>"
                           class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo isset($errors['name']) ? 'border-red-500' : 'border-gray-300'; ?>"
                           required>
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                    <select id="type" name="type" onchange="updateDepartments()"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo isset($errors['type']) ? 'border-red-500' : 'border-gray-300'; ?>"
                            required>
                        <option value="teacher" <?php echo $formData['type'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                        <option value="student" <?php echo $formData['type'] === 'student' ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>

                <!-- Department -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department *</label>
                    <select id="department" name="department"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo isset($errors['department']) ? 'border-red-500' : 'border-gray-300'; ?>"
                            required>
                        <?php 
                        $departments = $formData['type'] === 'teacher' ? getTeacherDepartments() : getStudentDepartments();
                        foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $formData['department'] === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($formData['role']); ?>"
                           class="