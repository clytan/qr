<?php
header('Content-Type: application/json');
require_once './dbconfig/connection.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

$sql = "
    SELECT 
        ui.id as id, ui.user_id as user_id, ui.invoice_number as invoice_number, ui.invoice_type as invoice_type, ui.created_on as created_on,
        ui.amount as amount, ui.cgst, ui.sgst as sgst, ui.igst as igst, ui.gst_total as gst_total, 
        ui.total_amount as total_amount, ui.status as status, ui.payment_mode as payment_mode, ui.payment_reference as payment_reference,
        u.user_full_name as full_name, u.user_email as email, u.user_phone as phone, u.user_qr_id as qr_id
    FROM 
        user_invoice ui
    JOIN 
        user_user u ON ui.user_id = u.id
    WHERE 
        ui.is_deleted = ?
    ORDER BY 
        ui.created_on DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$is_deleted = 0;
$stmt->bind_param('i', $is_deleted);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$invoices = [];

while ($row = $result->fetch_assoc()) {
    $invoices[] = $row;
}

$stmt->close();

if (empty($invoices)) {
    echo json_encode(['status' => false, 'message' => 'No invoices found']);
} else {
    echo json_encode(['status' => true, 'data' => $invoices]);
}
