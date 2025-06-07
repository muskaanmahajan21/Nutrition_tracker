<?php
require 'config.php';

try {
    // Create users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create food_consumption table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS food_consumption (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            food_code VARCHAR(50),
            food_name VARCHAR(255) NOT NULL,
            servings FLOAT NOT NULL,
            serving_unit VARCHAR(50),
            calories FLOAT,
            protein FLOAT,
            carbs FLOAT,
            fat FLOAT,
            fiber FLOAT,
            consumption_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Create a test user if none exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        $username = 'testuser';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->execute([
            ':username' => $username,
            ':password' => $password
        ]);
        
        echo "Test user created: Username - testuser, Password - password123";
    } else {
        echo "Database setup complete. Tables created successfully.";
    }
    
} catch (PDOException $e) {
    die("Database setup error: " . $e->getMessage());
}
?>