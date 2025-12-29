<?php
// Simple sitemap generator — outputs separate sitemaps + index into /sitemap/
// Usage (CLI): php sitemap-generator.php https://example.com
// Usage (web): visit sitemap-generator.php (it will output complete sitemap)

// Require the site bootstrap
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Determine if this is CLI or web request
$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    // CLI MODE - Generate full sitemap structure with multiple files
    runCliSitemapGenerator();
} else {
    // WEB MODE - Output complete sitemap XML with all URLs
    outputCompleteWebSitemap();
}

function runCliSitemapGenerator() {
    global $pdo;
    
    // Determine base URL: CLI arg > settings.website_url > auto (HTTP_HOST)
    $baseUrl = null;
    global $argv;
    if (!empty($argv[1])) {
        $baseUrl = rtrim($argv[1], '/');
    }

    if (!$baseUrl) {
        $settings = getSiteSettings();
        if (!empty($settings['website_url'])) {
            $baseUrl = rtrim($settings['website_url'], '/');
        }
    }

    if (!$baseUrl) {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        } else {
            fwrite(STDERR, "Base URL was not provided and couldn't be determined from settings or environment.\n");
            exit(1);
        }
    }

    echo "Generating sitemap for: $baseUrl\n";

    // Prepare output directory
    $outDir = __DIR__ . '/sitemap';
    if (!file_exists($outDir)) {
        mkdir($outDir, 0755, true);
        chmod($outDir, 0755);
    }

    // Create .htaccess to allow access to XML files
    $htaccessContent = "# Allow access to sitemap files\n<Files \"*.xml\">\n    Require all granted\n</Files>\n\n# Prevent directory listing\nOptions -Indexes";
    file_put_contents($outDir . '/.htaccess', $htaccessContent);
    chmod($outDir . '/.htaccess', 0644);

    // Helper to write a sitemap file
    function write_sitemap($filename, $items) {
        global $outDir;
        
        // Don't create empty sitemaps
        if (empty($items)) {
            echo "WARNING: Skipping empty sitemap: $filename\n";
            // Remove existing empty file if it exists
            $path = $outDir . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
            return false;
        }
        
        $path = $outDir . '/' . $filename;
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($items as $it) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($it['loc']) . "</loc>\n";
            if (!empty($it['lastmod'])) $xml .= "    <lastmod>" . htmlspecialchars($it['lastmod']) . "</lastmod>\n";
            if (!empty($it['changefreq'])) $xml .= "    <changefreq>" . htmlspecialchars($it['changefreq']) . "</changefreq>\n";
            if (isset($it['priority'])) $xml .= "    <priority>" . htmlspecialchars($it['priority']) . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        if (file_put_contents($path, $xml) === false) {
            fwrite(STDERR, "Error writing file: $path\n");
            return false;
        }
        chmod($path, 0644);
        echo "Generated: $filename with " . count($items) . " URLs\n";
        return $path;
    }

    // Get all URLs
    $allUrls = getAllSitemapUrls($baseUrl);

    // Write per-section sitemaps (only non-empty ones)
    $files = [];
    $sitemaps = [
        'pages' => $allUrls['pages'],
        'activities' => $allUrls['activities'],
        'executives' => $allUrls['executives'],
        'projects' => $allUrls['projects'],
        'classes' => $allUrls['classes'],
        'departments' => $allUrls['departments'],
        'members' => $allUrls['members'],
    ];

    foreach ($sitemaps as $name => $urls) {
        if (!empty($urls)) {
            $filename = "sitemap-{$name}.xml";
            $path = write_sitemap($filename, $urls);
            if ($path) {
                $files[$name] = $path;
            }
        } else {
            echo "Skipping empty sitemap: $name\n";
        }
    }

    // Write sitemap index (only if we have sitemaps)
    if (!empty($files)) {
        $indexPath = $outDir . '/sitemap-index.xml';
        $indexXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $indexXml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($files as $name => $path) {
            $url = $baseUrl . '/sitemap/' . basename($path);
            $indexXml .= "  <sitemap>\n";
            $indexXml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $indexXml .= "    <lastmod>" . date('c') . "</lastmod>\n";
            $indexXml .= "  </sitemap>\n";
        }
        $indexXml .= '</sitemapindex>';

        if (file_put_contents($indexPath, $indexXml)) {
            chmod($indexPath, 0644);
            echo "Generated: sitemap-index.xml with " . count($files) . " sitemaps\n";
        } else {
            fwrite(STDERR, "Error writing sitemap index\n");
        }
    } else {
        fwrite(STDERR, "No sitemaps were generated - all sections were empty\n");
    }

    // Update robots.txt to include sitemap index and allow
    $robotsPath = __DIR__ . '/robots.txt';
    $robotsContents = "User-agent: *\nAllow: /\nSitemap: " . $baseUrl . '/sitemap/sitemap-index.xml' . "\n";
    if (file_put_contents($robotsPath, $robotsContents)) {
        echo "Updated: robots.txt\n";
    }

    echo "\nSitemap generation complete!\n";
    echo "Main index: $baseUrl/sitemap/sitemap-index.xml\n";
    if (!empty($files)) {
        echo "Individual sitemaps:\n";
        foreach ($files as $name => $path) {
            echo " - $baseUrl/sitemap/" . basename($path) . " (" . count($sitemaps[$name]) . " URLs)\n";
        }
    }
}

