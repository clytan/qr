<?php
/**
 * Cron Job - Process All Reward Draws
 * Iterates through all active communities and executes draws if due.
 * No session required. Intended to be run via server cron or manual trigger.
 */

header('Content-Type: application/json');

// Adjust path as needed depending on where this file is placed relative to dbconfig
require_once __DIR__ . '/../dbconfig/connection.php';

// Disable time limit for batch processing
set_time_limit(0);
ignore_user_abort(true);

$response = [
    'status' => true,
    'processed_at' => date('Y-m-d H:i:s'),
    'summary' => [],
    'details' => []
];

try {
    $today = date('Y-m-d');
    $now = new DateTime();
    $nowFormatted = $now->format('Y-m-d H:i:s.000');

    // 1. Get Global Configuration
    $sqlConfig = "SELECT config_key, config_value FROM reward_config";
    $resultConfig = $conn->query($sqlConfig);
    
    $config = [];
    if ($resultConfig) {
        while ($row = $resultConfig->fetch_assoc()) {
            $config[$row['config_key']] = $row['config_value'];
        }
    }

    $spinEndTime = isset($config['spin_end_time']) ? $config['spin_end_time'] : '21:00:00';
    $maxWinners = isset($config['max_winners_per_draw']) ? (int)$config['max_winners_per_draw'] : 30;

    // Check if it's time to run (Current time >= Spin End Time)
    // Note: You might want to allow forcing the run via ?force=true
    $forceRun = isset($_GET['force']) && $_GET['force'] === 'true';
    $endDateTime = new DateTime($today . ' ' . $spinEndTime);

    if ($now < $endDateTime && !$forceRun) {
        echo json_encode([
            'status' => false,
            'message' => 'Too early to run draws. Configured end time: ' . $spinEndTime,
            'current_time' => $now->format('H:i:s')
        ]);
        exit;
    }

    // 2. Get All Active Communities
    // We only care about communities that have members
    $sqlCommunities = "SELECT DISTINCT community_id FROM community_members WHERE is_deleted = 0";
    $resultCommunities = $conn->query($sqlCommunities);
    
    $communitiesFound = 0;
    $drawsCreated = 0;
    $drawsSkipped = 0;
    $errors = 0;

    if ($resultCommunities) {
        while ($commRow = $resultCommunities->fetch_assoc()) {
            $communitiesFound++;
            $communityId = $commRow['community_id'];
            
            // 3. Process Each Community
            try {
                // Check if draw already completed for today
                $sqlCheck = "SELECT id, is_completed FROM reward_draws WHERE community_id = ? AND draw_date = ?";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bind_param('is', $communityId, $today);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                
                if ($resCheck->num_rows > 0 && $resCheck->fetch_assoc()['is_completed']) {
                    $drawsSkipped++;
                    $response['details'][] = "Community $communityId: Skipped (Already completed)";
                    continue;
                }
                
                // --- DRAW LOGIC START ---
                
                // Get eligible members
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
                $stmtMembers->bind_param('ii', $communityId, $communityId);
                $stmtMembers->execute();
                $resMembers = $stmtMembers->get_result();
                
                $members = [];
                $totalWeight = 0;
                
                while ($mRow = $resMembers->fetch_assoc()) {
                    $weight = 1.0 / (1 + (int)$mRow['past_wins']);
                    $members[] = [
                        'user_id' => $mRow['user_id'],
                        'weight' => $weight,
                        'name' => $mRow['user_full_name']
                    ];
                    $totalWeight += $weight;
                }
                
                $totalMembers = count($members);
                
                if ($totalMembers === 0) {
                    $drawsSkipped++;
                    $response['details'][] = "Community $communityId: Skipped (No members)";
                    continue;
                }
                
                $numWinners = min($maxWinners, $totalMembers);
                
                // Start Transaction for this community
                $conn->begin_transaction();
                
                // Insert/Get Draw Record
                if ($resCheck->num_rows > 0) {
                    // It existed but wasn't completed (rare edge case or retry)
                    $resCheck->data_seek(0);
                    $drawId = $resCheck->fetch_assoc()['id'];
                } else {
                    $sqlCreate = "INSERT INTO reward_draws (community_id, draw_date, total_participants, total_winners, is_completed, created_on, updated_on) VALUES (?, ?, ?, 0, 0, ?, ?)";
                    $stmtCreate = $conn->prepare($sqlCreate);
                    $stmtCreate->bind_param('isiss', $communityId, $today, $totalMembers, $nowFormatted, $nowFormatted);
                    $stmtCreate->execute();
                    $drawId = $conn->insert_id;
                }
                
                // Pick Winners
                $remainingMembers = $members;
                $remainingWeight = $totalWeight;
                $winnersCount = 0;
                
                for ($i = 0; $i < $numWinners && count($remainingMembers) > 0; $i++) {
                    $random = mt_rand() / mt_getrandmax() * $remainingWeight;
                    $cumulativeWeight = 0;
                    $selectedIndex = 0;
                    
                    foreach ($remainingMembers as $idx => $mem) {
                        $cumulativeWeight += $mem['weight'];
                        if ($random <= $cumulativeWeight) {
                            $selectedIndex = $idx;
                            break;
                        }
                    }
                    
                    $winner = $remainingMembers[$selectedIndex];
                    $position = $i + 1;
                    
                    $sqlInsertWin = "INSERT INTO reward_winners (draw_id, user_id, community_id, position, won_at, created_on) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtInsertWin = $conn->prepare($sqlInsertWin);
                    $stmtInsertWin->bind_param('iiiiss', $drawId, $winner['user_id'], $communityId, $position, $nowFormatted, $nowFormatted);
                    $stmtInsertWin->execute();
                    
                    $remainingWeight -= $winner['weight'];
                    array_splice($remainingMembers, $selectedIndex, 1);
                    $winnersCount++;
                }
                
                // Mark Completed
                $sqlComplete = "UPDATE reward_draws SET is_completed = 1, total_winners = ?, updated_on = ? WHERE id = ?";
                $stmtComplete = $conn->prepare($sqlComplete);
                $stmtComplete->bind_param('isi', $winnersCount, $nowFormatted, $drawId);
                $stmtComplete->execute();
                
                $conn->commit(); // Commit for this community
                
                $drawsCreated++;
                $response['details'][] = "Community $communityId: Success ($winnersCount winners)";
                
                // --- DRAW LOGIC END ---
                
            } catch (Exception $eComm) {
                $conn->rollback();
                $errors++;
                $response['details'][] = "Community $communityId: Error - " . $eComm->getMessage();
            }
        }
    }
    
    $response['summary'] = [
        'communities_found' => $communitiesFound,
        'draws_created' => $drawsCreated,
        'draws_skipped' => $drawsSkipped,
        'errors' => $errors
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
