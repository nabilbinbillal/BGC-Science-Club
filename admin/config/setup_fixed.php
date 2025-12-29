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
        description TEXT NOT NULL,
        image VARCHAR(255),
        link VARCHAR(255),
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Update members table to add executive_type and about fields if they don't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'executive_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE members ADD COLUMN executive_type ENUM('teacher', 'student') DEFAULT NULL");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'about'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE members ADD COLUMN about TEXT");
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
