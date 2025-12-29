<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];

                header('Location: index.php');
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "Invalid username. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again later.";
        error_log($e->getMessage()); // Log the error for debugging
    }
}

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BGC Science Club</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="font-poppins antialiased min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <img src="https://nabilbinbillal.github.io/scienceclub.jpg" alt="BGC Science Club" class="mx-auto h-16 w-auto">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                Admin Login
            </h2>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="bg-white dark:bg-gray-800 shadow rounded-lg px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="username">
                    Username
                </label>
                <input type="text" id="username" name="username" required
                    class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input type="password" id="password" name="password" required
                    class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit"
                    class="w-full bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Sign In
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <a href="../index.php" class="text-primary-500 hover:text-primary-600 dark:text-primary-400">
                ← Back to Website
            </a>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>


