<?php
/**
 * GST Calculation Test Script (GST INCLUSIVE)
 * This script demonstrates how GST is calculated when the total amount includes GST
 */

function calculateGSTInclusive($totalAmount) {
    // The total amount INCLUDES GST (18%)
    // We need to extract the base amount and GST components
    
    // Calculate base amount (reverse calculation)
    // Total = Base × 1.18
    // Base = Total ÷ 1.18
    $base_amount = round($totalAmount / 1.18, 2);
    
    // GST rates for India (intra-state)
    $cgst_rate = 9.0; // 9%
    $sgst_rate = 9.0; // 9%
    
    // Calculate individual GST components
    $cgst = round(($base_amount * $cgst_rate) / 100, 2);
    $sgst = round(($base_amount * $sgst_rate) / 100, 2);
    $igst = 0.00; // Not applicable for intra-state
    
    // Calculate totals
    $gst_total = $cgst + $sgst + $igst;
    
    // Verify total (should match input)
    $calculated_total = $base_amount + $gst_total;
    
    // Adjust for rounding if needed
    if ($calculated_total != $totalAmount) {
        $difference = $totalAmount - $calculated_total;
        $base_amount = round($base_amount + $difference, 2);
    }
    
    return [
        'total_amount' => $totalAmount,
        'base_amount' => $base_amount,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'igst' => $igst,
        'gst_total' => $gst_total
    ];
}

function displayInvoice($calculation) {
    echo "\n";
    echo "========================================\n";
    echo "           INVOICE BREAKDOWN            \n";
    echo "========================================\n";
    echo "\n";
    echo "Base Amount:     ₹" . number_format($calculation['base_amount'], 2) . "\n";
    echo "CGST (9%):       ₹" . number_format($calculation['cgst'], 2) . "\n";
    echo "SGST (9%):       ₹" . number_format($calculation['sgst'], 2) . "\n";
    echo "IGST:            ₹" . number_format($calculation['igst'], 2) . "\n";
    echo "----------------------------------------\n";
    echo "GST Total:       ₹" . number_format($calculation['gst_total'], 2) . "\n";
    echo "========================================\n";
    echo "TOTAL AMOUNT:    ₹" . number_format($calculation['total_amount'], 2) . "\n";
    echo "========================================\n";
    echo "\n";
}

// Test with different amounts
echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║  GST INCLUSIVE CALCULATION - INDIA      ║\n";
echo "╚══════════════════════════════════════════╝\n";
echo "\n";
echo "NOTE: Total amount from UI INCLUDES GST\n";
echo "We extract the base amount and GST breakdown\n";

// Test Case 1: ₹999
echo "\n--- TEST CASE 1: User Pays ₹999 (Total) ---\n";
$result1 = calculateGSTInclusive(999);
displayInvoice($result1);

// Test Case 2: ₹1000
echo "\n--- TEST CASE 2: User Pays ₹1000 (Total) ---\n";
$result2 = calculateGSTInclusive(1000);
displayInvoice($result2);

// Test Case 3: ₹500
echo "\n--- TEST CASE 3: User Pays ₹500 (Total) ---\n";
$result3 = calculateGSTInclusive(500);
displayInvoice($result3);

// Test Case 4: ₹1500
echo "\n--- TEST CASE 4: User Pays ₹1500 (Total) ---\n";
$result4 = calculateGSTInclusive(1500);
displayInvoice($result4);

// Summary
echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║              SUMMARY                     ║\n";
echo "╚══════════════════════════════════════════╝\n";
echo "\n";
echo "GST Structure: CGST (9%) + SGST (9%) = 18% Total\n";
echo "Calculation Method: GST INCLUSIVE\n";
echo "\n";
echo "How it works:\n";
echo "1. UI sends total amount (e.g., ₹999) - this is what user pays\n";
echo "2. Backend calculates base = total ÷ 1.18\n";
echo "3. Backend calculates CGST = base × 9%\n";
echo "4. Backend calculates SGST = base × 9%\n";
echo "5. Total GST = CGST + SGST\n";
echo "6. Verify: Base + Total GST = Total Amount (₹999)\n";
echo "7. Invoice is saved with all breakdown details\n";
echo "\n";
echo "Example for ₹999:\n";
echo "  Base Amount = 999 ÷ 1.18 = ₹846.61\n";
echo "  CGST (9%) = 846.61 × 0.09 = ₹76.19\n";
echo "  SGST (9%) = 846.61 × 0.09 = ₹76.19\n";
echo "  Total GST = 76.19 + 76.19 = ₹152.38\n";
echo "  Final = 846.61 + 152.38 = ₹999.00 ✓\n";
echo "\n";
?>
