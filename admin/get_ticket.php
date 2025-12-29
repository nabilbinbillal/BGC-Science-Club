<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized access');
}

if (!isset($_GET['id'])) {
    die('Ticket ID not provided');
}

$ticket_id = (int)$_GET['id'];

try {
    // Get ticket details with member information
    $stmt = $pdo->prepare("
        SELECT t.*, m.name as member_name, m.email as member_email 
        FROM tickets t 
        LEFT JOIN members m ON t.member_id = m.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        die('Ticket not found');
    }

    // Get ticket responses
    $stmt = $pdo->prepare("
        SELECT r.*, 
            CASE 
                WHEN r.response_by = 'admin' THEN a.name 
                ELSE m.name 
            END as responder_name
        FROM ticket_responses r
        LEFT JOIN admins a ON r.response_by = 'admin' AND r.user_id = a.id
        LEFT JOIN members m ON r.response_by = 'member' AND r.user_id = m.id
        WHERE r.ticket_id = ?
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $responses = $stmt->fetchAll();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Ticket #<?php echo $ticket['id']; ?></h2>
        <button onclick="document.getElementById('ticketModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <div class="space-y-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Member Name</h3>
                    <p class="mt-1"><?php echo htmlspecialchars($ticket['member_name']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Member Email</h3>
                    <p class="mt-1"><?php echo htmlspecialchars($ticket['member_email']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <p class="mt-1"><?php echo ucfirst($ticket['status']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Created</h3>
                    <p class="mt-1"><?php echo date('M j, Y H:i', strtotime($ticket['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-medium mb-2">Subject</h3>
            <p class="bg-white p-4 rounded-lg"><?php echo htmlspecialchars($ticket['subject']); ?></p>
        </div>

        <div>
            <h3 class="text-lg font-medium mb-2">Message</h3>
            <div class="bg-white p-4 rounded-lg whitespace-pre-wrap">
                <?php echo htmlspecialchars($ticket['message']); ?>
            </div>
        </div>

        <?php if (!empty($responses)): ?>
        <div>
            <h3 class="text-lg font-medium mb-4">Responses</h3>
            <div class="space-y-4">
                <?php foreach ($responses as $response): ?>
                <div class="bg-white p-4 rounded-lg">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($response['responder_name']); ?></span>
                            <span class="text-gray-500 text-sm">(<?php echo ucfirst($response['response_by']); ?>)</span>
                        </div>
                        <span class="text-sm text-gray-500">
                            <?php echo date('M j, Y H:i', strtotime($response['created_at'])); ?>
                        </span>
                    </div>
                    <p class="whitespace-pre-wrap"><?php echo htmlspecialchars($response['message']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div>
            <h3 class="text-lg font-medium mb-2">Add Response</h3>
            <form method="POST" action="tickets.php" class="space-y-4">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <input type="hidden" name="action" value="respond">
                
                <textarea name="response" rows="4" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Type your response here..."></textarea>
                
                <div class="flex justify-end space-x-2">
                    <?php if ($ticket['status'] === 'open'): ?>
                    <button type="submit" name="action" value="in_progress"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Start Progress
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($ticket['status'] === 'in_progress'): ?>
                    <button type="submit" name="action" value="resolve"
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        Resolve
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($ticket['status'] !== 'closed'): ?>
                    <button type="submit" name="action" value="close"
                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                        Close
                    </button>
                    <?php endif; ?>
                    
                    <button type="submit" name="action" value="respond"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
} catch (PDOException $e) {
    echo "Error loading ticket details: " . htmlspecialchars($e->getMessage());
}
?> 