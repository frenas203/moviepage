<?php
// Configure error reporting
if (defined('SHOW_ERROR_DETAILS') && SHOW_ERROR_DETAILS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Include the configuration file
require_once 'config.php';

// Try to establish the database connection
try {
    $conn = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set to ensure proper encoding
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting character set: " . $conn->error);
    }
} catch (Exception $e) {
    // Log the error for administrators
    error_log("Database connection error: " . $e->getMessage(), 0);
    
    // Show a user-friendly message
    if (defined('SHOW_ERROR_DETAILS') && SHOW_ERROR_DETAILS) {
        die("Database connection error: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later or contact an administrator.");
    }
}
?>
