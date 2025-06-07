<?php
session_start();
require 'config.php';

// This is just for testing - in production, always use the proper login form
// Direct login with hardcoded test user credentials
$username = 'testuser';
$password = 'password123';

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, start a new session
        $_SESSION['user_id'] = $user['id'];
        echo "Login successful! User ID: " . $_SESSION['user_id'];
        echo "<br><a href='index.html'>Go to Nutrition Tracker</a>";
    } else {
        echo "Invalid username or password. Make sure you've run setup.php first.";
        echo "<br><a href='setup.php'>Run Setup</a>";
    }
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
?>