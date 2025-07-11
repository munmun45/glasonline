<?php
session_start();

// Simple authentication - In a real application, use proper authentication
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    !($_SERVER['PHP_AUTH_USER'] === 'admin' && 
      $_SERVER['PHP_AUTH_PW'] === 'admin123')) {
    
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

// User is authenticated
$_SESSION['admin_logged_in'] = true;

// Redirect to products management
header('Location: products.php');
exit;
?>
