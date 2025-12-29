<?php
// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    try {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (password_verify($currentPassword, $admin['password'])) {
            if ($newPassword === $confirmPassword) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                
                $success = "Password updated successfully";
            } else {
                $error = "New passwords do not match";
            }
        } else {
            $error = "Current password is incorrect";
        }
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    
    try {
        $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, $_SESSION['admin_id']]);
        
        $_SESSION['admin_name'] = $name;
        $success = "Profile updated successfully";
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Get current admin data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Profile Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Profile Settings</h2>
            
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
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="name">
                        Full Name
                    </label>
<input type="text" id="name" name="name" required
                        value="<?php echo htmlspecialchars($admin['name']); ?>"
                        class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
<input type="email" id="email" name="email" required
                        value="<?php echo htmlspecialchars($admin['email']); ?>"
                        class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        Role
                    </label>
                    <div class="form-group">
                        <label>Role</label>
                        <?php if (isSuperAdmin()): ?>
                            <select name="role" class="form-control">
                                <option value="admin" <?php echo ($admin['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="superadmin" <?php echo ($admin['role'] === 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['role']); ?>" readonly>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" name="update_profile"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Update Profile
                </button>
            </form>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Change Password</h2>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="current_password">
                        Current Password
                    </label>
<input type="password" id="current_password" name="current_password" required
                        class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="new_password">
                        New Password
                    </label>
<input type="password" id="new_password" name="new_password" required
                        class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="confirm_password">
                        Confirm New Password
                    </label>
<input type="password" id="confirm_password" name="confirm_password" required
                        class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <button type="submit" name="change_password"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</div>