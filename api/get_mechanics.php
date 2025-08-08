<?php
// get_mechanics_slot_based.php - SLOT-BASED SYSTEM
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control, Pragma');

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

function logMechanics($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] SLOT_BASED_MECHANICS: $message";
    if ($data) {
        $log .= " | " . json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    error_log($log);
}

try {
    logMechanics("🎰 SLOT-BASED SYSTEM: Starting mechanics fetch");
    
    // Connect to MongoDB
    $client = new MongoDB\Client($mongoUri, [
        'connectTimeoutMS' => 10000,
        'serverSelectionTimeoutMS' => 10000
    ]);
    
    $database = $client->selectDatabase($databaseName);
    $mechanicsCollection = $database->selectCollection('mechanics');
    
    // Test connection
    $database->command(['ping' => 1]);
    logMechanics("✅ MongoDB connection successful");
    
    // Get date parameter (default to today)
    $checkDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    logMechanics("📅 Checking slots for date: $checkDate");
    
    // Fetch all mechanics from database
    $cursor = $mechanicsCollection->find([], [
        'sort' => ['name' => 1]
    ]);
    
    $mechanics = [];
    
    foreach ($cursor as $mechanic) {
        $mechanicId = (string)$mechanic->_id;
        $mechanicName = $mechanic->name ?? 'Unknown';
        
        // 🎰 SLOT-BASED: Get current available slots from database
        $currentSlots = isset($mechanic->availableSlots) ? (int)$mechanic->availableSlots : 4;
        $totalSlots = isset($mechanic->totalSlots) ? (int)$mechanic->totalSlots : 4;
        
        logMechanics("🎰 $mechanicName slots from database", [
            'availableSlots' => $currentSlots,
            'totalSlots' => $totalSlots
        ]);
        
        // Build mechanic data with SLOT-BASED information
        $mechanicData = [
            '_id' => $mechanicId,
            'name' => $mechanicName,
            'email' => $mechanic->email ?? '',
            'contact' => $mechanic->contact ?? '',
            'specialization' => $mechanic->specialization ?? 'General Repair',
            'experience' => (int)($mechanic->experience ?? 5),
            'shift' => $mechanic->shift ?? 'Full Day',
            'hourlyRate' => (float)($mechanic->hourlyRate ?? 0),
            'available' => (bool)($mechanic->available ?? true),
            
            // 🎰 SLOT-BASED: Use database stored slots
            'availableSlots' => $currentSlots,
            'totalSlots' => $totalSlots,
            'bookedSlots' => $totalSlots - $currentSlots,
            'isAvailable' => $currentSlots > 0,
            
            // Display helpers
            'slotText' => "$currentSlots/$totalSlots slots available",
            'statusText' => $currentSlots > 0 ? 'Available' : 'Fully Booked',
            
            // Metadata
            'checkDate' => $checkDate,
            'lastUpdated' => date('Y-m-d H:i:s'),
            'systemType' => 'SLOT_BASED'
        ];
        
        // Handle created date
        if (isset($mechanic->createdAt) && is_object($mechanic->createdAt) && method_exists($mechanic->createdAt, 'toDateTime')) {
            $mechanicData['createdAt'] = $mechanic->createdAt->toDateTime()->format('Y-m-d\TH:i:s\Z');
        } else {
            $mechanicData['createdAt'] = '';
        }
        
        $mechanics[] = $mechanicData;
        
        // Special logging for David Wilson
        if ($mechanicName === 'David Wilson') {
            logMechanics("🎯 DAVID WILSON SLOT-BASED RESULT", [
                'availableSlots' => $currentSlots,
                'totalSlots' => $totalSlots,
                'display' => "$currentSlots/$totalSlots slots available",
                'source' => 'DATABASE_STORED_SLOTS'
            ]);
        }
    }
    
    logMechanics("🎰 All mechanics processed with SLOT-BASED system", [
        'totalMechanics' => count($mechanics),
        'checkDate' => $checkDate
    ]);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => '🎰 SLOT-BASED: Mechanics loaded with database-stored slots',
        'mechanics' => $mechanics,
        'count' => count($mechanics),
        'checkDate' => $checkDate,
        'systemType' => 'SLOT_BASED',
        'slotLogic' => [
            'system' => 'Database-stored slot counts',
            'booking' => 'Decreases availableSlots by 1',
            'completion' => 'Increases availableSlots by 1', 
            'calculation' => 'Direct database slot management',
            'storage' => 'availableSlots field in mechanics collection'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (MongoDB\Driver\Exception\Exception $e) {
    logMechanics("❌ MongoDB error", [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_type' => 'MongoDB Exception'
    ]);
    
} catch (Exception $e) {
    logMechanics("❌ General error", [
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error loading mechanics: ' . $e->getMessage(),
        'error_type' => 'General Exception'
    ]);
}
?>