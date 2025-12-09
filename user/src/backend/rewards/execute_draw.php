<?php
/**
 * Execute Draw - Select Winners with Fair Weighted Probability
 * This should be called when the spin timer ends
 * Uses weighted random selection: users who won more times have lower probability
 */

header('Content-Type: application/json');
require_once '../dbconfig/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'error' => 'Not logged in', 'login_required' => true]);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s.000');

try {
    // Get user's community
    $sqlUser = "SELECT community_id FROM user_user WHERE id = ? AND is_deleted = 0";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    
    if ($resultUser->num_rows === 0) {
        echo json_encode(['status' => false, 'error' => 'User not found']);
        exit;
    }
    
    $userData = $resultUser->fetch_assoc();
    $community_id = $userData['community_id'];
    
    if (!$community_id) {
        echo json_encode(['status' => false, 'error' => 'User not assigned to any community']);
        exit;
    }
    
    // Check if draw already exists and is completed
    $sqlCheckDraw = "SELECT id, is_completed FROM reward_draws WHERE community_id = ? AND draw_date = ?";
    $stmtCheck = $conn->prepare($sqlCheckDraw);
    $stmtCheck->bind_param('is', $community_id, $today);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        $existingDraw = $resultCheck->fetch_assoc();
        if ($existingDraw['is_completed']) {
            // Draw already done, redirect to get results
            echo json_encode([
                'status' => true,
                'already_completed' => true,
                'draw_id' => $existingDraw['id'],
                'message' => 'Draw already completed for today'
            ]);
            exit;
        }
    }
    
    // Get max winners config
    $sqlConfig = "SELECT config_value FROM reward_config WHERE config_key = 'max_winners_per_draw'";
    $resultConfig = $conn->query($sqlConfig);
    $maxWinners = 30; // default
    if ($resultConfig && $resultConfig->num_rows > 0) {
        $maxWinners = (int)$resultConfig->fetch_assoc()['config_value'];
    }
    
    // Get all eligible community members with their past win counts
    // Weight formula: weight = 1 / (1 + past_wins)
    // This gives: 0 wins = weight 1, 1 win = weight 0.5, 2 wins = weight 0.33, etc.
    $sqlMembers = "SELECT 
                        cm.user_id,
                        u.user_qr_id,
                        u.user_full_name,
                        COALESCE(win_counts.total_wins, 0) as past_wins
                    FROM community_members cm
                    JOIN user_user u ON cm.user_id = u.id AND u.is_deleted = 0
                    LEFT JOIN (
                        SELECT user_id, COUNT(*) as total_wins 
                        FROM reward_winners 
                        WHERE community_id = ?
                        GROUP BY user_id
                    ) win_counts ON cm.user_id = win_counts.user_id
                    WHERE cm.community_id = ? 
                    AND cm.is_deleted = 0
                    ORDER BY u.id ASC";
    
    $stmtMembers = $conn->prepare($sqlMembers);
    $stmtMembers->bind_param('ii', $community_id, $community_id);
    $stmtMembers->execute();
    $resultMembers = $stmtMembers->get_result();
    
    $members = [];
    $totalWeight = 0;
    
    while ($row = $resultMembers->fetch_assoc()) {
        $weight = 1.0 / (1 + (int)$row['past_wins']);
        $members[] = [
            'user_id' => $row['user_id'],
            'user_qr_id' => $row['user_qr_id'],
            'user_full_name' => $row['user_full_name'],
            'past_wins' => $row['past_wins'],
            'weight' => $weight
        ];
        $totalWeight += $weight;
    }
    
    $totalMembers = count($members);
    
    if ($totalMembers === 0) {
        echo json_encode(['status' => false, 'error' => 'No members in community']);
        exit;
    }
    
    // Determine number of winners (min of maxWinners or total members)
    $numWinners = min($maxWinners, $totalMembers);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create or update draw record
        if ($resultCheck->num_rows > 0) {
            $draw_id = $existingDraw['id'];
        } else {
            $sqlCreateDraw = "INSERT INTO reward_draws (community_id, draw_date, total_participants, total_winners, is_completed, created_on, updated_on)
                              VALUES (?, ?, ?, 0, 0, ?, ?)";
            $stmtCreateDraw = $conn->prepare($sqlCreateDraw);
            $stmtCreateDraw->bind_param('isiss', $community_id, $today, $totalMembers, $now, $now);
            $stmtCreateDraw->execute();
            $draw_id = $conn->insert_id;
        }
        
        // Weighted random selection without replacement
        $winners = [];
        $remainingMembers = $members;
        $remainingWeight = $totalWeight;
        
        for ($i = 0; $i < $numWinners && count($remainingMembers) > 0; $i++) {
            // Generate random value between 0 and remaining total weight
            $random = mt_rand() / mt_getrandmax() * $remainingWeight;
            
            $cumulativeWeight = 0;
            $selectedIndex = 0;
            
            foreach ($remainingMembers as $index => $member) {
                $cumulativeWeight += $member['weight'];
                if ($random <= $cumulativeWeight) {
                    $selectedIndex = $index;
                    break;
                }
            }
            
            // Add selected member to winners
            $winner = $remainingMembers[$selectedIndex];
            $position = $i + 1;
            
            // Insert winner record
            $sqlInsertWinner = "INSERT INTO reward_winners (draw_id, user_id, community_id, position, won_at, created_on)
                                VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsertWinner = $conn->prepare($sqlInsertWinner);
            $stmtInsertWinner->bind_param('iiiiss', $draw_id, $winner['user_id'], $community_id, $position, $now, $now);
            $stmtInsertWinner->execute();
            
            $winners[] = [
                'position' => $position,
                'user_id' => $winner['user_id'],
                'user_qr_id' => $winner['user_qr_id'],
                'user_full_name' => $winner['user_full_name']
            ];
            
            // Remove winner from pool and adjust weight
            $remainingWeight -= $winner['weight'];
            array_splice($remainingMembers, $selectedIndex, 1);
        }
        
        // Mark draw as completed
        $sqlCompleteDraw = "UPDATE reward_draws SET is_completed = 1, total_winners = ?, updated_on = ? WHERE id = ?";
        $stmtComplete = $conn->prepare($sqlCompleteDraw);
        $stmtComplete->bind_param('isi', $numWinners, $now, $draw_id);
        $stmtComplete->execute();
        
        $conn->commit();
        
        echo json_encode([
            'status' => true,
            'draw_id' => $draw_id,
            'total_participants' => $totalMembers,
            'total_winners' => count($winners),
            'winners' => $winners,
            'message' => 'Draw completed successfully!'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in execute_draw: " . $e->getMessage());
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
