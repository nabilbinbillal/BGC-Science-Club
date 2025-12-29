<?php
if (!isSuperAdmin()) {
    header('Location: index.php');
    exit();
}

// Handle admin creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $username = sanitize($_POST['username']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $adminId = isset($_POST['admin_id']) ? $_POST['admin_id'] : null;
    
    // Only set password if it's provided (for updates) or if it's a new admin
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    
    try {
        if ($adminId) {
            // Update existing admin
            $sql = "UPDATE admins SET username = ?, name = ?, email = ?, role = ?";
            $params = [$username, $name, $email, $role];
            
            if ($password) {
                $sql .= ", password = ?";
                $params[] = $password;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $adminId;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $success = "Admin updated successfully";
        } else {
            // Create new admin
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $name, $email, $role]);
            
            $success = "Admin created successfully";
        }
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Handle admin deletion
if (isset($_POST['delete'])) {
    $adminId = $_POST['admin_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        
        $success = "Admin deleted successfully";
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Get all admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Admin Form -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                    <?php echo isset($_GET['edit']) ? 'Edit Admin' : 'Add New Admin'; ?>
                </h2>
                
                <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php
                    if (isset($_GET['edit'])) {
                        $editId = $_GET['edit'];
                        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
                        $stmt->execute([$editId]);
                        $admin = $stmt->fetch();
                    }
                    ?>
                    
                    <?php if (isset($admin)): ?>
                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="username">
                            Username
                        </label>
                        <input type="text" id="username" name="username" required
                            value="<?php echo isset($admin) ? htmlspecialchars($admin['username']) : ''; ?>"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="password">
                            <?php echo isset($admin) ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                        </label>
                        <input type="password" id="password" name="password" <?php echo !isset($admin) ? 'required' : ''; ?>
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="name">
                            Full Name
                        </label>
                        <input type="text" id="name" name="name" required
                            value="<?php echo isset($admin) ? htmlspecialchars($admin['name']) : ''; ?>"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="email">
                            Email
                        </label>
                        <input type="email" id="email" name="email" required
                            value="<?php echo isset($admin) ? htmlspecialchars($admin['email']) : ''; ?>"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="role">
                            Role
                        </label>
                        <select id="role" name="role" required
                            class="form-select w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="admin" <?php echo (isset($admin) && $admin['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="superadmin" <?php echo (isset($admin) && $admin['role'] === 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="submit"
                            class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                            <?php echo isset($admin) ? 'Update Admin' : 'Add Admin'; ?>
                        </button>
                        
                        <?php if (isset($admin)): ?>
                        <a href="?page=admins"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                            Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Admins List -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Admins</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" 
                                                src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=random"
                                                alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($admin['name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($admin['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $admin['role'] === 'superadmin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst($admin['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo date('M j, Y', strtotime($admin['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="?page=admins&edit=<?php echo $admin['id']; ?>"
                                        class="text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 mr-3">
                                        Edit
                                    </a>
                                    
                                    <?php if ($admin['id'] !== $_SESSION['admin_id']): ?>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" name="delete"
                                            class="text-red-500 hover:text-red-600 dark:hover:text-red-400"
                                            onclick="return confirm('Are you sure you want to delete this admin?')">
                                            Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>