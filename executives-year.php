<?php
require_once __DIR__ . '/config/db.php';

// Get the year parameter from either archive_year or year
$year = isset($_GET['archive_year']) ? $_GET['archive_year'] : (isset($_GET['year']) ? $_GET['year'] : null);

if (!$year) {
    header("Location: /executives");
    exit;
}

// If archive_year parameter is used, redirect to the correct format
if (isset($_GET['archive_year']) && !isset($_GET['year'])) {
    header("Location: /executives?year=" . $year);
    exit;
}

// Create archive directory if it doesn't exist
$archiveDir = __DIR__ . '/executives/archive';
if (!file_exists($archiveDir)) {
    mkdir($archiveDir, 0777, true);
}

// Get all available archive years
$archiveYearsStmt = $pdo->query("SELECT DISTINCT archive_year FROM executives WHERE archive_year IS NOT NULL ORDER BY archive_year DESC");
$archiveYears = $archiveYearsStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch executives for the given archive year
$stmt = $pdo->prepare("SELECT * FROM executives WHERE archive_year = ? ORDER BY sort_order ASC");
$stmt->execute([$year]);
$executives = $stmt->fetchAll();

// Organization Schema
$orgSchema = [
    "@context" => "https://schema.org",
    "@type" => "Organization",
    "name" => "BGC Science Club",
    "member" => []
];

// Add executives to organization schema
foreach ($executives as $executive) {
    $orgSchema["member"][] = [
        "@type" => "Person",
        "name" => $executive['name'],
        "description" => $executive['bio'],
        "url" => "http://" . $_SERVER['HTTP_HOST'] . "/executive/" . $executive['slug']
    ];
}

// Generate the static page content
$pageContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executives - Archive Year ' . htmlspecialchars($year) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Page Header -->
    <div class="bg-gray-100 py-8">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Executives - Archive Year ' . htmlspecialchars($year) . '</h1>
            <nav class="text-gray-500 text-sm">
                <a href="/" class="hover:text-purple-600">Home</a>
                <span class="mx-2">/</span>
                <a href="/executives" class="hover:text-purple-600">Executives</a>
                <span class="mx-2">/</span>
                <span>Archive - ' . htmlspecialchars($year) . '</span>
            </nav>
        </div>
    </div>

    <!-- Archive Year Filter -->
    <div class="container mx-auto px-4 py-4">
        <div class="bg-white rounded-lg shadow p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Archive Year:</label>
            <div class="flex flex-wrap gap-2">
                <a href="/executives" 
                    class="px-4 py-2 rounded-md bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors duration-200">
                    Current Committee
                </a>';

foreach ($archiveYears as $archiveYear) {
    $activeClass = $archiveYear == $year ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200';
    $pageContent .= '<a href="/executives?year=' . $archiveYear . '" 
        class="px-4 py-2 rounded-md ' . $activeClass . ' transition-colors duration-200">' . $archiveYear . '</a>';
}

$pageContent .= '</div>
        </div>
    </div>

    <!-- Executives Grid -->
    <div class="container mx-auto px-4 py-12">';

if (count($executives) > 0) {
    $pageContent .= '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">';
    foreach ($executives as $executive) {
        $pageContent .= '<div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
            <a href="/executive/' . htmlspecialchars($executive['slug']) . '" class="block">
                <div class="aspect-w-1 aspect-h-1">
                    <img 
                        src="' . ($executive['profile_pic'] ? '/uploads/executives/' . $executive['profile_pic'] : '/assets/images/default-avatar.jpg') . '"
                        alt="' . htmlspecialchars($executive['name']) . '"
                        class="object-cover w-full h-full"
                    >
                </div>
                <div class="p-4">
                    <h3 class="text-xl font-semibold text-gray-800 mb-1">' . htmlspecialchars($executive['name']) . '</h3>
                    <p class="text-purple-600 font-medium mb-2">' . htmlspecialchars($executive['role']) . '</p>
                    <p class="text-gray-600 text-sm">
                        ' . ucfirst($executive['type']) . ' • 
                        ' . htmlspecialchars($executive['department']) . '
                    </p>
                </div>
            </a>
        </div>';
    }
    $pageContent .= '</div>';
} else {
    $pageContent .= '<p class="text-center text-gray-600">No executives found for archive year ' . htmlspecialchars($year) . '.</p>';
}

$pageContent .= '</div>

    <!-- Organization Schema -->
    <script type="application/ld+json">
    ' . json_encode($orgSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '
    </script>
</body>
</html>';

// Save the static page
$filename = $archiveDir . '/' . $year . '.php';
file_put_contents($filename, $pageContent);

// Redirect to the newly created static page
header("Location: /executives?year=" . $year);
exit;