function outputCompleteWebSitemap() {
    global $pdo;
    
    // Set proper headers FIRST before any output
    header('Content-Type: application/xml; charset=utf-8');

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

    // Get ALL URLs for the complete sitemap
    $allUrls = getAllSitemapUrls($baseUrl);

    // Output XML directly - NO other output before this
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Output all pages (only non-empty sections)
    outputUrlSet($allUrls['pages']);
    outputUrlSet($allUrls['activities']);
    outputUrlSet($allUrls['executives']);
    outputUrlSet($allUrls['projects']);
    outputUrlSet($allUrls['classes']);
    outputUrlSet($allUrls['departments']);
    outputUrlSet($allUrls['members']);

    echo '</urlset>';
    
    // Exit immediately to prevent any other output
    exit;
}

function getAllSitemapUrls($baseUrl) {
    global $pdo;
    
    $result = [
        'pages' => [],
        'activities' => [],
        'executives' => [],
        'projects' => [],
        'classes' => [],
        'departments' => [],
        'members' => [],
        'activities_raw_data' => []
    ];

    // Static pages
    $staticRoutes = [
        '/' => '/',
        '/about' => '/about',
        '/about-developer' => '/about-developer',
        '/executives' => '/executives',
        '/members' => '/members',
        '/activities' => '/activities',
        '/projects' => '/projects',
        '/classes' => '/classes',
        '/departments' => '/departments',
        '/join' => '/join',
        '/logo' => '/logo',
        '/ticket' => '/ticket',
        '/search' => '/search'
    ];

    foreach ($staticRoutes as $slug) {
        $result['pages'][] = ['loc' => $baseUrl . $slug, 'lastmod' => date('c'), 'changefreq' => 'monthly', 'priority' => '0.7'];
    }

    // Activities - FIXED: Check if status column exists
    try {
        // Check if status column exists
        $checkStatus = $pdo->query("SHOW COLUMNS FROM activities LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            $actStmt = $pdo->query("SELECT id, title, date, description FROM activities WHERE status = 'published' ORDER BY date DESC");
        } else {
            $actStmt = $pdo->query("SELECT id, title, date, description FROM activities ORDER BY date DESC");
        }
        
        $actRows = $actStmt->fetchAll();
        $result['activities_raw_data'] = $actRows;
        
        foreach ($actRows as $r) {
            $slug = slugifyText($r['title']);
            $loc = $baseUrl . '/activity/' . $r['id'] . '-' . $slug;
            $result['activities'][] = ['loc' => $loc, 'lastmod' => date('c', strtotime($r['date'])), 'changefreq' => 'monthly', 'priority' => '0.6'];
        }
    } catch (Exception $e) {
        // Silently fail
        error_log("Activities sitemap error: " . $e->getMessage());
    }

    // Executives - FIXED: Check if status column exists
    try {
        // Check if status column exists
        $checkStatus = $pdo->query("SHOW COLUMNS FROM executives LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            $stmt = $pdo->query("SELECT slug, updated_at FROM executives WHERE slug IS NOT NULL AND slug != '' AND (status = 'published' OR status = 'active')");
        } else {
            $stmt = $pdo->query("SELECT slug, updated_at FROM executives WHERE slug IS NOT NULL AND slug != ''");
        }
        
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $loc = $baseUrl . '/executive/' . $r['slug'];
            $lastmod = !empty($r['updated_at']) ? date('c', strtotime($r['updated_at'])) : date('c');
            $result['executives'][] = ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => 'yearly', 'priority' => '0.6'];
        }
    } catch (Exception $e) {
        // Silently fail
        error_log("Executives sitemap error: " . $e->getMessage());
    }

    // PROJECTS - FIXED: No status column in projects table!
    try {
        // Simple query - no status column exists
        $stmt = $pdo->query("SELECT slug, date, created_at FROM projects WHERE slug IS NOT NULL AND slug != ''");
        $rows = $stmt->fetchAll();
        
        // Debug output in CLI mode
        if (php_sapi_name() === 'cli') {
            echo "Found " . count($rows) . " projects in database\n";
            if (count($rows) > 0) {
                echo "Project URLs to generate:\n";
            }
        }
        
        foreach ($rows as $r) {
            // Check if slug is valid
            if (empty($r['slug'])) {
                continue;
            }
            
            $loc = $baseUrl . '/project/' . $r['slug'];
            
            // Determine lastmod: use created_at if date is empty
            if (!empty($r['date'])) {
                $lastmod = date('c', strtotime($r['date']));
            } elseif (!empty($r['created_at'])) {
                $lastmod = date('c', strtotime($r['created_at']));
            } else {
                $lastmod = date('c');
            }
            
            $result['projects'][] = ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => 'monthly', 'priority' => '0.6'];
            
            // Debug output
            if (php_sapi_name() === 'cli') {
                echo "  - $loc\n";
            }
        }
        
        if (php_sapi_name() === 'cli' && empty($result['projects'])) {
            echo "Warning: No project URLs generated. Checking if slugs are empty...\n";
            
            // Debug: Show all projects with slugs
            $debugStmt = $pdo->query("SELECT id, slug, title FROM projects");
            $debugRows = $debugStmt->fetchAll();
            foreach ($debugRows as $debugRow) {
                echo "  Project ID {$debugRow['id']}: Slug = '{$debugRow['slug']}', Title = '{$debugRow['title']}'\n";
            }
        }
        
    } catch (Exception $e) {
        error_log("Projects sitemap error: " . $e->getMessage());
        if (php_sapi_name() === 'cli') {
            echo "ERROR fetching projects: " . $e->getMessage() . "\n";
        }
    }

    // Members - FIXED: Check if status column exists
    try {
        // Check if status column exists
        $checkStatus = $pdo->query("SHOW COLUMNS FROM members LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            $stmt = $pdo->query("SELECT slug, updated_at FROM members WHERE slug IS NOT NULL AND slug != '' AND (status = 'active' OR status = 'published')");
        } else {
            $stmt = $pdo->query("SELECT slug, updated_at FROM members WHERE slug IS NOT NULL AND slug != ''");
        }
        
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $loc = $baseUrl . '/member/' . $r['slug'];
            $lastmod = !empty($r['updated_at']) ? date('c', strtotime($r['updated_at'])) : date('c');
            $result['members'][] = ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => 'yearly', 'priority' => '0.5'];
        }
    } catch (Exception $e) {
        // Silently fail
        error_log("Members sitemap error: " . $e->getMessage());
    }

    // Classes
    try {
        $classOptions = getClassOptions();
        if (!empty($classOptions)) {
            foreach ($classOptions as $c) {
                $slug = slugifyText($c);
                $result['classes'][] = ['loc' => $baseUrl . '/class/' . $slug, 'lastmod' => date('c'), 'changefreq' => 'monthly', 'priority' => '0.5'];
            }
        }
    } catch (Exception $e) {
        // Silently fail
        error_log("Classes sitemap error: " . $e->getMessage());
    }

    // Departments
    try {
        $deptOptions = getDepartmentOptions();
        if (!empty($deptOptions)) {
            foreach ($deptOptions as $d) {
                $slug = slugifyText($d);
                $result['departments'][] = ['loc' => $baseUrl . '/department/' . $slug, 'lastmod' => date('c'), 'changefreq' => 'monthly', 'priority' => '0.5'];
            }
        }
    } catch (Exception $e) {
        // Silently fail
        error_log("Departments sitemap error: " . $e->getMessage());
    }

    return $result;
}

function outputUrlSet($urls) {
    foreach ($urls as $item) {
        echo "<url>\n";
        echo "<loc>" . htmlspecialchars($item['loc']) . "</loc>\n";
        if (!empty($item['lastmod'])) echo "<lastmod>" . htmlspecialchars($item['lastmod']) . "</lastmod>\n";
        if (!empty($item['changefreq'])) echo "<changefreq>" . htmlspecialchars($item['changefreq']) . "</changefreq>\n";
        if (!empty($item['priority'])) echo "<priority>" . htmlspecialchars($item['priority']) . "</priority>\n";
        echo "</url>\n";
    }
}
?>