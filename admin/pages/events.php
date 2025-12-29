<?php
// Prevent direct access
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Location: ../index.php?page=events');
    exit();
}

require_once __DIR__ . '/../../config/db.php';
require_once '../includes/functions.php';

// Handle add/edit/delete event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $event_date = $_POST['event_date'];
    $description = sanitize($_POST['description']);
    $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : null;

    try {
        if ($event_id) {
            // Update event
            $stmt = $pdo->prepare("UPDATE events SET name=?, event_date=?, description=? WHERE id=?");
            $stmt->execute([$name, $event_date, $description, $event_id]);
            $success = "Event updated successfully.";
        } else {
            // Add event
            $stmt = $pdo->prepare("INSERT INTO events (name, event_date, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $event_date, $description]);
            $success = "Event added successfully.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$deleteId]);
        $success = "Event deleted successfully.";
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

// Get events
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();

// If editing, get event data
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$editId]);
    $event = $stmt->fetch();
}
?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6"><?php echo isset($event) ? 'Edit Event' : 'Add New Event'; ?></h2>
    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <?php if (isset($event)): ?>
            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
        <?php endif; ?>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Event Name</label>
            <input type="text" name="name" required value="<?php echo isset($event) ? htmlspecialchars($event['name']) : ''; ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Event Date</label>
            <input type="date" name="event_date" required value="<?php echo isset($event) ? $event['event_date'] : ''; ?>" class="form-input w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description</label>
            <textarea name="description" rows="3" class="form-textarea w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo isset($event) ? htmlspecialchars($event['description']) : ''; ?></textarea>
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-4 rounded">
                <?php echo isset($event) ? 'Update Event' : 'Add Event'; ?>
            </button>
            <?php if (isset($event)): ?>
                <a href="index.php?page=events" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">All Events</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($ev['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($ev['event_date']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php
                            $today = '2025-04-20';
                            if ($ev['event_date'] > $today) {
                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Upcoming</span>';
                            } else {
                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">' . htmlspecialchars($ev['event_date']) . '</span>';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="index.php?page=events&edit=<?php echo $ev['id']; ?>" class="text-primary-500 hover:text-primary-600 mr-3">Edit</a>
                            <a href="index.php?page=events&delete=<?php echo $ev['id']; ?>" class="text-red-500 hover:text-red-600" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>