<?php
// Get filter parameters (if any)
$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : 'current';

// Get distinct archive years for dropdown
$yearStmt = $pdo->query("SELECT DISTINCT archive_year FROM executives WHERE archive_year IS NOT NULL ORDER BY archive_year DESC");
$archiveYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// Prepare the query with filters
$query = "SELECT * FROM executives WHERE 1=1";
$params = [];

if ($selectedType) {
    $query .= " AND type = ?";
    $params[] = $selectedType;
}

if ($selectedYear === 'current') {
    $query .= " AND archive_year IS NULL";
} elseif ($selectedYear !== 'all') {
    $query .= " AND archive_year = ?";
    $params[] = $selectedYear;
}

// Add sorting
$query .= " ORDER BY executives.sort_order ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
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
?>

<!-- Page Header -->
<div class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Our Executives</h1>
        <nav class="text-gray-500 text-sm">
            <a href="/" class="hover:text-purple-600">Home</a>
            <span class="mx-2">/</span>
            <span>Executives</span>
        </nav>
    </div>
</div>


<!-- Filter Form -->
<div class="container mx-auto px-4 py-6">
    <div class="flex space-x-4">
        <div>
            <select id="typeFilter" name="type" class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Types</option>
                <option value="Student" <?php echo $selectedType == 'Student' ? 'selected' : ''; ?>>Student</option>
                <option value="Teacher" <?php echo $selectedType == 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
            </select>
        </div>
        <div>
            <select id="yearFilter" name="year" class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="current" <?php echo $selectedYear == 'current' ? 'selected' : ''; ?>>Current Committee</option>
                <option value="all" <?php echo $selectedYear == 'all' ? 'selected' : ''; ?>>All Committees</option>
                <?php foreach ($archiveYears as $year): ?>
                    <option value="<?php echo htmlspecialchars($year); ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                        Executives - <?php echo htmlspecialchars($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<script>
document.getElementById('yearFilter').addEventListener('change', function() {
    const year = this.value;
    if (year === 'current') {
        window.location.href = '/executives';
    } else if (year === 'all') {
        window.location.href = '/executives?year=all';
    } else {
        window.location.href = '/executives-year.php?year=' + encodeURIComponent(year);
    }
});

// Add event listener for type filter
document.getElementById('typeFilter').addEventListener('change', function() {
    const type = this.value;
    const year = document.getElementById('yearFilter').value;
    let url = '/executives';
    const params = new URLSearchParams();
    
    if (type) {
        params.append('type', type);
    }
    
    if (year !== 'current') {
        params.append('year', year);
    }
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    window.location.href = url;
});
</script>

<!-- Executives Grid -->
<div class="container mx-auto px-4 py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($executives as $executive): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <a href="/executive/<?php echo htmlspecialchars($executive['slug']); ?>" class="block">
                    <div class="aspect-w-1 aspect-h-1">
                        <img 
                            src="<?php echo $executive['profile_pic'] ? '/uploads/executives/' . $executive['profile_pic'] : 'assets/images/default-avatar.jpg'; ?>"
                            alt="<?php echo htmlspecialchars($executive['name']); ?>"
                            class="object-cover w-full h-full"
                        >
                    </div>
                    <div class="p-4">
                        <h3 class="text-xl font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($executive['name']); ?></h3>
                        <p class="text-purple-600 font-medium mb-2"><?php echo htmlspecialchars($executive['role']); ?></p>
                        <p class="text-gray-600 text-sm">
                            <?php echo ucfirst($executive['type']); ?> • 
                            <?php echo htmlspecialchars($executive['department']); ?>
                        </p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Organization Schema -->
<script type="application/ld+json">
<?php echo json_encode($orgSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
</script>
