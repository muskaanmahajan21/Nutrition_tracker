<?php
// Set headers to allow for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// For development debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load the JSON data
$jsonData = file_get_contents('food_data.json');
if ($jsonData === false) {
    echo json_encode(['success' => false, 'message' => 'Could not load food data']);
    exit;
}

$foods = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
    exit;
}

// Check if search term is provided
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = strtolower(trim($_GET['search']));
    $results = [];
    
    // Search for matching food items
    foreach ($foods as $food) {
        if (isset($food['food_name']) && strpos(strtolower($food['food_name']), $searchTerm) !== false) {
            // Prepare nutrition data with proper null checks
            $nutritionData = [
                'energy_kcal' => isset($food['energy_kcal']) ? $food['energy_kcal'] : 
                               (isset($food['unit_serving_energy_kcal']) ? $food['unit_serving_energy_kcal'] : 0),
                'carb_g' => isset($food['carb_g']) ? $food['carb_g'] : 
                          (isset($food['unit_serving_carb_g']) ? $food['unit_serving_carb_g'] : 0),
                'protein_g' => isset($food['protein_g']) ? $food['protein_g'] : 
                            (isset($food['unit_serving_protein_g']) ? $food['unit_serving_protein_g'] : 0),
                'fat_g' => isset($food['fat_g']) ? $food['fat_g'] : 
                         (isset($food['unit_serving_fat_g']) ? $food['unit_serving_fat_g'] : 0),
                'fibre_g' => isset($food['fibre_g']) ? $food['fibre_g'] : 
                           (isset($food['unit_serving_fibre_g']) ? $food['unit_serving_fibre_g'] : 0),
                // Add vitamin fields with proper null checks
                'unit_serving_vita_ug' => $food['unit_serving_vita_ug'] ?? 0,
                'unit_serving_vite_mg' => $food['unit_serving_vite_mg'] ?? 0,
                'unit_serving_vitd2_ug' => $food['unit_serving_vitd2_ug'] ?? 0,
                'unit_serving_vitd3_ug' => $food['unit_serving_vitd3_ug'] ?? 0,
                'unit_serving_vitk1_ug' => $food['unit_serving_vitk1_ug'] ?? 0,
                'unit_serving_vitk2_ug' => $food['unit_serving_vitk2_ug'] ?? 0,
                'unit_serving_vitb1_mg' => $food['unit_serving_vitb1_mg'] ?? 0,
                'unit_serving_vitb2_mg' => $food['unit_serving_vitb2_mg'] ?? 0,
                'unit_serving_vitb3_mg' => $food['unit_serving_vitb3_mg'] ?? 0,
                'unit_serving_vitb5_mg' => $food['unit_serving_vitb5_mg'] ?? 0,
                'unit_serving_vitb6_mg' => $food['unit_serving_vitb6_mg'] ?? 0,
                'unit_serving_vitb7_ug' => $food['unit_serving_vitb7_ug'] ?? 0,
                'unit_serving_vitb9_ug' => $food['unit_serving_vitb9_ug'] ?? 0,
                'unit_serving_vitc_mg' => $food['unit_serving_vitc_mg'] ?? 0
            ];
            
            // Add debug logging
            error_log("Food data for {$food['food_name']}: " . json_encode($food));
            error_log("Processed nutrition data: " . json_encode($nutritionData));
            
            $results[] = [
                'food_code' => $food['food_code'] ?? '',
                'food_name' => $food['food_name'],
                'servings_unit' => $food['servings_unit'] ?? 'serving',
                'nutrition' => $nutritionData
            ];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $results]);
} else {
    // If no search term is provided
    echo json_encode(['success' => false, 'message' => 'Please provide a search term']);
}
?>