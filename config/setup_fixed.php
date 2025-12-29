<?php
// Include database configuration
require_once 'db.php';

try {
    // Create tables if they don't exist
    
    // Admins table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Members table
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        class_level VARCHAR(100) DEFAULT NULL,
        group_name VARCHAR(100) DEFAULT NULL,
        role ENUM('member', 'executive') NOT NULL DEFAULT 'member',
        executive_type ENUM('teacher', 'student') DEFAULT NULL,
        about TEXT,
        joining_date DATE NOT NULL,
        roll_no VARCHAR(50),
        member_id VARCHAR(20) NOT NULL UNIQUE,
        status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        image VARCHAR(255) DEFAULT NULL,
        gender ENUM('male', 'female', 'other') DEFAULT 'male',
        position VARCHAR(100) DEFAULT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Activities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        image VARCHAR(255),
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Add link column to activities table if it does not exist
    $stmt = $pdo->query("SHOW COLUMNS FROM activities LIKE 'link'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN link VARCHAR(255) DEFAULT NULL");
    }
    
    // Events table
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Executives table
    $pdo->exec("CREATE TABLE IF NOT EXISTS executives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        session VARCHAR(50) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        bio TEXT,
        facebook VARCHAR(255),
        twitter VARCHAR(255),
        linkedin VARCHAR(255),
        profile_pic VARCHAR(255),
        slug VARCHAR(255) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Projects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        short_description VARCHAR(255) NULL,
        description TEXT NOT NULL,
        long_description LONGTEXT NULL,
        image VARCHAR(255),
        link VARCHAR(255),
        slug VARCHAR(255) UNIQUE,
        class_scope TEXT NULL,
        department_scope TEXT NULL,
        contributor_ids TEXT NULL,
        is_collaborative TINYINT(1) DEFAULT 0,
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Ensure new project columns exist for legacy tables
    $projectColumns = [
        'short_description' => "ALTER TABLE projects ADD COLUMN short_description VARCHAR(255) NULL AFTER title",
        'long_description' => "ALTER TABLE projects ADD COLUMN long_description LONGTEXT NULL AFTER description",
        'slug' => "ALTER TABLE projects ADD COLUMN slug VARCHAR(255) UNIQUE AFTER link",
        'class_scope' => "ALTER TABLE projects ADD COLUMN class_scope TEXT NULL AFTER slug",
        'department_scope' => "ALTER TABLE projects ADD COLUMN department_scope TEXT NULL AFTER class_scope",
        'contributor_ids' => "ALTER TABLE projects ADD COLUMN contributor_ids TEXT NULL AFTER department_scope",
        'is_collaborative' => "ALTER TABLE projects ADD COLUMN is_collaborative TINYINT(1) DEFAULT 0 AFTER contributor_ids"
    ];
    
    foreach ($projectColumns as $column => $query) {
        $stmt = $pdo->query("SHOW COLUMNS FROM projects LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($query);
        }
    }
    
    // Update members table to add missing columns if they don't exist
    $memberColumns = [
        'executive_type' => "ALTER TABLE members ADD COLUMN executive_type ENUM('teacher', 'student') DEFAULT NULL",
        'about' => "ALTER TABLE members ADD COLUMN about TEXT",
        'class_level' => "ALTER TABLE members ADD COLUMN class_level VARCHAR(100) DEFAULT NULL",
        'group_name' => "ALTER TABLE members ADD COLUMN group_name VARCHAR(100) DEFAULT NULL"
    ];
    
    foreach ($memberColumns as $column => $query) {
        $stmt = $pdo->query("SHOW COLUMNS FROM members LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($query);
        }
    }
    
    // Ensure settings table has extended fields
    $settingsColumns = [
        'class_options' => "ALTER TABLE settings ADD COLUMN class_options TEXT NULL AFTER linkedin_url",
        'department_options' => "ALTER TABLE settings ADD COLUMN department_options TEXT NULL AFTER class_options",
        'whatsapp_link' => "ALTER TABLE settings ADD COLUMN whatsapp_link VARCHAR(255) NULL AFTER department_options"
    ];
    
    foreach ($settingsColumns as $column => $query) {
        $stmt = $pdo->query("SHOW COLUMNS FROM settings LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($query);
        }
    }
    
    // Ensure there is at least one settings row with defaults
    $stmt = $pdo->query("SELECT COUNT(*) FROM settings");
    $settingsCount = $stmt->fetchColumn();
    if ($settingsCount == 0) {
        $defaultClasses = json_encode([
            ['name' => 'Intermediate 1st Year', 'groups' => ['Science', 'Commerce', 'Arts']],
            ['name' => 'Intermediate 2nd Year', 'groups' => ['Science', 'Commerce', 'Arts']]
        ]);
        $defaultDepartments = json_encode([
            'ICT',
            'Physics',
            'Chemistry',
            'Botany',
            'Zoology',
            'Mathematics'
        ]);
        
        $stmt = $pdo->prepare("INSERT INTO settings (
            recaptcha_enabled,
            site_name,
            site_description,
            contact_email,
            contact_phone,
            facebook_url,
            twitter_url,
            instagram_url,
            linkedin_url,
            class_options,
            department_options,
            whatsapp_link
        ) VALUES (1, 'BGC Science Club', NULL, NULL, NULL, NULL, NULL, NULL, NULL, ?, ?, NULL)");
        $stmt->execute([$defaultClasses, $defaultDepartments]);
    }
    
    // Insert sample executive data if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM executives");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO executives (name, position, department, session, email, phone, bio, facebook, twitter, linkedin, profile_pic, slug) VALUES
            ('John Doe', 'President', 'Physics', '2023-2024', 'john@example.com', '1234567890', 'Leader of the club', 'https://facebook.com/johndoe', 'https://twitter.com/johndoe', 'https://linkedin.com/in/johndoe', 'default-profile.png', 'john-doe'),
            ('Jane Smith', 'Vice President', 'Chemistry', '2023-2024', 'jane@example.com', '0987654321', 'Vice leader', 'https://facebook.com/janesmith', 'https://twitter.com/janesmith', 'https://linkedin.com/in/janesmith', 'default-profile.png', 'jane-smith')
        ");
    }
    
    // Check if the superadmin exists
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin'");
    $stmt->execute();
    
    // If not, create the default superadmin account
    if ($stmt->rowCount() == 0) {
        $password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, name, email, role) VALUES ('admin', :password, 'Super Admin', 'admin@bgcscienceclub.com', 'superadmin')");
        $stmt->bindParam(':password', $password);
        $stmt->execute();
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
