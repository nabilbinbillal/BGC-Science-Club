<?php
// convert_to_webp.php
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Load database configuration
$dbConfig = __DIR__ . '/config/db.php';
if (!file_exists($dbConfig)) {
    die("Error: Database configuration file not found at: " . $dbConfig . "\n");
}

// Include the database configuration which will set up $pdo
require_once $dbConfig;

// Check if $pdo was created successfully
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Error: Failed to initialize database connection. Please check your database configuration.\n");
}

class ImageConverter {
    private $pdo;
    private $basePath;
    private $directories = [
        'members' => 'members',
        'executives' => 'executives',
        'activities' => 'activities',
        'projects' => 'projects'
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->basePath = __DIR__ . '/uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function convertAll() {
        foreach ($this->directories as $type => $dir) {
            $fullPath = $this->basePath . $dir . '/';
            if (!is_dir($fullPath)) {
                echo "Creating directory: $fullPath\n";
                mkdir($fullPath, 0755, true);
            }
            $this->processDirectory($fullPath, $type);
        }
    }

    private function processDirectory($directory, $type) {
        if (!file_exists($directory)) {
            echo "Directory not found: $directory\n";
            return;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $directory . $file;
            if (is_dir($filePath)) {
                $this->processDirectory($filePath . '/', $type);
                continue;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                continue;
            }

            $this->convertToWebP($filePath, $type);
        }
    }

