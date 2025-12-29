<?php
require_once __DIR__ . '/../../includes/functions.php';

if (!function_exists('generateExecutiveSlug')) {
    function generateExecutiveSlug($name) {
        global $pdo;
        
        $slug = slugifyText($name);
        if (empty($slug)) {
            $slug = 'executive-' . uniqid();
        }
    
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM executives WHERE slug = ?");
        $stmt->execute([$slug]);
        $count = $stmt->fetchColumn();
    
        if ($count > 0) {
            $i = 1;
            do {
                $newSlug = $slug . "-" . $i;
                $stmt->execute([$newSlug]);
                $count = $stmt->fetchColumn();
                $i++;
            } while ($count > 0);
            return $newSlug;
        }
    
        return $slug;
    }
}

if (!function_exists('getExecutiveTypes')) {
    function getExecutiveTypes() {
        return [
            'teacher' => 'Teacher',
            'student' => 'Student'
        ];
    }
}

if (!function_exists('formatSocialLinks')) {
    function formatSocialLinks($links) {
        if (empty($links)) return [];
        return is_array($links) ? $links : json_decode($links, true);
    }
}
// Add this function at the top of your file or in a shared functions file
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}