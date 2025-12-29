<?php
require_once '../config/auth.php';
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$page = 'executives';
$page_title = 'Add Executive';

$errors = [];
$formData = [
    'name' => '',
    'position' => '',
    'department' => '',
    'email' => '',
    'phone' => '',
    'sort_order' => 0
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'position' => trim($_POST['position'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0)
    ];

    // Validate required fields
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required';
    }
    if (empty($formData['position'])) {
        $errors['position'] = 'Position is required';
    }
    if (empty($formData['department'])) {
        $errors['department'] = 'Department is required';
    }
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    // Handle file upload
    $profile_pic = null;
    if (!empty($_FILES['profile_pic']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if ($_FILES['profile_pic']['size'] > $max_size) {
            $errors['profile_pic'] = 'Image size must be less than 2MB';
        } elseif (!in_array($_FILES['profile_pic']['type'], $allowed_types)) {
            $errors['profile_pic'] = 'Only JPG, PNG, and GIF images are allowed';
        } else {
            $upload_dir = '../uploads/executives/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $file_name = 'exec_' . time() . '.' . strtolower($file_ext);
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
            $role = $formData['position']; // store position into role column as well for compatibility
            $stmt = $pdo->prepare("INSERT INTO executives 
                                 (name, position, role, department, email, phone, profile_pic, sort_order) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $success = $stmt->execute([
                $formData['name'],
                $formData['position'],
                $role,
                $formData['department'],
                $formData['email'],
                $formData['phone'],
                $profile_pic,
                $formData['sort_order']
            ]);

            if ($success) {
                $_SESSION['success'] = "Executive added successfully";
                header("Location: executives.php");
                exit();
            }
        } catch (PDOException $e) {
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

                <!-- Position -->
                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position *</label>
                    <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($formData['position']); ?>"
                           class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo isset($errors['position']) ? 'border-red-500' : 'border-gray-300'; ?>"
                           required>
                </div>

                <!-- Department -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department *</label>
                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($formData['department']); ?>"
                           class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo isset($errors['department']) ? 'border-red-500' : 'border-gray-300'; ?>"
                           required>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>"
                           class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?>"
                           required>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>"
                           class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($formData['sort_order']); ?>"
                           class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- Profile Picture -->
                <div class="md:col-span-2">
                    <label for="profile_pic" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900 dark:file:text-blue-200
                                  hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                                  <?php echo isset($errors['profile_pic']) ? 'border-red-500' : 'border-gray-300'; ?>">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">JPEG, PNG or GIF (Max 2MB)</p>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Add Executive
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>