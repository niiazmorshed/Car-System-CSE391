<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Return empty appointments for now
echo json_encode([
    'success' => true,
    'appointments' => [],
    'count' => 0,
    'message' => 'No appointments yet (working!)'
]);
?>
