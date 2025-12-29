<?php
// Handle activity creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $date = $_POST['date'];
    $link = sanitize($_POST['link']); // Add activity link
    $activityId = isset($_POST['activity_id']) ? $_POST['activity_id'] : null;
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/activities/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Use centralized helper (compress + convert to webp) and return final filename
        if (function_exists('uploadMemberImage')) {
            $result = uploadMemberImage($_FILES['image'], $uploadDir);
            if ($result) $image = $result;
        } else {
            // fallback: simple move
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '.' . $fileExt;
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $fileName;
            }
        }
    }
    
    try {
        if ($activityId) {
            // Update existing activity
            $sql = "UPDATE activities SET title = ?, description = ?, date = ?, link = ?";
            $params = [$title, $description, $date, $link];
            
            if ($image) {
                $sql .= ", image = ?";
                $params[] = $image;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $activityId;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $success = "Activity updated successfully";
        } else {
            // Create new activity
            $stmt = $pdo->prepare("INSERT INTO activities (title, description, date, link, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $date, $link, $image]);
            
            $success = "Activity created successfully";
        }
    } catch (PDOException $e) {
        $error = "An error occurred: " . $e->getMessage(); // Show real error for debugging
    }
}

// Handle activity deletion
if (isset($_POST['delete'])) {
    $activityId = $_POST['activity_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->execute([$activityId]);
        
        $success = "Activity deleted successfully";
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Get all activities
$stmt = $pdo->query("SELECT * FROM activities ORDER BY date DESC");
$activities = $stmt->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Activity Form -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                    <?php echo isset($_GET['edit']) ? 'Edit Activity' : 'Add New Activity'; ?>
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
                
                <form method="POST" enctype="multipart/form-data">
                    <?php
                    if (isset($_GET['edit'])) {
                        $editId = $_GET['edit'];
                        $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
                        $stmt->execute([$editId]);
                        $activity = $stmt->fetch();
                    }
                    ?>
                    
                    <?php if (isset($activity)): ?>
                    <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="title">
                            Title
                        </label>
                        <input type="text" id="title" name="title" required
                            value="<?php echo isset($activity) ? htmlspecialchars($activity['title']) : ''; ?>"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="description">
                            Description
                        </label>
                        <textarea id="description" name="description" required rows="4"
                            class="form-textarea w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo isset($activity) ? htmlspecialchars($activity['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="date">
                            Date
                        </label>
                        <input type="date" id="date" name="date" required
                            value="<?php echo isset($activity) ? $activity['date'] : ''; ?>"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="link">
                            Link
                        </label>
                        <input type="url" id="link" name="link"
                            value="<?php echo isset($activity) ? htmlspecialchars($activity['link']) : ''; ?>"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="image">
                            Image
                        </label>
                        <input type="file" id="image" name="image" accept="image/*"
                            class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="submit"
                            class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                            <?php echo isset($activity) ? 'Update Activity' : 'Add Activity'; ?>
                        </button>
                        
                        <?php if (isset($activity)): ?>
                        <a href="?page=activities"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                            Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Activities List -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Activities</h2>
                
                <div class="space-y-6">
                    <?php foreach ($activities as $activity): ?>
                    <div class="flex items-center bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <?php if ($activity['image']): ?>
                        <?php $disp = function_exists('pickBestUploadFilename') ? pickBestUploadFilename('activities', $activity['image']) : $activity['image']; ?>
                        <img src="../uploads/activities/<?php echo htmlspecialchars($disp); ?>" 
                            alt="<?php echo htmlspecialchars($activity['title']); ?>"
                            class="h-24 w-24 object-cover rounded-lg">
                        <?php else: ?>
                        <div class="h-24 w-24 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-gray-400 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="ml-4 flex-grow">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($activity['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                <?php echo formatDate($activity['date']); ?>
                            </p>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo substr(htmlspecialchars($activity['description']), 0, 100) . '...'; ?>
                            </p>
                        </div>
                        
                        <div class="ml-4 flex items-center space-x-2">
                            <a href="?page=activities&edit=<?php echo $activity['id']; ?>"
                                class="text-primary-500 hover:text-primary-600 dark:hover:text-primary-400">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <form method="POST" class="inline-block">
                                <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                <button type="submit" name="delete" 
                                    class="text-red-500 hover:text-red-600 dark:hover:text-red-400"
                                    onclick="return confirm('Are you sure you want to delete this activity?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>