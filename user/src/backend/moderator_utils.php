<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');

// Check if user is a moderator
function isUserModerator($user_id, $community_id)
{
    global $conn;
    $sql = "SELECT 1 FROM user_roles 
            WHERE user_id = ? AND community_id = ? 
            AND role_type IN ('moderator', 'admin') 
            AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $community_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Check if user is banned or in timeout
function checkUserPenalties($user_id, $community_id)
{
    global $conn;
    $sql = "SELECT penalty_type, end_time 
            FROM user_penalties 
            WHERE user_id = ? AND community_id = ? 
            AND is_active = 1 AND is_deleted = 0 
            AND (end_time IS NULL OR end_time > NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $community_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return [
            'penalized' => true,
            'type' => $row['penalty_type'],
            'end_time' => $row['end_time']
        ];
    }
    return ['penalized' => false];
}

// Get user's role in a community
function getUserRole($user_id, $community_id)
{
    global $conn;
    $sql = "SELECT role_type FROM user_roles 
            WHERE user_id = ? AND community_id = ? 
            AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $community_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['role_type'];
    }
    return 'member';
}