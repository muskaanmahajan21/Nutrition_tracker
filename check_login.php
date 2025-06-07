<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If the request is AJAX, return JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Not logged in',
            'redirect' => 'login.html'
        ]);
        exit;
    }
    
    // Otherwise, redirect to login page
    header('Location: login.html');
    exit;
}
?>