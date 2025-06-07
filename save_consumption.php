<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Get raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging
error_log('Received data: ' . print_r($data, true));

// Validate required fields
if (!isset($data['food_code'], $data['food_name'], $data['servings'], $data['serving_unit'], $data['nutrition'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    // Calculate nutrition values based on servings
    $servings = (float)$data['servings'];
    $nutrition = $data['nutrition'];
    
    $stmt = $pdo->prepare("
        INSERT INTO food_consumption (
            user_id, 
            food_code, 
            food_name, 
            servings, 
            serving_unit,
            calories,
            protein, 
            carbs, 
            fat, 
            fiber,
            vitamin_a_ug,
            vitamin_e_mg,
            vitamin_d2_ug,
            vitamin_d3_ug,
            vitamin_k1_ug,
            vitamin_k2_ug,
            vitamin_b1_mg,
            vitamin_b2_mg,
            vitamin_b3_mg,
            vitamin_b5_mg,
            vitamin_b6_mg,
            vitamin_b7_ug,
            vitamin_b9_ug,
            vitamin_c_mg,
            consumption_date
        ) VALUES (
            :user_id, 
            :food_code, 
            :food_name, 
            :servings, 
            :serving_unit,
            :calories,
            :protein, 
            :carbs, 
            :fat, 
            :fiber,
            :vitamin_a_ug,
            :vitamin_e_mg,
            :vitamin_d2_ug,
            :vitamin_d3_ug,
            :vitamin_k1_ug,
            :vitamin_k2_ug,
            :vitamin_b1_mg,
            :vitamin_b2_mg,
            :vitamin_b3_mg,
            :vitamin_b5_mg,
            :vitamin_b6_mg,
            :vitamin_b7_ug,
            :vitamin_b9_ug,
            :vitamin_c_mg,
            CURDATE()
        )
    ");
    
    // If energy_kcal doesn't exist, try unit_serving_energy_kcal
    $calories = isset($nutrition['energy_kcal']) ? 
                $nutrition['energy_kcal'] * $servings : 
                (isset($nutrition['unit_serving_energy_kcal']) ? 
                 $nutrition['unit_serving_energy_kcal'] * $servings : 0);
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':food_code' => $data['food_code'],
        ':food_name' => $data['food_name'],
        ':servings' => $servings,
        ':serving_unit' => $data['serving_unit'],
        ':calories' => $calories,
        ':protein' => ($nutrition['protein_g'] ?? 0) * $servings,
        ':carbs' => ($nutrition['carb_g'] ?? 0) * $servings,
        ':fat' => ($nutrition['fat_g'] ?? 0) * $servings,
        ':fiber' => ($nutrition['fibre_g'] ?? 0) * $servings,
        ':vitamin_a_ug' => ($nutrition['unit_serving_vita_ug'] ?? 0) * $servings,
        ':vitamin_e_mg' => ($nutrition['unit_serving_vite_mg'] ?? 0) * $servings,
        ':vitamin_d2_ug' => ($nutrition['unit_serving_vitd2_ug'] ?? 0) * $servings,
        ':vitamin_d3_ug' => ($nutrition['unit_serving_vitd3_ug'] ?? 0) * $servings,
        ':vitamin_k1_ug' => ($nutrition['unit_serving_vitk1_ug'] ?? 0) * $servings,
        ':vitamin_k2_ug' => ($nutrition['unit_serving_vitk2_ug'] ?? 0) * $servings,
        ':vitamin_b1_mg' => ($nutrition['unit_serving_vitb1_mg'] ?? 0) * $servings,
        ':vitamin_b2_mg' => ($nutrition['unit_serving_vitb2_mg'] ?? 0) * $servings,
        ':vitamin_b3_mg' => ($nutrition['unit_serving_vitb3_mg'] ?? 0) * $servings,
        ':vitamin_b5_mg' => ($nutrition['unit_serving_vitb5_mg'] ?? 0) * $servings,
        ':vitamin_b6_mg' => ($nutrition['unit_serving_vitb6_mg'] ?? 0) * $servings,
        ':vitamin_b7_ug' => ($nutrition['unit_serving_vitb7_ug'] ?? 0) * $servings,
        ':vitamin_b9_ug' => ($nutrition['unit_serving_vitb9_ug'] ?? 0) * $servings,
        ':vitamin_c_mg' => ($nutrition['unit_serving_vitc_mg'] ?? 0) * $servings
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Food consumption saved successfully',
        'user_id' => $_SESSION['user_id'],
        'data_saved' => [
            'food_name' => $data['food_name'],
            'servings' => $servings,
            'calories' => $calories
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>