<?php
require_once('../dbconfig/connection.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$code = isset($data['code']) ? strtoupper(trim($data['code'])) : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Promo code is required']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

try {
    // Check if promo code exists and is valid
    $sql = "SELECT * FROM promo_codes 
            WHERE code = ? 
            AND is_active = 1 
            AND (valid_from IS NULL OR valid_from <= NOW()) 
            AND (valid_until IS NULL OR valid_until >= NOW())
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code']);
        exit;
    }
    
    $promo = $result->fetch_assoc();
    
    // Check if promo code has reached max uses
    if ($promo['current_uses'] >= $promo['max_uses']) {
        echo json_encode(['success' => false, 'message' => 'This promo code has been fully redeemed']);
        exit;
    }
    
    // Check minimum amount requirement
    if ($amount < $promo['min_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => "Minimum order amount of ₹" . number_format($promo['min_amount'], 2) . " required for this promo code"
        ]);
        exit;
    }
    
    // Calculate discount
    $discount = 0;
    if ($promo['discount_type'] === 'percentage') {
        $discount = ($amount * $promo['discount_value']) / 100;
        
        // Apply max discount cap if set
        if ($promo['max_discount'] !== null && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        // Fixed discount
        $discount = $promo['discount_value'];
        
        // Discount cannot exceed the total amount
        if ($discount > $amount) {
            $discount = $amount;
        }
    }
    
    $discount = round($discount, 2);
    $finalAmount = round($amount - $discount, 2);
    
    // Ensure final amount is not negative
    if ($finalAmount < 0) {
        $finalAmount = 0;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Promo code applied successfully!',
        'promo_code' => $code,
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value'],
        'discount_amount' => $discount,
        'original_amount' => $amount,
        'final_amount' => $finalAmount,
        'description' => $promo['description']
    ]);
    
} catch (Exception $e) {
    error_log("Promo code validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error validating promo code']);
}

$conn->close();
?>
