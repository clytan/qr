<?php
/**
 * Get Available QR Frames API
 * 
 * Returns list of all available frame images from the frames folder
 */

header('Content-Type: application/json');

try {
    // Path to frames folder
    $framesDir = __DIR__ . '/../../frames';
    
    if (!is_dir($framesDir)) {
        echo json_encode([
            'status' => false,
            'message' => 'Frames directory not found'
        ]);
        exit;
    }
    
    // Get all PNG files from frames folder
    $frames = [];
    $files = scandir($framesDir);
    
    foreach ($files as $file) {
        // Only include PNG files
        if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
            $frames[] = [
                'id' => $file,
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'url' => '/user/src/frames/' . $file,
                'thumbnail' => '/user/src/frames/' . $file
            ];
        }
    }
    
    // Sort frames by name
    usort($frames, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Add "No Frame" option at the beginning
    array_unshift($frames, [
        'id' => 'none',
        'name' => 'No Frame',
        'url' => null,
        'thumbnail' => null
    ]);
    
    // Add default frame option
    array_splice($frames, 1, 0, [[
        'id' => 'default',
        'name' => 'Default',
        'url' => '/user/src/assets/images/frame.png',
        'thumbnail' => '/user/src/assets/images/frame.png'
    ]]);
    
    echo json_encode([
        'status' => true,
        'data' => $frames,
        'count' => count($frames)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Error loading frames: ' . $e->getMessage()
    ]);
}
?>
