<?php
/**
 * QR Code Image Generator Helper
 * 
 * Generates a QR code image for a user's profile URL
 */

require_once __DIR__ . '/../../libs/phpqrcode/phpqrcode.php';

/**
 * Generate a QR code image for a user
 * 
 * @param string $userQrId The user's QR ID (e.g., ZOK0000001)
 * @param string|null $outputPath Optional specific output path. If null, generates temp file.
 * @return string|false Path to the generated image file, or false on failure
 */
function generateUserQRImage($userQrId, $outputPath = null) {
    try {
        // Generate the profile URL
        $profileUrl = "https://zokli.io/profile.php?qr=" . urlencode($userQrId);
        
        // Determine output path
        if ($outputPath === null) {
            $tempDir = __DIR__ . '/../../temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $outputPath = $tempDir . '/qr_' . $userQrId . '_' . time() . '.png';
        }
        
        // Generate QR code with high quality settings
        // Size 8 = 8 pixels per module, Margin 2 = 2 module margin
        QRcode::png($profileUrl, $outputPath, QR_ECLEVEL_H, 8, 2);
        
        if (file_exists($outputPath)) {
            error_log("QR code generated successfully: " . $outputPath);
            return $outputPath;
        } else {
            error_log("QR code generation failed: file not created");
            return false;
        }
    } catch (Exception $e) {
        error_log("QR code generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate QR code with custom colors
 * 
 * @param string $userQrId The user's QR ID
 * @param string $darkColor Hex color for dark modules (e.g., #000000)
 * @param string $lightColor Hex color for light modules (e.g., #FFFFFF)
 * @return string|false Path to the generated image file, or false on failure
 */
function generateColoredQRImage($userQrId, $darkColor = '#000000', $lightColor = '#FFFFFF') {
    // For now, generate standard black/white QR
    // Color customization can be added later if needed
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
