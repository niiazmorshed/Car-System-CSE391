<?php
// config.php - Database configuration
$mongoUri = "mongodb+srv://carSystem:qIXG8p0wm1mPVPYl@cluster0.pyoefad.mongodb.net/carSystem?retryWrites=true&w=majority";
$databaseName = "carSystem";

// Include MongoDB PHP Driver
try {
    require_once __DIR__ . '/vendor/autoload.php';
} catch (Exception $e) {
    die('MongoDB PHP Driver not found. Please install using: composer require mongodb/mongodb');
}

// Set timezone
date_default_timezone_set('UTC');

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Function to handle MongoDB connection errors
function handleConnectionError($error) {
    return json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $error->getMessage()
    ]);
}

// Function to sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Function to validate ObjectId
function isValidObjectId($id) {
    return preg_match('/^[a-f\d]{24}$/i', $id);
}

// CORS headers function
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json');
}
?>