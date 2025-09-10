<?php
header('Content-Type: application/json');
require_once('./dbconfig/connection.php');

try {
    // Prepare slab query (no user input here, but good practice)
    $slab_sql = "SELECT id, name FROM user_slab WHERE is_deleted=0";
    $slab_result = $conn->query($slab_sql);

    if (!$slab_result) {
        throw new Exception("Failed to fetch user slabs: " . $conn->error);
    }

    $slabs = [];
    while ($row = $slab_result->fetch_assoc()) {
        $slabs[] = $row;
    }

    // Prepare user types query
    $type_sql = "SELECT id, user_type_name FROM user_user_type WHERE is_deleted=0";
    $type_result = $conn->query($type_sql);

    if (!$type_result) {
        throw new Exception("Failed to fetch user types: " . $conn->error);
    }

    $types = [];
    while ($row = $type_result->fetch_assoc()) {
        $types[] = $row;
    }

    echo json_encode([
        'status' => true,
        'slabs' => $slabs,
        'types' => $types
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
