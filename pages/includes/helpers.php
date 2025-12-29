<?php
/**
 * Get the list of department options for members filter.
 * Returns an array of distinct department names from the members table,
 * ordered alphabetically.
 *
 * @return array
 */
function getDepartmentOptions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT DISTINCT department FROM members WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $departments ?: [];
    } catch (PDOException $e) {
        // Log error or handle as needed
        return [];
    }
}

/**
 * Get the list of department options for executives filter.
 * Returns an array of distinct department names from the executives table,
 * ordered alphabetically.
 *
 * @return array
 */
function getExecutiveDepartmentOptions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT DISTINCT department FROM executives WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $departments ?: [];
    } catch (PDOException $e) {
        // Log error or handle as needed
        return [];
    }
}
?>
