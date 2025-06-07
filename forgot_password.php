<?php
session_start();
require 'config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'next_step' => ''];

// Load security questions
$questions = include 'security_questions.php';

// Step 1: Verify username/email
if (!isset($_SESSION['reset_step']) && isset($_POST['identifier'])) {
    $identifier = $_POST['identifier'] ?? '';
    
    if (empty($identifier)) {
        $response['message'] = 'Please enter your username or email';
        echo json_encode($response); exit;
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = :id OR email = :id");
        $stmt->execute([':id' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_step'] = 'verify_questions';
            $_SESSION['reset_attempts'] = 0;
            
            // Fetch user's security questions
            $stmt = $pdo->prepare("SELECT 
                security_question1, security_question2, security_question3
                FROM users WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            $security = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Store questions for verification
            $_SESSION['security_question1'] = $security['security_question1'];
            $_SESSION['security_question2'] = $security['security_question2'];
            
            // Send questions to frontend
            $response['success'] = true;
            $response['next_step'] = 'verify_questions';
            $response['questions'] = [
                '1' => $questions[$security['security_question1']],
                '2' => $questions[$security['security_question2']]
            ];
        } else {
            $response['message'] = 'No account found with that username or email';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} 
// Step 2: Verify security questions
elseif (isset($_SESSION['reset_step']) && $_SESSION['reset_step'] === 'verify_questions' && isset($_POST['answer1'])) {
    if ($_SESSION['reset_attempts'] >= 3) {
        session_destroy();
        $response['message'] = 'Too many attempts. Please start over.';
        echo json_encode($response); exit;
    }
    
    $answer1 = $_POST['answer1'] ?? '';
    $answer2 = $_POST['answer2'] ?? '';
    
    if (empty($answer1) || empty($answer2)) {
        $response['message'] = 'Please answer both questions';
        echo json_encode($response); exit;
    }
    
    try {
        // Get user's security questions and answers
        $stmt = $pdo->prepare("SELECT 
            security_question1, security_answer1_hash, security_answer1_salt,
            security_question2, security_answer2_hash, security_answer2_salt
            FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['reset_user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify answers
        $correct = 0;
        
        // Check first question
        if (hash('sha256', $answer1 . $user['security_answer1_salt']) === $user['security_answer1_hash']) {
            $correct++;
        }
        
        // Check second question
        if (hash('sha256', $answer2 . $user['security_answer2_salt']) === $user['security_answer2_hash']) {
            $correct++;
        }
        
        if ($correct === 2) {
            $_SESSION['reset_step'] = 'reset_password';
            $_SESSION['reset_verified'] = true;
            $response['success'] = true;
            $response['next_step'] = 'reset_password';
        } else {
            $_SESSION['reset_attempts']++;
            $attempts_left = 3 - $_SESSION['reset_attempts'];
            $response['message'] = 'Incorrect answers. ' . $attempts_left . ' attempts remaining.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}
// Step 3: Reset password
elseif (isset($_SESSION['reset_step']) && $_SESSION['reset_step'] === 'reset_password' && isset($_SESSION['reset_verified']) && isset($_POST['password'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm)) {
        $response['message'] = 'Please enter and confirm your new password';
        echo json_encode($response); exit;
    }
    
    if ($password !== $confirm) {
        $response['message'] = 'Passwords do not match';
        echo json_encode($response); exit;
    }
    
    // Password validation (same as signup)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $response['message'] = 'Password must be at least 8 characters with at least one uppercase, one lowercase, one number and one special character';
        echo json_encode($response); exit;
    }
    
    try {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute([
            ':password' => $hashed_password,
            ':id' => $_SESSION['reset_user_id']
        ]);
        
        // Clear reset session
        session_destroy();
        
        $response['success'] = true;
        $response['message'] = 'Password updated successfully';
        $response['redirect'] = 'login.html';
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}
else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
exit;
?>