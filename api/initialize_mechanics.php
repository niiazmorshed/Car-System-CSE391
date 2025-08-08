<?php
// initialize_mechanics_slots.php - Initialize mechanics with slot-based system
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Connect to MongoDB
    $client = new MongoDB\Client($mongoUri);
    $database = $client->selectDatabase($databaseName);
    $mechanicsCollection = $database->selectCollection('mechanics');
    
    // Test connection
    $database->command(['ping' => 1]);
    
    // Initialize mechanics with slot-based data
    $mechanics = [
        [
            'name' => 'David Wilson',
            'email' => 'david.wilson@autofix.com',
            'contact' => '0134567890',
            'specialization' => 'Transmission & Drivetrain',
            'experience' => 10,
            'shift' => 'Full Day',
            'hourlyRate' => 85.0,
            'available' => true,
            'availableSlots' => 3,  // 🎰 SLOT-BASED: David has 3/4 slots available
            'totalSlots' => 4,      // 🎰 SLOT-BASED: Total capacity per day
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'James Davis',
            'email' => 'james.davis@autofix.com',
            'contact' => '0156789012',
            'specialization' => 'General Maintenance & Oil Change',
            'experience' => 5,
            'shift' => 'Morning',
            'hourlyRate' => 65.0,
            'available' => true,
            'availableSlots' => 4,  // 🎰 SLOT-BASED: James has 4/4 slots available
            'totalSlots' => 4,
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'John Smith',
            'email' => 'john.smith@autofix.com',
            'contact' => '0987654321',
            'specialization' => 'Brake Systems & Suspension',
            'experience' => 6,
            'shift' => 'Afternoon',
            'hourlyRate' => 70.0,
            'available' => true,
            'availableSlots' => 4,  // 🎰 SLOT-BASED: John has 4/4 slots available
            'totalSlots' => 4,
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Mike Johnson',
            'email' => 'mike.johnson@autofix.com',
            'contact' => '0123456789',
            'specialization' => 'Engine Repair & Diagnostics',
            'experience' => 8,
            'shift' => 'Morning',
            'hourlyRate' => 75.0,
            'available' => true,
            'availableSlots' => 4,  // 🎰 SLOT-BASED: Mike has 4/4 slots available
            'totalSlots' => 4,
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Robert Brown',
            'email' => 'robert.brown@autofix.com',
            'contact' => '0145678901',
            'specialization' => 'Electrical Systems & A/C',
            'experience' => 7,
            'shift' => 'Evening',
            'hourlyRate' => 80.0,
            'available' => true,
            'availableSlots' => 4,  // 🎰 SLOT-BASED: Robert has 4/4 slots available
            'totalSlots' => 4,
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ]
    ];
    
    // Clear existing mechanics and insert new ones
    $mechanicsCollection->deleteMany([]);
    $result = $mechanicsCollection->insertMany($mechanics);
    
    echo json_encode([
        'success' => true,
        'message' => '🎰 SLOT-BASED: Mechanics initialized successfully',
        'inserted' => $result->getInsertedCount(),
        'mechanics' => array_map(function($mechanic) {
            return [
                'name' => $mechanic['name'],
                'availableSlots' => $mechanic['availableSlots'],
                'totalSlots' => $mechanic['totalSlots'],
                'specialization' => $mechanic['specialization']
            ];
        }, $mechanics),
        'note' => 'David Wilson has 3/4 slots to simulate existing bookings, others have full availability'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error initializing mechanics: ' . $e->getMessage()
    ]);
}
?>