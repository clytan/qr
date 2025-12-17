<?php
/**
 * QR Code Image Generator Helper
 * 
 * Generates a QR code image for a user's profile URL using Google Charts API
 * This is more reliable than custom PHP QR generation
 */

/**
 * Generate a QR code image for a user
 * 
 * @param string $userQrId The user's QR ID (e.g., ZOK0000001)
 * @param string|null $outputPath Optional specific output path. If null, generates temp file.
 * @return string|false Path to the generated image file, or false on failure
 */
function generateUserQRImage($userQrId, $outputPath = null) {
    try {
        // Generate the profile URL - must match the actual profile page path
        $profileUrl = "https://zokli.in/user/src/ui/profile.php?qr=" . urlencode($userQrId);
        
        // Determine output path
        if ($outputPath === null) {
            $tempDir = __DIR__ . '/../../temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $outputPath = $tempDir . '/qr_' . $userQrId . '_' . time() . '.png';
        }
        
        // Use Google Charts API to generate QR code (reliable and standard-compliant)
        $googleChartUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($profileUrl) . "&choe=UTF-8&chld=H|2";
        
        // Download the QR code image
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $imageData = @file_get_contents($googleChartUrl, false, $context);
        
        if ($imageData === false) {
            error_log("Failed to fetch QR from Google Charts API, trying alternative method");
            // Fallback: Use QR Server API (another reliable service)
            $qrServerUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($profileUrl) . "&ecc=H&margin=10";
            $imageData = @file_get_contents($qrServerUrl, false, $context);
        }
        
        if ($imageData === false) {
            error_log("QR code generation failed: Could not fetch from API");
            return false;
        }
        
        // Save to file
        if (file_put_contents($outputPath, $imageData) !== false) {
            error_log("QR code generated successfully: " . $outputPath);
            return $outputPath;
        } else {
            error_log("QR code generation failed: Could not write file");
            return false;
        }
    } catch (Exception $e) {
        error_log("QR code generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate QR code with custom colors (not supported by API, returns standard)
 * 
 * @param string $userQrId The user's QR ID
 * @param string $darkColor Hex color for dark modules (e.g., #000000)
 * @param string $lightColor Hex color for light modules (e.g., #FFFFFF)
 * @return string|false Path to the generated image file, or false on failure
 */
function generateColoredQRImage($userQrId, $darkColor = '#000000', $lightColor = '#FFFFFF') {
    // API doesn't support custom colors, generate standard QR
    return generateUserQRImage($userQrId);
}

/**
 * Clean up temporary QR images
 * 
 * @param string $filePath Path to the file to delete
 * @return bool True if deleted, false otherwise
 */
function cleanupQRImage($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}
