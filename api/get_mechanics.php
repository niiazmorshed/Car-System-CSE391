<?php
require_once 'config.php';

try {
    // Create MongoDB client using built-in extension
    $client = new MongoDB\Client($mongoUri, [
        'connectTimeoutMS' => 10000,
        'serverSelectionTimeoutMS' => 10000
    ]);
    
    $database = $client->selectDatabase($databaseName);
    $mechanicsCollection = $database->selectCollection('mechanics');
    
    // Test connection
    $database->command(['ping' => 1]);
    
    // Get mechanics
    $cursor = $mechanicsCollection->find([], ['sort' => ['name' => 1]]);
    $mechanics = [];
    
    foreach ($cursor as $mechanic) {
        $mechanics[] = [
            '_id' => (string)$mechanic->_id,
            'name' => $mechanic->name ?? 'Unknown',
            'specialization' => $mechanic->specialization ?? 'General Repair',
            'experience' => (int)($mechanic->experience ?? 5),
            'availableSlots' => (int)($mechanic->availableSlots ?? 4),
            'totalSlots' => (int)($mechanic->totalSlots ?? 4),
            'isAvailable' => ((int)($mechanic->availableSlots ?? 4)) > 0,
            'slotText' => ((int)($mechanic->availableSlots ?? 4)) . "/4 slots available"
        ];
    }
    
    echo json_encode([
        'success' => true,
        'mechanics' => $mechanics,
        'count' => count($mechanics)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
