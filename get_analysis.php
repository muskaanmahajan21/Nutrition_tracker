<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    // Get nutrition goals
    $stmt = $pdo->prepare("SELECT * FROM nutrition_goals WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $nutritionGoals = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get vitamin goals
    $stmt = $pdo->prepare("SELECT * FROM vitamin_goals WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $vitaminGoals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get today's consumption with correct column names
    $stmt = $pdo->prepare("
        SELECT 
            SUM(calories) as total_calories,
            SUM(protein) as total_protein,
            SUM(carbs) as total_carbs,
            SUM(fat) as total_fat,
            SUM(fiber) as total_fiber,
            SUM(vitamin_a_ug) as total_vitamin_a_ug,
            SUM(vitamin_e_mg) as total_vitamin_e_mg,
            SUM(vitamin_d2_ug) as total_vitamin_d2_ug,
            SUM(vitamin_d3_ug) as total_vitamin_d3_ug,
            SUM(vitamin_k1_ug) as total_vitamin_k1_ug,
            SUM(vitamin_k2_ug) as total_vitamin_k2_ug,
            SUM(vitamin_b1_mg) as total_vitamin_b1_mg,
            SUM(vitamin_b2_mg) as total_vitamin_b2_mg,
            SUM(vitamin_b3_mg) as total_vitamin_b3_mg,
            SUM(vitamin_b5_mg) as total_vitamin_b5_mg,
            SUM(vitamin_b6_mg) as total_vitamin_b6_mg,
            SUM(vitamin_b7_ug) as total_vitamin_b7_ug,
            SUM(vitamin_b9_ug) as total_vitamin_b9_ug,
            SUM(vitamin_c_mg) as total_vitamin_c_mg
        FROM food_consumption 
        WHERE user_id = :user_id 
        AND DATE(consumption_date) = CURDATE()
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $consumption = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare nutrition analysis
    $analysis = [
        'calories' => [
            'consumed' => $consumption['total_calories'] ?? 0,
            'goal' => $nutritionGoals['calories'] ?? 0,
            'isDeficit' => ($consumption['total_calories'] ?? 0) < ($nutritionGoals['calories'] ?? 0)
        ],
        'protein' => [
            'consumed' => $consumption['total_protein'] ?? 0,
            'goal' => $nutritionGoals['protein'] ?? 0,
            'isDeficit' => ($consumption['total_protein'] ?? 0) < ($nutritionGoals['protein'] ?? 0)
        ],
        'carbs' => [
            'consumed' => $consumption['total_carbs'] ?? 0,
            'goal' => $nutritionGoals['carbs'] ?? 0,
            'isDeficit' => ($consumption['total_carbs'] ?? 0) < ($nutritionGoals['carbs'] ?? 0)
        ],
        'fat' => [
            'consumed' => $consumption['total_fat'] ?? 0,
            'goal' => $nutritionGoals['fat'] ?? 0,
            'isDeficit' => ($consumption['total_fat'] ?? 0) < ($nutritionGoals['fat'] ?? 0)
        ],
        'fiber' => [
            'consumed' => $consumption['total_fiber'] ?? 0,
            'goal' => $nutritionGoals['fiber'] ?? 0,
            'isDeficit' => ($consumption['total_fiber'] ?? 0) < ($nutritionGoals['fiber'] ?? 0)
        ]
    ];

    // Prepare vitamin analysis
    $vitaminAnalysis = [];
    foreach ($vitaminGoals as $goal) {
        $vitaminName = $goal['vitamin_name'];
        $consumed = 0;
        switch ($vitaminName) {
            case 'vitamin_a':
                $consumed = $consumption['total_vitamin_a_ug'] ?? 0;
                break;
            case 'vitamin_e':
                $consumed = $consumption['total_vitamin_e_mg'] ?? 0;
                break;
            case 'vitamin_d':
                // D = D2 + D3
                $consumed = ($consumption['total_vitamin_d2_ug'] ?? 0) + ($consumption['total_vitamin_d3_ug'] ?? 0);
                break;
            case 'vitamin_k':
                // K = K1 + K2
                $consumed = ($consumption['total_vitamin_k1_ug'] ?? 0) + ($consumption['total_vitamin_k2_ug'] ?? 0);
                break;
            case 'vitamin_b1':
                $consumed = $consumption['total_vitamin_b1_mg'] ?? 0;
                break;
            case 'vitamin_b2':
                $consumed = $consumption['total_vitamin_b2_mg'] ?? 0;
                break;
            case 'vitamin_b3':
                $consumed = $consumption['total_vitamin_b3_mg'] ?? 0;
                break;
            case 'vitamin_b5':
                $consumed = $consumption['total_vitamin_b5_mg'] ?? 0;
                break;
            case 'vitamin_b6':
                $consumed = $consumption['total_vitamin_b6_mg'] ?? 0;
                break;
            case 'vitamin_b7':
                $consumed = $consumption['total_vitamin_b7_ug'] ?? 0;
                break;
            case 'vitamin_b9':
                $consumed = $consumption['total_vitamin_b9_ug'] ?? 0;
                break;
            case 'vitamin_c':
                $consumed = $consumption['total_vitamin_c_mg'] ?? 0;
                break;
        }
        $vitaminAnalysis[$vitaminName] = [
            'consumed' => $consumed,
            'goal' => $goal['goal_amount'],
            'unit' => $goal['unit'],
            'isDeficit' => $consumed < $goal['goal_amount']
        ];
    }

    // Generate suggestions based on deficits
    $suggestions = [];
    
    if ($analysis['protein']['isDeficit']) {
        $suggestions[] = [
            'name' => 'Chicken Breast',
            'details' => 'High in protein (31g per 100g) and low in fat. Great for muscle building and recovery.'
        ];
    }
    
    if ($analysis['carbs']['isDeficit']) {
        $suggestions[] = [
            'name' => 'Brown Rice',
            'details' => 'Complex carbohydrates (23g per 100g) with fiber. Provides sustained energy.'
        ];
    }
    
    if ($analysis['fiber']['isDeficit']) {
        $suggestions[] = [
            'name' => 'Lentils',
            'details' => 'High in fiber (8g per 100g) and protein. Excellent for digestion and heart health.'
        ];
    }

    // Add vitamin-specific suggestions
    foreach ($vitaminAnalysis as $vitamin => $data) {
        if ($data['isDeficit']) {
            switch ($vitamin) {
                case 'vitamin_a':
                    $suggestions[] = [
                        'name' => 'Carrots',
                        'details' => 'Rich in Vitamin A (835µg per 100g). Great for vision and immune system.'
                    ];
                    break;
                case 'vitamin_c':
                    $suggestions[] = [
                        'name' => 'Oranges',
                        'details' => 'High in Vitamin C (53.2mg per 100g). Excellent for immune support.'
                    ];
                    break;
                case 'vitamin_d':
                    $suggestions[] = [
                        'name' => 'Salmon',
                        'details' => 'Good source of Vitamin D (526IU per 100g). Important for bone health.'
                    ];
                    break;
                // Add more vitamin-specific suggestions as needed
            }
        }
    }

    echo json_encode([
        'success' => true,
        'analysis' => $analysis,
        'vitamin_analysis' => $vitaminAnalysis,
        'suggestions' => $suggestions
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 