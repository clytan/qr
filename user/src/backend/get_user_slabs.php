<?php
header('Content-Type: application/json');
include 'dbconfig/connection.php';

try {
    $query = "SELECT id, name, ref_commission FROM qr.user_slab WHERE is_deleted = 0 ORDER BY name ASC";
    $result = $conn->query($query);
    
    if ($result) {
        $slabs = array();
        while ($row = $result->fetch_assoc()) {
            $slabs[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'ref_commission' => $row['ref_commission']
            );
        }
        
        echo json_encode(array(
            'status' => true,
            'data' => $slabs
        ));
    } else {
        echo json_encode(array(
            'status' => false,
            'message' => 'Failed to fetch user slabs'
        ));
    }
} catch (Exception $e) {
    echo json_encode(array(
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}

$conn->close();
?>
