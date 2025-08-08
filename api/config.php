<?php
// Vercel PHP serverless function config
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// MongoDB connection
$mongoUri = $_ENV['MONGO_URI'] ?? "mongodb+srv://carSystem:qIXG8p0wm1mPVPYl@cluster0.pyoefad.mongodb.net/carSystem?retryWrites=true&w=majority";
$databaseName = "carSystem";

// Vercel automatically includes MongoDB extension for PHP
// No need for vendor/autoload.php with vercel-php runtime

date_default_timezone_set('UTC');
ini_set('display_errors', 0);
error_reporting(E_ALL);

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isValidObjectId($id) {
    return preg_match('/^[a-f\d]{24}$/i', $id);
}
?>
