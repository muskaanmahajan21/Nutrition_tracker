<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['calories'], $data['protein'], $data['carbs'], $data['fat'], $data['fiber'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Save nutrition goals
    $stmt = $pdo->prepare("
        INSERT INTO nutrition_goals (user_id, calories, protein, carbs, fat, fiber)
        VALUES (:user_id, :calories, :protein, :carbs, :fat, :fiber)
        ON DUPLICATE KEY UPDATE
            calories = VALUES(calories),
            protein = VALUES(protein),
            carbs = VALUES(carbs),
            fat = VALUES(fat),
            fiber = VALUES(fiber)
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':calories' => $data['calories'],
        ':protein' => $data['protein'],
        ':carbs' => $data['carbs'],
        ':fat' => $data['fat'],
        ':fiber' => $data['fiber']
    ]);

    // Delete existing vitamin goals
    $stmt = $pdo->prepare("DELETE FROM vitamin_goals WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);

    // Save vitamin goals if they exist
    if (isset($data['vitamins']) && is_array($data['vitamins'])) {
        $stmt = $pdo->prepare("
            INSERT INTO vitamin_goals (user_id, vitamin_name, goal_amount, unit)
            VALUES (:user_id, :vitamin_name, :goal_amount, :unit)
        ");

        foreach ($data['vitamins'] as $vitamin) {
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':vitamin_name' => $vitamin['name'],
                ':goal_amount' => $vitamin['amount'],
                ':unit' => $vitamin['unit']
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Goals saved successfully']);

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 