    private function convertToWebP($source, $type) {
        $webpPath = pathinfo($source, PATHINFO_DIRNAME) . '/' . pathinfo($source, PATHINFO_FILENAME) . '.webp';

        // If webp already exists and looks good, skip
        if (file_exists($webpPath) && filesize($webpPath) > 0) {
            echo "WebP already exists for: $source\n";
            return;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $size = @filesize($source) ?: 0;

        // If very small and getimagesize can't parse it, skip early (likely corrupted/non-image)
        if ($size > 0 && $size < 2048) {
            $gs = @getimagesize($source);
            if ($gs === false) {
                echo "Skipping small/invalid image (probably corrupted): $source ($size bytes)\n";
                return;
            }
        }
        $image = null;

        // Try to load with GD but don't abort if it fails — we'll try Imagick next.
        try {
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = @imagecreatefromjpeg($source);
                    break;
                case 'png':
                    $image = @imagecreatefrompng($source);
                    if ($image !== false) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                    break;
                default:
                    echo "Skipping unsupported file type: $source\n";
                    return;
            }
        } catch (Exception $e) {
            // GD raised an unexpected exception — log it and continue to fallback
            echo "GD threw an error while reading $source: " . $e->getMessage() . "\n";
        }

        if ($image === false || $image === null) {
            // GD couldn't read this file — log helpful info, but DO NOT return here.
            $size = @filesize($source) ?: 0;
            echo "GD failed to create image for $source (size: {$size} bytes) — will try Imagick fallback if available\n";
        }

        // Attempt conversion using GD first if we have an image resource
        $success = false;
        // In PHP8+ GD returns a GdImage object, in older versions it returns a resource
        $hasGdImage = ($image !== null) && (is_resource($image) || (class_exists('GdImage') && $image instanceof GdImage));
        if ($hasGdImage) {
            $success = @imagewebp($image, $webpPath, 80);
            @imagedestroy($image);
            if ($success && file_exists($webpPath) && filesize($webpPath) > 0) {
                // done — GD produced the webp
            } else {
                // cleanup partial webp
                @unlink($webpPath);
                $success = false;
            }
        }

        // If GD failed to convert, try Imagick (better handling for many PNG/JPEG variants)
        if (!$success && class_exists('Imagick')) {
            try {
                $im = new \Imagick($source);
                // For animated images, write the first frame only
                if ($im->getNumberImages() > 1) {
                    $coalesced = $im->coalesceImages();
                    $frame = $coalesced->getImage();
                    $frame->setImageFormat('webp');
                    $frame->setImageCompressionQuality(80);
                    $frame->writeImage($webpPath);
                    $frame->clear();
                    $frame->destroy();
                    $coalesced->clear();
                    $coalesced->destroy();
                } else {
                    $im->setImageFormat('webp');
                    $im->setImageCompressionQuality(80);
                    $im->writeImage($webpPath);
                }
                $im->clear();
                $im->destroy();

                if (file_exists($webpPath) && filesize($webpPath) > 0) $success = true;
            } catch (Exception $e) {
                $success = false;
                echo "Imagick conversion failed for $source: " . $e->getMessage() . "\n";
                @unlink($webpPath);
            }
        }

        // CLI fallback: cwebp
        if (!$success && function_exists('exec')) {
            $cwebpPath = trim(@shell_exec('command -v cwebp 2>/dev/null')) ?: null;
            if ($cwebpPath) {
                $cmd = escapeshellcmd($cwebpPath) . ' -quiet -q 80 ' . escapeshellarg($source) . ' -o ' . escapeshellarg($webpPath);
                exec($cmd . ' 2>&1', $out, $rc);
                if ($rc === 0 && file_exists($webpPath) && filesize($webpPath) > 0) {
                    echo "Converted with cwebp: $source -> $webpPath\n";
                    $success = true;
                } else {
                    echo "cwebp failed (rc=$rc) for $source\n";
                    @unlink($webpPath);
                }
            }
        }

        // CLI fallback: vips
        if (!$success && function_exists('exec')) {
            $vipsPath = trim(@shell_exec('command -v vips 2>/dev/null')) ?: null;
            if ($vipsPath) {
                // vips copy source out.webp[Q=80]
                $cmd = escapeshellcmd($vipsPath) . ' copy ' . escapeshellarg($source) . ' ' . escapeshellarg($webpPath) . '[Q=80]';
                exec($cmd . ' 2>&1', $out, $rc);
                if ($rc === 0 && file_exists($webpPath) && filesize($webpPath) > 0) {
                    echo "Converted with vips: $source -> $webpPath\n";
                    $success = true;
                } else {
                    echo "vips failed (rc=$rc) for $source\n";
                    @unlink($webpPath);
                }
            }
        }

        if (!$success) {
            echo "Failed to convert (GD+Imagick+CLI) : $source\n";
            return;
        }

        // Update DB references in a way that matches how filenames are stored in this app
        // Build robust candidate variants so we match how filenames are stored in the DB
        // e.g. basename ('foo.jpg'), 'uploads/members/foo.jpg', '/uploads/members/foo.jpg', 'members/foo.jpg', etc.
        $full = str_replace('\\', '/', $source);
        $basename = basename($full);

        // Try to find the '/uploads/' portion and derive relative paths from it
        $uploadsIdx = stripos($full, '/uploads/');
        $relFromUploads = false;
        if ($uploadsIdx !== false) {
            $relFromUploads = substr($full, $uploadsIdx); // leading slash, e.g. /uploads/members/foo.jpg
        }

        // new path candidates (we will still store basename in DB as most tables do)
        $newPath = str_replace('.' . $extension, '.webp', $basename); // store only basename by default

        // Compose a variety of candidates that reflect how values are stored across the app
        $variants = [];
        $variants[] = $basename;
        if ($relFromUploads !== false) {
            $variants[] = ltrim($relFromUploads, '/'); // uploads/members/foo.jpg
            $variants[] = $relFromUploads; // /uploads/members/foo.jpg
        }
        // Include type-relative variants (members/foo.jpg) in case some tables store without the 'uploads/' prefix
        $variants[] = $type . '/' . $basename;
        $variants[] = '/' . $type . '/' . $basename;
        // Some code may store paths like '../uploads/members/foo.jpg' or 'uploads/members/foo.jpg' with a relative prefix
        if ($relFromUploads !== false) {
            $variants[] = '..' . $relFromUploads; // ../uploads/members/foo.jpg
        }

        // Ensure the list is unique, non-empty and trimmed
        $variants = array_values(array_unique(array_filter(array_map(function ($v) { return trim($v); }, $variants))));

        $this->updateDatabaseReferences($variants, $newPath, $type, $basename);

        // Remove original file only if webp is smaller
        $origSize = @filesize($source) ?: 0;
        $webpSize = @filesize($webpPath) ?: 0;
        if ($webpSize > 0 && $webpSize < $origSize) {
            @unlink($source);
            echo "Converted: $source -> $webpPath\n";
        } else {
            // webp not smaller: remove webp and keep original
            @unlink($webpPath);
            echo "Skipped conversion (webp not smaller): $source\n";
        }
    }

