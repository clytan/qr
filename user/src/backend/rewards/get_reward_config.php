<?php
/**
 * Get Reward Configuration
 * Returns spin timing and other config settings
 */

header('Content-Type: application/json');
require_once '../dbconfig/connection.php';
session_start();

try {
    // Get all config values
    $sql = "SELECT config_key, config_value FROM reward_config";
    $result = $conn->query($sql);
    
    $config = [];
    while ($row = $result->fetch_assoc()) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
    // Parse times and calculate current state
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    
    $spinStartTime = isset($config['spin_start_time']) ? $config['spin_start_time'] : '00:00:00';
    $spinEndTime = isset($config['spin_end_time']) ? $config['spin_end_time'] : '21:00:00';
    $maxWinners = isset($config['max_winners_per_draw']) ? (int)$config['max_winners_per_draw'] : 30;
    $spinDuration = isset($config['spin_duration_seconds']) ? (int)$config['spin_duration_seconds'] : 10;
    
    $startDateTime = new DateTime($today . ' ' . $spinStartTime);
    $endDateTime = new DateTime($today . ' ' . $spinEndTime);
    
    // Calculate status
    $isSpinning = ($now >= $startDateTime && $now < $endDateTime);
    $hasEnded = ($now >= $endDateTime);
    
    // Time remaining until end (in seconds)
    $timeRemaining = 0;
    if ($isSpinning) {
        $timeRemaining = $endDateTime->getTimestamp() - $now->getTimestamp();
    }
    
    echo json_encode([
        'status' => true,
        'config' => [
            'spin_start_time' => $spinStartTime,
            'spin_end_time' => $spinEndTime,
            'max_winners' => $maxWinners,
            'spin_duration' => $spinDuration
        ],
        'state' => [
            'is_spinning' => $isSpinning,
            'has_ended' => $hasEnded,
            'time_remaining_seconds' => $timeRemaining,
            'current_time' => $now->format('H:i:s'),
            'end_time' => $spinEndTime
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
