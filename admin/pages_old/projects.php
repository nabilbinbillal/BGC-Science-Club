<?php

require_once __DIR__ . '/../../config/db.php';
require_once '../includes/functions.php';

$classOptions = getClassOptions();
$departmentOptions = getDepartmentOptions();
$errors = [];

// Handle add/edit/delete project
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    $title = sanitize($_POST['title'] ?? '');
    $shortDescription = sanitize($_POST['short_description'] ?? '');
    $longDescription = trim($_POST['long_description'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');
    $link = sanitize($_POST['link'] ?? '');
    $slugInput = $_POST['slug'] ?? '';
    $classScope = isset($_POST['class_scope']) ? array_values(array_unique(array_map('trim', (array)$_POST['class_scope']))) : [];
    $departmentScope = isset($_POST['department_scope']) ? array_values(array_unique(array_map('trim', (array)$_POST['department_scope']))) : [];
    $isCollaborative = isset($_POST['is_collaborative']) ? 1 : 0;
    $contributorsRaw = $_POST['contributors'] ?? '';

    if (empty($title)) {
        $errors[] = "Project title is required.";
    }
    if (empty($date)) {
        $errors[] = "Project date is required.";
    }

    $contributorIds = array_filter(array_map('trim', preg_split('/[\s,]+/', $contributorsRaw)));
    $contributorIds = array_values(array_unique($contributorIds));

    if (!empty($contributorIds)) {
        $placeholders = implode(',', array_fill(0, count($contributorIds), '?'));
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id IN ($placeholders)");
        $stmt->execute($contributorIds);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_diff($contributorIds, $existing);
        if (!empty($missing)) {
            $errors[] = "Unknown member IDs: " . implode(', ', $missing);
        }
    }

    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/projects/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid() . '.' . $fileExt;
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = $fileName;
        }
    }

    if (empty($errors)) {
        try {
            $slugBase = !empty($slugInput) ? $slugInput : $title;
            $classScope = array_values(array_intersect($classScope, $classOptions));
            $departmentScope = array_values(array_intersect($departmentScope, $departmentOptions));
            $slug = generateProjectSlug($slugBase, $project_id);
            $classScopeJson = json_encode($classScope, JSON_UNESCAPED_UNICODE);
            $departmentScopeJson = json_encode($departmentScope, JSON_UNESCAPED_UNICODE);
            $contributorsJson = json_encode($contributorIds, JSON_UNESCAPED_UNICODE);
            $legacyDescription = $shortDescription ?: $longDescription;

            if ($project_id) {
                $sql = "UPDATE projects SET title=?, short_description=?, description=?, long_description=?, date=?, link=?, slug=?, class_scope=?, department_scope=?, contributor_ids=?, is_collaborative=?";
                $params = [$title, $shortDescription, $legacyDescription, $longDescription, $date, $link, $slug, $classScopeJson, $departmentScopeJson, $contributorsJson, $isCollaborative];
                if ($image) {
                    $sql .= ", image=?";
                    $params[] = $image;
                }
                $sql .= " WHERE id=?";
                $params[] = $project_id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success = "Project updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO projects (title, short_description, description, long_description, date, link, slug, class_scope, department_scope, contributor_ids, is_collaborative, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $shortDescription, $legacyDescription, $longDescription, $date, $link, $slug, $classScopeJson, $departmentScopeJson, $contributorsJson, $isCollaborative, $image]);
                $success = "Project added successfully.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again.";
        }
    } else {
        $error = implode(' ', $errors);
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$deleteId]);
        $success = "Project deleted successfully.";
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Get projects
$stmt = $pdo->query("SELECT * FROM projects ORDER BY date DESC");
$projects = array_map(function ($project) {
    return normalizeProjectRecord($project);
}, $stmt->fetchAll());

