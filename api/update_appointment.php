<?php
// update_appointment_slot_based.php - SLOT-BASED STATUS UPDATE SYSTEM
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

function logStatusUpdate($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] SLOT_STATUS_UPDATE: $message";
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
if (empty($input['appointmentId']) || empty($input['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: appointmentId and status'
    ]);
    exit;
}

$appointmentId = trim($input['appointmentId']);
$newStatus = trim($input['status']);

// Validate appointment ID format
if (!preg_match('/^[a-f\d]{24}$/i', $appointmentId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid appointment ID format'
    ]);
    exit;
}

// Validate status values
$validStatuses = ['confirmed', 'pending', 'in-progress', 'completed', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
    ]);
    exit;
}

try {
    logStatusUpdate("🎰 SLOT-BASED: Starting status update", [
        'appointmentId' => $appointmentId,
        'newStatus' => $newStatus
    ]);
    
    // MongoDB connection
    $client = new MongoDB\Client($mongoUri);
    $database = $client->selectDatabase($databaseName);
    
    // Test connection
    $database->command(['ping' => 1]);
    
    $appointmentsCollection = $database->selectCollection('appointments');
    $mechanicsCollection = $database->selectCollection('mechanics');
    
    // Get current appointment
    $appointment = $appointmentsCollection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($appointmentId)
    ]);
    
    if (!$appointment) {
        echo json_encode([
            'success' => false,
            'message' => 'Appointment not found'
        ]);
        exit;
    }
    
    $currentStatus = $appointment->status ?? 'unknown';
    $mechanicId = $appointment->mechanicId ?? null;
    
    logStatusUpdate("Current appointment found", [
        'currentStatus' => $currentStatus,
        'newStatus' => $newStatus,
        'mechanicId' => $mechanicId,
        'clientName' => $appointment->clientName ?? 'Unknown'
    ]);
    
    // Get mechanic information
    $mechanic = $mechanicsCollection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($mechanicId)
    ]);
    
    if (!$mechanic) {
        echo json_encode([
            'success' => false,
            'message' => 'Associated mechanic not found'
        ]);
        exit;
    }
    
    $currentSlots = isset($mechanic->availableSlots) ? (int)$mechanic->availableSlots : 4;
    $totalSlots = isset($mechanic->totalSlots) ? (int)$mechanic->totalSlots : 4;
    
    logStatusUpdate("Current mechanic slots", [
        'mechanicName' => $mechanic->name,
        'currentSlots' => $currentSlots,
        'totalSlots' => $totalSlots
    ]);
    
    // 🎰 SLOT-BASED: Determine slot changes based on status transitions
    $slotChange = 0;
    $shouldUpdateSlots = false;
    
    // Active statuses (occupy slots): confirmed, pending, in-progress
    // Inactive statuses (free slots): completed, cancelled
    
    $activeStatuses = ['confirmed', 'pending', 'in-progress'];
    $inactiveStatuses = ['completed', 'cancelled'];
    
    $wasActive = in_array($currentStatus, $activeStatuses);
    $willBeActive = in_array($newStatus, $activeStatuses);
    
    if ($wasActive && !$willBeActive) {
        // Transition from active to inactive = FREE UP SLOT (+1)
        $slotChange = 1;
        $shouldUpdateSlots = true;
        logStatusUpdate("🎰 SLOT CHANGE: Freeing up slot (active → inactive)");
    } elseif (!$wasActive && $willBeActive) {
        // Transition from inactive to active = OCCUPY SLOT (-1)
        if ($currentSlots > 0) {
            $slotChange = -1;
            $shouldUpdateSlots = true;
            logStatusUpdate("🎰 SLOT CHANGE: Occupying slot (inactive → active)");
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot reactivate appointment: No available slots for this mechanic'
            ]);
            exit;
        }
    } else {
        logStatusUpdate("🎰 NO SLOT CHANGE: Status change within same category", [
            'wasActive' => $wasActive,
            'willBeActive' => $willBeActive
        ]);
    }
    
    // Start transaction to update appointment and slots atomically
    $session = $client->startSession();
    
    try {
        $session->startTransaction();
        
        // Update appointment status
        $updateData = [
            'status' => $newStatus,
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        // Add optional fields if provided
        if (isset($input['appointmentDate'])) {
            $newDate = new DateTime($input['appointmentDate']);
            $updateData['appointmentDate'] = new MongoDB\BSON\UTCDateTime($newDate->getTimestamp() * 1000);
        }
        
        if (isset($input['mechanicId']) && $input['mechanicId'] !== $mechanicId) {
            // Handle mechanic reassignment (complex operation)
            echo json_encode([
                'success' => false,
                'message' => 'Mechanic reassignment requires special handling. Please use the dedicated reassignment endpoint.'
            ]);
            exit;
        }
        
        $appointmentUpdateResult = $appointmentsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($appointmentId)],
            ['$set' => $updateData],
            ['session' => $session]
        );
        
        if ($appointmentUpdateResult->getModifiedCount() !== 1) {
            throw new Exception('Failed to update appointment');
        }
        
        $newSlotCount = $currentSlots;
        
        // 🎰 SLOT-BASED: Update mechanic slots if needed
        if ($shouldUpdateSlots) {
            $newSlotCount = $currentSlots + $slotChange;
            $newSlotCount = max(0, min($totalSlots, $newSlotCount)); // Clamp between 0 and total
            
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
            
            logStatusUpdate("🎰 SLOT-BASED: Slots updated successfully", [
                'mechanicName' => $mechanic->name,
                'previousSlots' => $currentSlots,
                'newSlots' => $newSlotCount,
                'slotChange' => $slotChange
            ]);
        }
        
        // Commit the transaction
        $session->commitTransaction();
        
        logStatusUpdate("🎰 SLOT-BASED: Status update completed successfully", [
            'appointmentId' => $appointmentId,
            'statusChange' => "$currentStatus → $newStatus",
            'slotChange' => $slotChange,
            'newSlotCount' => $newSlotCount
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '🎰 SLOT-BASED: Appointment status updated successfully!',
            'appointmentId' => $appointmentId,
            'statusUpdate' => [
                'previousStatus' => $currentStatus,
                'newStatus' => $newStatus,
                'updatedAt' => date('Y-m-d H:i:s')
            ],
            'slotUpdate' => $shouldUpdateSlots ? [
                'mechanicId' => $mechanicId,
                'mechanicName' => $mechanic->name,
                'previousAvailableSlots' => $currentSlots,
                'currentAvailableSlots' => $newSlotCount,
                'totalSlots' => $totalSlots,
                'slotChange' => $slotChange,
                'reason' => "$currentStatus → $newStatus",
                'systemType' => 'SLOT_BASED'
            ] : null
        ]);
        
    } catch (Exception $transactionError) {
        $session->abortTransaction();
        throw $transactionError;
    } finally {
        $session->endSession();
    }
    
} catch (MongoDB\Driver\Exception\Exception $e) {
    logStatusUpdate("❌ MongoDB error", [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    logStatusUpdate("❌ General error", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Status update error: ' . $e->getMessage()
    ]);
}
?>