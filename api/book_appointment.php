<?php
// book_appointment_slot_based.php - SLOT-BASED BOOKING SYSTEM
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

function logBooking($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] SLOT_BOOKING: $message";
    if ($data) {
        $log .= " | Data: " . json_encode($data);
    }
    error_log($log);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

// Validate required fields
$required = ['clientName', 'clientPhone', 'clientAddress', 'carLicense', 'carEngine', 'appointmentDate', 'mechanicId'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Missing field: $field"
        ]);
        exit;
    }
}

try {
    logBooking("🎰 SLOT-BASED: Starting appointment booking", $input);
    
    // MongoDB connection
    require_once 'config.php';
$client = new \MongoDB\Client($mongoUri, [
    'typeMap' => [
        'root' => 'array',
        'document' => 'array',
        'array' => 'array'
    ]
]);
    $database = $client->selectDatabase($databaseName);
    
    // Test connection
    $database->command(['ping' => 1]);
    
    $appointmentsCollection = $database->selectCollection('appointments');
    $mechanicsCollection = $database->selectCollection('mechanics');
    
    // Validate mechanic exists
    $mechanicId = trim($input['mechanicId']);
    
    $mechanic = null;
    if (preg_match('/^[a-f\d]{24}$/i', $mechanicId)) {
        $mechanic = $mechanicsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($mechanicId)]);
    }
    
    if (!$mechanic) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid mechanic selected'
        ]);
        exit;
    }
    
    logBooking("Mechanic validated", ['mechanicId' => $mechanicId, 'mechanicName' => $mechanic->name]);
    
    // 🎰 SLOT-BASED: Check if mechanic has available slots
    $currentSlots = isset($mechanic->availableSlots) ? (int)$mechanic->availableSlots : 4;
    $totalSlots = isset($mechanic->totalSlots) ? (int)$mechanic->totalSlots : 4;
    
    logBooking("🎰 Current slot availability", [
        'mechanicName' => $mechanic->name,
        'currentSlots' => $currentSlots,
        'totalSlots' => $totalSlots
    ]);
    
    if ($currentSlots <= 0) {
        echo json_encode([
            'success' => false,
            'message' => "Mechanic {$mechanic->name} is fully booked. No slots available.",
            'mechanicName' => $mechanic->name,
            'availableSlots' => $currentSlots,
            'totalSlots' => $totalSlots
        ]);
        exit;
    }
    
    // Parse and validate appointment date
    $appointmentDateStr = trim($input['appointmentDate']);
    
    try {
        $appointmentDate = new DateTime($appointmentDateStr);
        $today = new DateTime();
        
        if ($appointmentDate < $today->setTime(0, 0, 0)) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot book appointments for past dates'
            ]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format'
        ]);
        exit;
    }
    
    $mongoAppointmentDate = new MongoDB\BSON\UTCDateTime($appointmentDate->getTimestamp() * 1000);
    
    logBooking("Date validation passed", [
        'appointmentDate' => $appointmentDate->format('Y-m-d H:i:s')
    ]);
    
    // Check for duplicate bookings (same client phone or car license on same date)
    $clientPhone = trim($input['clientPhone']);
    $carLicense = strtoupper(trim($input['carLicense']));
    
    // Create date range for the appointment day
    $startOfDay = clone $appointmentDate;
    $startOfDay->setTime(0, 0, 0);
    $endOfDay = clone $appointmentDate;
    $endOfDay->setTime(23, 59, 59);
    
    $mongoStartDate = new MongoDB\BSON\UTCDateTime($startOfDay->getTimestamp() * 1000);
    $mongoEndDate = new MongoDB\BSON\UTCDateTime($endOfDay->getTimestamp() * 1000);
    
    $duplicateByPhone = $appointmentsCollection->findOne([
        'clientPhone' => $clientPhone,
        'appointmentDate' => [
            '$gte' => $mongoStartDate,
            '$lte' => $mongoEndDate
        ],
        'status' => [
            '$in' => ['confirmed', 'pending', 'in-progress']
        ]
    ]);
    
    if ($duplicateByPhone) {
        echo json_encode([
            'success' => false,
            'message' => 'You already have an appointment booked for ' . $appointmentDate->format('Y-m-d') . '. Please contact us to modify your existing appointment.',
            'duplicateType' => 'phone',
            'existingAppointment' => [
                'clientName' => $duplicateByPhone->clientName,
                'mechanicId' => $duplicateByPhone->mechanicId,
                'date' => $duplicateByPhone->appointmentDate->toDateTime()->format('Y-m-d')
            ]
        ]);
        exit;
    }
    
    logBooking("Duplicate check passed");
    
    // 🎰 SLOT-BASED: Start transaction to book appointment and decrease slots atomically
    $session = $client->startSession();
    
    try {
        $session->startTransaction();
        
        // Create the appointment
        $appointmentData = [
            'clientName' => sanitizeInput($input['clientName']),
            'clientPhone' => sanitizeInput($clientPhone),
            'clientAddress' => sanitizeInput($input['clientAddress']),
            'carLicense' => $carLicense,
            'carEngine' => sanitizeInput(strtoupper($input['carEngine'])),
            'appointmentDate' => $mongoAppointmentDate,
            'mechanicId' => $mechanicId,
            'status' => 'confirmed', // Default status for new appointments
            'notes' => isset($input['notes']) ? sanitizeInput($input['notes']) : '',
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $appointmentResult = $appointmentsCollection->insertOne($appointmentData, ['session' => $session]);
        
        // 🎰 SLOT-BASED: Decrease available slots by 1
        $newSlotCount = $currentSlots - 1;
        
        $slotUpdateResult = $mechanicsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($mechanicId)],
            [
                '$set' => [
                    'availableSlots' => $newSlotCount,
                    'updatedAt' => new MongoDB\BSON\UTCDateTime()
                ]
            ],
            ['session' => $session]
        );
        
        if ($slotUpdateResult->getModifiedCount() !== 1) {
            throw new Exception('Failed to update mechanic slot count');
        }
        
        // Commit the transaction
        $session->commitTransaction();
        
        logBooking("🎰 SLOT-BASED: Appointment booked and slot decreased successfully", [
            'appointmentId' => (string)$appointmentResult->getInsertedId(),
            'mechanicName' => $mechanic->name,
            'clientName' => $appointmentData['clientName'],
            'date' => $appointmentDate->format('Y-m-d'),
            'previousSlots' => $currentSlots,
            'newSlots' => $newSlotCount
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '🎰 SLOT-BASED: Appointment booked successfully! Slot count updated.',
            'appointmentId' => (string)$appointmentResult->getInsertedId(),
            'appointment' => [
                'clientName' => $appointmentData['clientName'],
                'mechanicName' => $mechanic->name,
                'mechanicId' => $mechanicId,
                'appointmentDate' => $appointmentDate->format('Y-m-d'),
                'status' => 'confirmed',
                'carLicense' => $carLicense
            ],
            'slotUpdate' => [
                'mechanicId' => $mechanicId,
                'mechanicName' => $mechanic->name,
                'date' => $appointmentDate->format('Y-m-d'),
                'previousAvailableSlots' => $currentSlots,
                'currentAvailableSlots' => $newSlotCount,
                'totalSlots' => $totalSlots,
                'slotChange' => -1,
                'systemType' => 'SLOT_BASED'
            ]
        ]);
        
    } catch (Exception $transactionError) {
        $session->abortTransaction();
        throw $transactionError;
    } finally {
        $session->endSession();
    }
    
} catch (MongoDB\Driver\Exception\Exception $e) {
    logBooking("❌ MongoDB error", [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    logBooking("❌ General error", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Booking error: ' . $e->getMessage()
    ]);
}
?>