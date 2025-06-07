<?php
session_start();
require 'config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'redirect' => ''];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';
    
    // Login form handling
    if ($form_type === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $response['message'] = 'Please enter both email and password';
        } else {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, password, name FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Password is correct, start a new session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    
                    $response['success'] = true;
                    $response['message'] = 'Login successful';
                    $response['redirect'] = 'goals.html';
                } else {
                    $response['message'] = 'Invalid email or password';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        }
    }
    // Signup form handling
    else if ($form_type === 'signup') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $age = $_POST['age'] ?? '';
        $birthdate = $_POST['birthdate'] ?? '';
        
        // Name validation
        if (empty($name) || !preg_match('/^[\p{L} ]{2,50}$/u', $name)) {
            $response['message'] = 'Name must be 2-50 letters and spaces only';
            echo json_encode($response); exit;
        }

        // Email validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format';
            echo json_encode($response); exit;
        }

        // Allowed email domains
        $allowedDomains = [
            'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com',
            'icloud.com', 'protonmail.com', 'edu'
        ];

        // Extract domain from email
        $emailParts = explode('@', $email);
        $domain = strtolower(end($emailParts));

        // Check if domain is allowed
        $isValidDomain = false;
        foreach ($allowedDomains as $allowed) {
            if (str_ends_with($domain, $allowed)) {
                $isValidDomain = true;
                break;
            }
        }

        if (!$isValidDomain) {
            $response['message'] = 'Please use a valid email provider (Gmail, Yahoo, Outlook, etc.) or your educational email';
            echo json_encode($response); exit;
        }

        // Password validation
        if (empty($password) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            $response['message'] = 'Password must be at least 8 characters with at least one uppercase, one lowercase, one number and one special character';
            echo json_encode($response); exit;
        }

        // Phone validation (if present)
        if (!empty($phone)) {
            // Check if phone already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone");
            $stmt->execute([':phone' => $phone]);
            if ($stmt->fetch()) {
                $response['message'] = 'This phone number is already registered';
                echo json_encode($response); exit;
            }
            
            if (!preg_match('/^[5-9][0-9]{9}$/', $phone)) {
                $response['message'] = 'Phone must start with 5,6,7,8,9 and be 10 digits';
                echo json_encode($response); exit;
            }
            if (preg_match('/^(\d)\1{9}$/', $phone)) {
                $response['message'] = 'Phone number cannot be all the same digit';
                echo json_encode($response); exit;
            }
            
            $sequentialPatterns = [
                '0123456789', '9876543210', '1234567890', '0987654321'
            ];
            if (in_array($phone, $sequentialPatterns)) {
                $response['message'] = 'Phone number cannot be sequential numbers';
                echo json_encode($response); exit;
            }
        }

        // Birthdate and age validation
        if (empty($birthdate)) {
            $response['message'] = 'Birthdate is required';
            echo json_encode($response); exit;
        }
        
        $birth = strtotime($birthdate);
        $today = strtotime(date('Y-m-d'));
        $calcAge = date('Y', $today) - date('Y', $birth);
        if (date('md', $today) < date('md', $birth)) {
            $calcAge--;
        }
        
        if ($calcAge < 13) {
            $response['message'] = 'You must be at least 13 years old to register';
            echo json_encode($response); exit;
        }
        if ($calcAge > 120) {
            $response['message'] = 'Age must be less than 120';
            echo json_encode($response); exit;
        }
        if (!empty($age) && intval($age) !== $calcAge) {
            $response['message'] = 'Age does not match birthdate';
            echo json_encode($response); exit;
        }
        
        // Security questions validation
        $questions = include 'security_questions.php';
        $question1 = $_POST['security_question1'] ?? '';
        $answer1 = $_POST['security_answer1'] ?? '';
        $question2 = $_POST['security_question2'] ?? '';
        $answer2 = $_POST['security_answer2'] ?? '';
        $question3 = $_POST['security_question3'] ?? '';
        $answer3 = $_POST['security_answer3'] ?? '';

        if (empty($question1) || !array_key_exists($question1, $questions) ||
            empty($question2) || !array_key_exists($question2, $questions) ||
            empty($question3) || !array_key_exists($question3, $questions)) {
            $response['message'] = 'Please select valid security questions';
            echo json_encode($response); exit;
        }

        if (empty($answer1) || empty($answer2) || empty($answer3)) {
            $response['message'] = 'Please answer all security questions';
            echo json_encode($response); exit;
        }

        // Generate salts and hash answers
        $salt1 = bin2hex(random_bytes(16));
        $salt2 = bin2hex(random_bytes(16));
        $salt3 = bin2hex(random_bytes(16));

        $hashed_answer1 = hash('sha256', $answer1 . $salt1);
        $hashed_answer2 = hash('sha256', $answer2 . $salt2);
        $hashed_answer3 = hash('sha256', $answer3 . $salt3);
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                $response['message'] = 'Email already exists. Please use another email or login.';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Extract username from email
                $username = explode('@', $email)[0];
                
                // Check for inappropriate usernames
                $inappropriateWords = ['admin', 'root', 'moderator', 'administrator'];
                $tempUsername = strtolower($username);
                foreach ($inappropriateWords as $word) {
                    if (strpos($tempUsername, $word) !== false) {
                        $response['message'] = 'Username contains restricted words';
                        echo json_encode($response); exit;
                    }
                }
                
                // Check if username exists, append numbers if needed
                $baseUsername = $username;
                $counter = 1;
                while (true) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                    $stmt->execute([':username' => $username]);
                    if (!$stmt->fetch()) break;
                    
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, age, 
                                      security_question1, security_answer1_hash, security_answer1_salt,
                                      security_question2, security_answer2_hash, security_answer2_salt,
                                      security_question3, security_answer3_hash, security_answer3_salt) 
                                      VALUES (:username, :password, :email, :name, :phone, :age,
                                      :question1, :answer1, :salt1,
                                      :question2, :answer2, :salt2,
                                      :question3, :answer3, :salt3)");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':email' => $email,
                    ':name' => $name,
                    ':phone' => $phone,
                    ':age' => $age,
                    ':question1' => $question1,
                    ':answer1' => $hashed_answer1,
                    ':salt1' => $salt1,
                    ':question2' => $question2,
                    ':answer2' => $hashed_answer2,
                    ':salt2' => $salt2,
                    ':question3' => $question3,
                    ':answer3' => $hashed_answer3,
                    ':salt3' => $salt3
                ]);
                
                // Log in the new user
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                
                $response['success'] = true;
                $response['message'] = 'Registration successful';
                $response['redirect'] = 'goals.html';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Output JSON response
echo json_encode($response);
exit;
?>