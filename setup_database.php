<?php
// Set headers
header('Content-Type: text/html');

// For debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require 'config.php';

// Output function
function output($message) {
    echo $message . "<br>\n";
    flush();
    ob_flush();
}

// Start HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Nutrition Tracker Database Setup</h1>";

try {
    // Create users table
    output("Creating users table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `email` VARCHAR(100) NOT NULL,
          `name` VARCHAR(100) NOT NULL,
          `phone` VARCHAR(20) NULL,
          `age` INT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE INDEX `username_UNIQUE` (`username`),
          UNIQUE INDEX `email_UNIQUE` (`email`)
        ) ENGINE=InnoDB;
    ");
    output("<span class='success'>Users table created or already exists.</span>");

    // Create food_consumption table
    output("Creating food_consumption table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `food_consumption` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `user_id` INT NOT NULL,
          `food_code` VARCHAR(20) NOT NULL,
          `food_name` VARCHAR(100) NOT NULL,
          `servings` FLOAT NOT NULL,
          `serving_unit` VARCHAR(50) NOT NULL,
          `calories` FLOAT NOT NULL,
          `protein` FLOAT NOT NULL,
          `carbs` FLOAT NOT NULL,
          `fat` FLOAT NOT NULL,
          `fiber` FLOAT DEFAULT 0,
          `consumption_date` DATE NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          CONSTRAINT `fk_user`
            FOREIGN KEY (`user_id`)
            REFERENCES `users` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ) ENGINE=InnoDB;
    ");
    output("<span class='success'>Food consumption table created or already exists.</span>");

    // Create nutrition_goals table
    output("Creating nutrition_goals table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `nutrition_goals` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `user_id` INT NOT NULL,
          `calories` FLOAT NOT NULL,
          `protein` FLOAT NOT NULL,
          `carbs` FLOAT NOT NULL,
          `fat` FLOAT NOT NULL,
          `fiber` FLOAT NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`),
          CONSTRAINT `fk_nutrition_goals_user`
            FOREIGN KEY (`user_id`)
            REFERENCES `users` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ) ENGINE=InnoDB;
    ");
    output("<span class='success'>Nutrition goals table created or already exists.</span>");

    // Create vitamin_goals table
    output("Creating vitamin_goals table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vitamin_goals` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `user_id` INT NOT NULL,
          `vitamin_name` VARCHAR(20) NOT NULL,
          `goal_amount` FLOAT NOT NULL,
          `unit` VARCHAR(10) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_vitamin` (`user_id`, `vitamin_name`),
          CONSTRAINT `fk_vitamin_goals_user`
            FOREIGN KEY (`user_id`)
            REFERENCES `users` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ) ENGINE=InnoDB;
    ");
    output("<span class='success'>Vitamin goals table created or already exists.</span>");

    // Create test user
    output("Creating test user...");
    
    // Check if test user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'testuser' OR email = 'test@example.com'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        output("<span class='success'>Test user already exists, skipping...</span>");
    } else {
        // Create test user
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, age) 
                              VALUES ('testuser', :password, 'test@example.com', 'Test User', '1234567890', 30)");
        $stmt->execute([':password' => $password_hash]);
        
        output("<span class='success'>Test user created successfully.</span>");
        output("Username: testuser");
        output("Email: test@example.com");
        output("Password: password123");
    }
    
    output("<br><strong>Setup completed successfully!</strong>");
    output("<a href='login.html'>Go to login page</a>");

} catch (PDOException $e) {
    output("<span class='error'>Database error: " . $e->getMessage() . "</span>");
}

// End HTML output
echo "</body></html>";
?>