    /**
     * Update database rows which reference the converted file.
     *
     * @param array $variants candidate values to look for in DB columns (could be basename, uploads/..., /uploads/..., members/...)
     * @param string $newPath new value to store (basename.webp is expected)
     * @param string $type one of members|executives|activities|projects
     * @param string|null $origBasename original filename basename (optional, for clearer logging)
     */
    private function updateDatabaseReferences(array $variants, $newPath, $type, $origBasename = null) {
        // Normalize and dedupe variants
        $variants = array_values(array_unique(array_filter(array_map(function ($v) { return trim($v); }, $variants))));
        $newBasename = basename($newPath);

        try {
            // Members table (image and id_card_image)
            if ($type === 'members') {
                $cols = ['image', 'id_card_image'];
                $totalUpdated = 0;
                foreach ($cols as $col) {
                    $placeholders = [];
                    $params = [':new' => $newBasename];
                    foreach ($variants as $i => $v) {
                        $k = ':v' . ($i + 1);
                        $placeholders[] = $k;
                        $params[$k] = $v;
                    }
                    $sql = "UPDATE members SET $col = :new WHERE $col IN (" . implode(',', $placeholders) . ")";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                    $count = $stmt->rowCount();
                    if ($count > 0) echo "Updated $count record(s) in members.$col for $newBasename\n";
                    $totalUpdated += $count;
                }
                if ($totalUpdated === 0) {
                    $display = $origBasename ?? implode('|', $variants);
                    echo "No records found to update for {$display}\n";
                }
                return;
            }

            // Executives table
            if ($type === 'executives') {
                $placeholders = [];
                $params = [':new' => $newBasename];
                foreach ($variants as $i => $v) {
                    $k = ':v' . ($i + 1);
                    $placeholders[] = $k;
                    $params[$k] = $v;
                }

                $sql = "UPDATE executives SET profile_pic = :new WHERE profile_pic IN (" . implode(',', $placeholders) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $countExec = $stmt->rowCount();
                if ($countExec > 0) echo "Updated $countExec record(s) in executives.profile_pic for $newBasename\n";

                // Legacy case: executives stored in members table as image with role = 'executive'
                $sql = "UPDATE members SET image = :new WHERE image IN (" . implode(',', $placeholders) . ") AND role = 'executive'";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $countMem = $stmt->rowCount();
                if ($countMem > 0) echo "Updated $countMem record(s) in members.image (role=executive) for $newBasename\n";

                if (($countExec + $countMem) === 0) {
                    $display = $origBasename ?? implode('|', $variants);
                    echo "No records found to update for {$display}\n";
                }
                return;
            }

            // Activities / Projects
            if (in_array($type, ['activities', 'projects'])) {
                $placeholders = [];
                $params = [':new' => $newBasename];
                foreach ($variants as $i => $v) {
                    $k = ':v' . ($i + 1);
                    $placeholders[] = $k;
                    $params[$k] = $v;
                }
                $sql = "UPDATE $type SET image = :new WHERE image IN (" . implode(',', $placeholders) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $count = $stmt->rowCount();
                if ($count > 0) echo "Updated $count record(s) in $type.image for $newBasename\n";
                else {
                    $display = $origBasename ?? implode('|', $variants);
                    echo "No records found to update for {$display}\n";
                }
                return;
            }
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    }
    
}

// Run the conversion
try {
    // $pdo is already created in db.php
    $converter = new ImageConverter($pdo);
    $converter->convertAll();
    
    echo "Conversion completed successfully!\n";
} catch (PDOException $e) {
    die("Error during conversion: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}