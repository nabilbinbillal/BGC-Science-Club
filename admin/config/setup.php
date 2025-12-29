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
    try {
        $pdo->exec("ALTER TABLE activities ADD COLUMN IF NOT EXISTS link VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        // Ignore if column already exists or error occurs
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
    try {
        $pdo->exec("ALTER TABLE members 
            ADD COLUMN IF NOT EXISTS executive_type ENUM('teacher', 'student') DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS about TEXT");
    } catch (PDOException $e) {
        // Ignore if columns already exist
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
        echo "Default superadmin created successfully.<br>";
    }
    
    echo "Database setup completed successfully.";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}