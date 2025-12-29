<?php
require_once '../config/db.php';

try {
    // Check database connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection is successful.<br>";

    // Check if the admins table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "The 'admins' table exists.<br>";
    } else {
        echo "The 'admins' table does not exist. Please run the SQL script to create it.<br>";
    }

    // Check if there are any admin users
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM admins");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "Admin users are present in the database.<br>";
    } else {
        echo "No admin users found. Please insert an admin user.<br>";
    }

    if (isset($_POST['update_settings'])) {
        $admin_id = $_POST['admin_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $query = "UPDATE admins SET username = ?, email = ?";
        $params = [$username, $email];
        
        // Only allow role updates for superadmins
        if (isSuperAdmin() && isset($_POST['role'])) {
            $role = $_POST['role'];
            $query .= ", role = ?";
            $params[] = $role;
        }
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query .= ", password = ?";
            $params[] = $hashed_password;
        }
        
        $query .= " WHERE id = ?";
        $params[] = $admin_id;
        
        $stmt = $conn->prepare($query);
        if ($stmt->execute($params)) {
            $_SESSION['message'] = "Settings updated successfully";
            $_SESSION['success'] = true;
        } else {
            $_SESSION['message'] = "Error updating settings";
            $_SESSION['success'] = false;
        }
        header("Location: pages/settings.php");
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>