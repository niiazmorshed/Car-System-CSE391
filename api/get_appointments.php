<?php
// get_appointments.php - SLOT-BASED APPOINTMENT RETRIEVAL
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    require_once 'config.php';
} catch (Exception $configError) {
    echo json_encode([
        'success' => false,
        'message' => 'Configuration error: ' . $configError->getMessage()
    ]);
    exit;
}

function logAppointments($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] SLOT_APPOINTMENTS: $message";
    if ($data) {
        $log .= " | " . json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    error_log($log);
}

try {
    logAppointments("🎰 SLOT-BASED: Starting appointments fetch");
    
    // Connect to MongoDB
    $client = new MongoDB\Client($mongoUri, [
        'connectTimeoutMS' => 10000,
        'serverSelectionTimeoutMS' => 10000
    ]);
    
    $database = $client->selectDatabase($databaseName);
    $appointmentsCollection = $database->selectCollection('appointments');
    
    // Test connection
    $database->command(['ping' => 1]);
    logAppointments("✅ MongoDB connection successful");
    
    // Fetch all appointments sorted by creation date (newest first)
    $cursor = $appointmentsCollection->find([], [
        'sort' => ['createdAt' => -1]
    ]);
    
    $appointments = [];
    
    foreach ($cursor as $appointment) {
        $appointmentId = (string)$appointment->_id;
        
        // Build appointment data
        $appointmentData = [
            '_id' => $appointmentId,
            'clientName' => $appointment->clientName ?? 'Unknown',
            'clientPhone' => $appointment->clientPhone ?? '',
            'clientAddress' => $appointment->clientAddress ?? '',
            'carLicense' => $appointment->carLicense ?? '',
            'carEngine' => $appointment->carEngine ?? '',
            'mechanicId' => $appointment->mechanicId ?? '',
            'status' => $appointment->status ?? 'pending',
            'notes' => $appointment->notes ?? ''
        ];
        
        // Handle appointment date
        if (isset($appointment->appointmentDate) && is_object($appointment->appointmentDate) && method_exists($appointment->appointmentDate, 'toDateTime')) {
            $appointmentData['appointmentDate'] = $appointment->appointmentDate->toDateTime()->format('Y-m-d\TH:i:s\Z');
        } else {
            $appointmentData['appointmentDate'] = '';
        }
        
        // Handle created date
        if (isset($appointment->createdAt) && is_object($appointment->createdAt) && method_exists($appointment->createdAt, 'toDateTime')) {
            $appointmentData['createdAt'] = $appointment->createdAt->toDateTime()->format('Y-m-d\TH:i:s\Z');
        } else {
            $appointmentData['createdAt'] = '';
        }
        
        // Handle updated date
        if (isset($appointment->updatedAt) && is_object($appointment->updatedAt) && method_exists($appointment->updatedAt, 'toDateTime')) {
            $appointmentData['updatedAt'] = $appointment->updatedAt->toDateTime()->format('Y-m-d\TH:i:s\Z');
        } else {
            $appointmentData['updatedAt'] = '';
        }
        
        $appointments[] = $appointmentData;
    }
    
    logAppointments("🎰 All appointments fetched", [
        'totalAppointments' => count($appointments)
    ]);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => '🎰 SLOT-BASED: Appointments loaded successfully',
        'appointments' => $appointments,
        'count' => count($appointments),
        'systemType' => 'SLOT_BASED',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (MongoDB\Driver\Exception\Exception $e) {
    logAppointments("❌ MongoDB error", [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_type' => 'MongoDB Exception'
    ]);
    
} catch (Exception $e) {
    logAppointments("❌ General error", [
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error loading appointments: ' . $e->getMessage(),
        'error_type' => 'General Exception'
    ]);
}
?>