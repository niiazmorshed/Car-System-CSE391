<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// For now, return hardcoded mechanics data
$mechanics = [
    [
        '_id' => '66b5a8c9d1e2f3a4b5c6d7e1',
        'name' => 'David Wilson',
        'specialization' => 'Transmission & Drivetrain',
        'experience' => 10,
        'availableSlots' => 3,
        'totalSlots' => 4,
        'isAvailable' => true,
        'slotText' => '3/4 slots available'
    ],
    [
        '_id' => '66b5a8c9d1e2f3a4b5c6d7e2',
        'name' => 'James Davis',
        'specialization' => 'General Maintenance & Oil Change',
        'experience' => 5,
        'availableSlots' => 4,
        'totalSlots' => 4,
        'isAvailable' => true,
        'slotText' => '4/4 slots available'
    ],
    [
        '_id' => '66b5a8c9d1e2f3a4b5c6d7e3',
        'name' => 'John Smith',
        'specialization' => 'Brake Systems & Suspension',
        'experience' => 6,
        'availableSlots' => 4,
        'totalSlots' => 4,
        'isAvailable' => true,
        'slotText' => '4/4 slots available'
    ],
    [
        '_id' => '66b5a8c9d1e2f3a4b5c6d7e4',
        'name' => 'Mike Johnson',
        'specialization' => 'Engine Repair & Diagnostics',
        'experience' => 8,
        'availableSlots' => 4,
        'totalSlots' => 4,
        'isAvailable' => true,
        'slotText' => '4/4 slots available'
    ],
    [
        '_id' => '66b5a8c9d1e2f3a4b5c6d7e5',
        'name' => 'Robert Brown',
        'specialization' => 'Electrical Systems & A/C',
        'experience' => 7,
        'availableSlots' => 4,
        'totalSlots' => 4,
        'isAvailable' => true,
        'slotText' => '4/4 slots available'
    ]
];

echo json_encode([
    'success' => true,
    'mechanics' => $mechanics,
    'count' => count($mechanics),
    'message' => 'Hardcoded mechanics data (working!)'
]);
?>
