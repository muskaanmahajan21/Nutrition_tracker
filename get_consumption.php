<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// For development debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'User not logged in. Please log in first.',
        'redirect' => 'login.php'
    ]);
    exit;
}

// Load database configuration
require 'config.php';

// Get date parameter
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    // Get user's food consumption for the specified date
    $stmt = $pdo->prepare("
        SELECT 
            id, food_code, food_name, servings, serving_unit,
            calories, protein, carbs, fat, fiber,
            vitamin_a_ug, vitamin_e_mg, vitamin_d2_ug, vitamin_d3_ug,
            vitamin_k1_ug, vitamin_k2_ug, vitamin_b1_mg, vitamin_b2_mg,
            vitamin_b3_mg, vitamin_b5_mg, vitamin_b6_mg, vitamin_b7_ug,
            vitamin_b9_ug, vitamin_c_mg
        FROM 
            food_consumption
        WHERE 
            user_id = :user_id AND 
            consumption_date = :date
        ORDER BY 
            created_at
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':date' => $date
    ]);
    
    $foodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate nutrition summary
    $summary = [
        'calories' => 0,
        'protein' => 0,
        'carbs' => 0,
        'fat' => 0,
        'fiber' => 0,
        'vitamin_a_ug' => 0,
        'vitamin_e_mg' => 0,
        'vitamin_d2_ug' => 0,
        'vitamin_d3_ug' => 0,
        'vitamin_k1_ug' => 0,
        'vitamin_k2_ug' => 0,
        'vitamin_b1_mg' => 0,
        'vitamin_b2_mg' => 0,
        'vitamin_b3_mg' => 0,
        'vitamin_b5_mg' => 0,
        'vitamin_b6_mg' => 0,
        'vitamin_b7_ug' => 0,
        'vitamin_b9_ug' => 0,
        'vitamin_c_mg' => 0
    ];
    
    foreach ($foodItems as $item) {
        $summary['calories'] += $item['calories'] ?? 0;
        $summary['protein'] += $item['protein'] ?? 0;
        $summary['carbs'] += $item['carbs'] ?? 0;
        $summary['fat'] += $item['fat'] ?? 0;
        $summary['fiber'] += $item['fiber'] ?? 0;
        $summary['vitamin_a_ug'] += $item['vitamin_a_ug'] ?? 0;
        $summary['vitamin_e_mg'] += $item['vitamin_e_mg'] ?? 0;
        $summary['vitamin_d2_ug'] += $item['vitamin_d2_ug'] ?? 0;
        $summary['vitamin_d3_ug'] += $item['vitamin_d3_ug'] ?? 0;
        $summary['vitamin_k1_ug'] += $item['vitamin_k1_ug'] ?? 0;
        $summary['vitamin_k2_ug'] += $item['vitamin_k2_ug'] ?? 0;
        $summary['vitamin_b1_mg'] += $item['vitamin_b1_mg'] ?? 0;
        $summary['vitamin_b2_mg'] += $item['vitamin_b2_mg'] ?? 0;
        $summary['vitamin_b3_mg'] += $item['vitamin_b3_mg'] ?? 0;
        $summary['vitamin_b5_mg'] += $item['vitamin_b5_mg'] ?? 0;
        $summary['vitamin_b6_mg'] += $item['vitamin_b6_mg'] ?? 0;
        $summary['vitamin_b7_ug'] += $item['vitamin_b7_ug'] ?? 0;
        $summary['vitamin_b9_ug'] += $item['vitamin_b9_ug'] ?? 0;
        $summary['vitamin_c_mg'] += $item['vitamin_c_mg'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'date' => $date,
        'food_items' => $foodItems,
        'summary' => $summary
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>