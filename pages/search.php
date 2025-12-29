<?php
// Set JSON content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../../../config/db.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Get query parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$departmentId = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$classLevelId = isset($_GET['class_level']) ? (int)$_GET['class_level'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12; // Members per page

try {
    // Use existing PDO connection
    global $pdo;
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }
    
    // Base query
    $query = "SELECT m.*, d.name as department_name, c.name as class_name 
              FROM members m
              LEFT JOIN departments d ON m.department_id = d.id
              LEFT JOIN classes c ON m.class_id = c.id
              WHERE 1=1";
    
    $params = [];
    $types = ''; // For prepared statement parameter types
    
    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (m.name LIKE ? OR m.email LIKE ? OR m.roll_number LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    if ($departmentId > 0) {
        $query .= " AND m.department_id = ?";
        $params[] = $departmentId;
        $types .= 'i';
    }
    
    if ($classLevelId > 0) {
        $query .= " AND m.class_id = ?";
        $params[] = $classLevelId;
        $types .= 'i';
    }
    
    // Count total records for pagination
    $countQuery = str_replace('m.*, d.name as department_name, c.name as class_name', 'COUNT(*) as total', $query);
    $countStmt = $pdo->prepare($countQuery);
    
    // Bind parameters if any
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // All parameters are treated as strings for LIKE
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $result = $countStmt->get_result();
    $totalRecords = (int)$result->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Add pagination to main query
    $offset = ($page - 1) * $perPage;
    $query .= " ORDER BY m.name ASC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    // Execute query
    $stmt = $pdo->prepare($query);
    
    // Bind parameters if any
    if (!empty($params)) {
        $types = str_repeat('s', count($params) - 2) . 'ii'; // All previous params as strings, last two as integers
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $members = [];
    
    while ($row = $result->fetch_assoc()) {
        $members[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'roll_number' => $row['roll_number'],
            'department_name' => $row['department_name'],
            'class_name' => $row['class_name'],
            'photo_url' => !empty($row['photo']) ? '/uploads/members/' . $row['photo'] : null,
            'role' => $row['role'],
            'joined_year' => !empty($row['joined_date']) ? date('Y', strtotime($row['joined_date'])) : null
        ];
    }
    
    // Format response
    $response = [
        'success' => true,
        'members' => $members,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalRecords,
            'total_pages' => $totalPages
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
