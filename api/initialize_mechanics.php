<?php
require_once 'config.php';

try {
    // Create MongoDB client
    $client = new MongoDB\Client($mongoUri);
    $database = $client->selectDatabase($databaseName);
    $mechanicsCollection = $database->selectCollection('mechanics');
    
    // Clear existing
    $mechanicsCollection->deleteMany([]);
    
    // Insert mechanics with slot data
    $mechanics = [
        [
            'name' => 'David Wilson',
            'specialization' => 'Transmission & Drivetrain',
            'experience' => 10,
            'availableSlots' => 3,
            'totalSlots' => 4
        ],
        [
            'name' => 'James Davis',  
            'specialization' => 'General Maintenance & Oil Change',
            'experience' => 5,
            'availableSlots' => 4,
            'totalSlots' => 4
        ],
        [
            'name' => 'John Smith',
            'specialization' => 'Brake Systems & Suspension', 
            'experience' => 6,
            'availableSlots' => 4,
            'totalSlots' => 4
        ],
        [
            'name' => 'Mike Johnson',
            'specialization' => 'Engine Repair & Diagnostics',
            'experience' => 8, 
            'availableSlots' => 4,
            'totalSlots' => 4
        ],
        [
            'name' => 'Robert Brown',
            'specialization' => 'Electrical Systems & A/C',
            'experience' => 7,
            'availableSlots' => 4, 
            'totalSlots' => 4
        ]
    ];
    
    $result = $mechanicsCollection->insertMany($mechanics);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mechanics initialized successfully',
        'inserted' => $result->getInsertedCount()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
