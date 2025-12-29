<?php
// Departments admin CRUD page
if (!defined('ADMIN')) exit();

$settings = getSiteSettings(true);
$deptMeta = $settings['department_options_meta'] ?? [];

// Handle add/edit/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $emoji = trim($_POST['emoji'] ?? '');
        if ($name !== '') {
            $deptMeta[] = ['name' => $name, 'emoji' => $emoji];
        }
    } elseif ($action === 'edit') {
        $i = intval($_POST['index'] ?? -1);
        if ($i >= 0 && isset($deptMeta[$i])) {
            $deptMeta[$i]['name'] = trim($_POST['name'] ?? $deptMeta[$i]['name']);
            $deptMeta[$i]['emoji'] = trim($_POST['emoji'] ?? $deptMeta[$i]['emoji']);
        }
    } elseif ($action === 'delete') {
        $i = intval($_POST['index'] ?? -1);
        if ($i >= 0 && isset($deptMeta[$i])) {
            array_splice($deptMeta, $i, 1);
        }
    }

    // Persist changes to settings table
    try {
        // First, get the current settings
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentSettings) {
            // Function to remove emojis
            function removeEmoji($text) {
                // Match Emoticons
                $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
                $text = preg_replace($regexEmoticons, '', $text);
                
                // Match Miscellaneous Symbols and Pictographs
                $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
                $text = preg_replace($regexSymbols, '', $text);
                
                // Match Transport And Map Symbols
                $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
                $text = preg_replace($regexTransport, '', $text);
                
                // Match Miscellaneous Symbols
                $regexMisc = '/[\x{2600}-\x{26FF}]/u';
                $text = preg_replace($regexMisc, '', $text);
                
                // Match Dingbats
                $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
                $text = preg_replace($regexDingbats, '', $text);
                
                return trim($text);
            }
            
            // Get the current department options with emojis removed
            $departmentOptions = array_map(function($m) use (&$removeEmoji) { 
                return [
                    'name' => removeEmoji(trim($m['name'])), 
                    'emoji' => '' // Remove emoji entirely to avoid encoding issues
                ]; 
            }, $deptMeta);
            
            // Update the settings
            $jsonOptions = json_encode($departmentOptions, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON encode error: ' . json_last_error_msg());
            }
            
            $stmt = $pdo->prepare("UPDATE settings SET department_options = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$jsonOptions, $currentSettings['id']]);
            
            $success = 'Departments updated successfully.';
            // Refresh the settings
            $settings = getSiteSettings(true);
            $deptMeta = $settings['department_options_meta'] ?? [];
        } else {
            $error = 'Settings not found in database.';
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $error = 'Unable to save departments: ' . $e->getMessage();
    }
}
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Manage Departments</h2>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>

    <div class="mb-6">
        <form method="POST" class="flex gap-2 items-center">
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" placeholder="Department name" class="px-3 py-2 border rounded-md w-64" required>
            <input type="text" name="emoji" placeholder="Emoji (optional)" class="px-3 py-2 border rounded-md w-32">
            <button class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md">Add department</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-2 text-left">#</th>
                    <th class="px-6 py-2 text-left">Emoji</th>
                    <th class="px-6 py-2 text-left">Name</th>
                    <th class="px-6 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($deptMeta as $i => $d): ?>
                <tr>
                    <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-200"><?php echo $i+1; ?></td>
                    <td class="px-6 py-3 text-sm"><?php echo htmlspecialchars($d['emoji'] ?? ''); ?></td>
                    <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($d['name']); ?></td>
                    <td class="px-6 py-3 text-sm">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="showEdit(<?= $i ?>, '<?= addslashes($d['name']) ?>', '<?= addslashes($d['emoji'] ?? '') ?>')" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 rounded-md">Edit</button>
                            <form method="POST" onsubmit="return confirm('Delete department?');" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="index" value="<?= $i ?>">
                                <button class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-md">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deptMeta)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-6 text-center text-gray-500">No departments added yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Simple edit modal -->
<div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden" style="backdrop-filter: blur(2px);">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Edit Department</h3>
        <form id="editForm" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editIndex" name="index">
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-300">Name</label>
                <input id="editName" name="name" type="text" class="w-full px-3 py-2 border rounded-md" required>
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-300">Emoji</label>
                <input id="editEmoji" name="emoji" type="text" class="w-full px-3 py-2 border rounded-md">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEdit()" class="px-4 py-2 border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Function to show edit modal
function showEdit(index, name, emoji) {
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    document.getElementById('editIndex').value = index;
    document.getElementById('editName').value = name || '';
    document.getElementById('editEmoji').value = emoji || '';
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeEdit();
        }
    });
}

// Function to close edit modal
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal when pressing Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEdit();
    }
});
</script>