// If editing, get project data
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$editId]);
    $project = normalizeProjectRecord($stmt->fetch());
}
?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6"><?php echo isset($project) ? 'Edit Project' : 'Add New Project'; ?></h2>
    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <?php if (isset($project)): ?>
            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
        <?php endif; ?>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Project Title</label>
            <input type="text" name="title" required value="<?php echo isset($project) ? htmlspecialchars($project['title']) : ''; ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Short Description</label>
            <input type="text" name="short_description" maxlength="255" value="<?php echo isset($project) ? htmlspecialchars($project['short_description']) : ''; ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="One line summary (max 255 characters)">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Long Description</label>
            <textarea name="long_description" rows="5" class="form-textarea w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Explain the project goals, outcomes, methods..."><?php echo isset($project) ? htmlspecialchars($project['long_description']) : ''; ?></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Date</label>
            <input type="date" name="date" required value="<?php echo isset($project) ? $project['date'] : date('Y-m-d'); ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Project Link</label>
            <input type="url" name="link" value="<?php echo isset($project) ? htmlspecialchars($project['link']) : ''; ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Custom Slug</label>
            <input type="text" name="slug" value="<?php echo isset($project) ? htmlspecialchars($project['slug']) : ''; ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="leave blank to auto generate">
            <p class="text-xs text-gray-500 mt-1">Slug controls the project URL (e.g. /project/your-slug).</p>
        </div>
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Classes Involved</label>
                <select name="class_scope[]" multiple class="w-full px-4 py-2 border rounded-md h-32 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <?php
                    $selectedClasses = isset($project) ? $project['class_scope'] : [];
                    foreach ($classOptions as $classOption): ?>
                        <option value="<?php echo htmlspecialchars($classOption); ?>" <?php echo in_array($classOption, $selectedClasses) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple classes.</p>
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Departments Involved</label>
                <select name="department_scope[]" multiple class="w-full px-4 py-2 border rounded-md h-32 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <?php
                    $selectedDepartments = isset($project) ? $project['department_scope'] : [];
                    foreach ($departmentOptions as $deptOption): ?>
                        <option value="<?php echo htmlspecialchars($deptOption); ?>" <?php echo in_array($deptOption, $selectedDepartments) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($deptOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Leave empty if the project is open to all departments.</p>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Contributor Member IDs</label>
            <textarea name="contributors" rows="3" class="form-textarea w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="BGC-24-0001, BGC-24-0002"><?php echo isset($project) ? htmlspecialchars(implode(', ', $project['contributor_ids'])) : ''; ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Add member IDs separated by comma or newline.</p>
        </div>
        <div class="mb-4 flex items-center space-x-3">
            <input type="checkbox" id="is_collaborative" name="is_collaborative" class="h-4 w-4 text-primary-500" <?php echo isset($project) && $project['is_collaborative'] ? 'checked' : ''; ?>>
            <label for="is_collaborative" class="text-gray-700 dark:text-gray-300 text-sm font-medium">Club-wide collaborative project</label>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Image</label>
            <input type="file" name="image" accept="image/*" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <?php if (isset($project) && $project['image']): ?>
                <img src="../uploads/projects/<?php echo $project['image']; ?>" alt="Project Image" class="h-16 mt-2 rounded">
            <?php endif; ?>
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded">
                <?php echo isset($project) ? 'Update Project' : 'Add Project'; ?>
            </button>
            <?php if (isset($project)): ?>
                <a href="index.php?page=projects" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">All Projects</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Link</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scope</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contributors</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($projects as $prj): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($prj['title']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">/project/<?php echo htmlspecialchars($prj['slug']); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($prj['date']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($prj['link']): ?>
                                <a href="<?php echo htmlspecialchars($prj['link']); ?>" target="_blank" class="text-primary-500 hover:underline">View</a>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($prj['image']): ?>
                                <img src="../uploads/projects/<?php echo $prj['image']; ?>" alt="Project Image" class="h-10 rounded">
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                            <?php if (!empty($prj['class_scope'])): ?>
                                <div><span class="text-xs uppercase text-gray-500">Classes:</span> <?php echo htmlspecialchars(implode(', ', $prj['class_scope'])); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($prj['department_scope'])): ?>
                                <div><span class="text-xs uppercase text-gray-500">Departments:</span> <?php echo htmlspecialchars(implode(', ', $prj['department_scope'])); ?></div>
                            <?php endif; ?>
                            <?php if ($prj['is_collaborative']): ?>
                                <span class="inline-flex mt-1 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Club-wide</span>
                            <?php endif; ?>
                            <?php if (empty($prj['class_scope']) && empty($prj['department_scope']) && !$prj['is_collaborative']): ?>
                                <span class="text-xs text-gray-500">General</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                            <?php
                            $count = count($prj['contributor_ids']);
                            echo $count ? $count . ' member' . ($count > 1 ? 's' : '') : 'Not set';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="index.php?page=projects&edit=<?php echo $prj['id']; ?>" class="text-primary-500 hover:text-primary-600 mr-3">Edit</a>
                            <a href="index.php?page=projects&delete=<?php echo $prj['id']; ?>" class="text-red-500 hover:text-red-600" onclick="return confirm('Are you sure you want to delete this project?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
