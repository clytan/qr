<?php
/**
 * Auto-Community Assignment Helper Functions
 * Automatically assigns users to communities (100 users per community)
 */

/**
 * Get or create the appropriate community for a new user
 * Logic: Every 100 users = 1 community
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id The newly created user ID
 * @return array ['status' => bool, 'community_id' => int, 'community_name' => string]
 */
function assignUserToCommunity($conn, $user_id) {
    try {
        $conn->begin_transaction();
        
        // Get total active users (not deleted)
        $sqlCount = "SELECT COUNT(*) as total FROM user_user WHERE is_deleted = 0";
        $resultCount = $conn->query($sqlCount);
        $totalUsers = $resultCount->fetch_assoc()['total'];
        
        // Calculate which community this user should be in
        // Community 1: Users 1-100
        // Community 2: Users 101-200
        // Community 3: Users 201-300, etc.
        $communityNumber = ceil($totalUsers / 100);
        if ($communityNumber < 1) $communityNumber = 1;
        
        $communityName = "Community $communityNumber";
        
        error_log("Auto-assignment: Total users = $totalUsers, Assigning to $communityName");
        
        // Check if community exists
        $sqlCheck = "SELECT id, current_members, is_full FROM community WHERE name = ? AND is_deleted = 0";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('s', $communityName);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        
        if ($resultCheck->num_rows > 0) {
            // Community exists
            $community = $resultCheck->fetch_assoc();
            $community_id = $community['id'];
            $current_members = $community['current_members'];
            
            error_log("Community exists: ID = $community_id, Members = $current_members");
        } else {
            // Create new community
            $sqlCreate = "INSERT INTO community (name, current_members, is_full, created_by, created_on, updated_by, updated_on, is_deleted) 
                         VALUES (?, 0, 0, ?, NOW(3), ?, NOW(3), 0)";
            $stmtCreate = $conn->prepare($sqlCreate);
            $stmtCreate->bind_param('sii', $communityName, $user_id, $user_id);
            
            if (!$stmtCreate->execute()) {
                throw new Exception("Failed to create community: " . $stmtCreate->error);
            }
            
            $community_id = $conn->insert_id;
            $current_members = 0;
            $stmtCreate->close();
            
            error_log("Created new community: ID = $community_id, Name = $communityName");
        }
        $stmtCheck->close();
        
        // Update user with community_id
        $sqlUpdateUser = "UPDATE user_user SET community_id = ? WHERE id = ?";
        $stmtUpdateUser = $conn->prepare($sqlUpdateUser);
        $stmtUpdateUser->bind_param('ii', $community_id, $user_id);
        
        if (!$stmtUpdateUser->execute()) {
            throw new Exception("Failed to update user community: " . $stmtUpdateUser->error);
        }
        $stmtUpdateUser->close();
        
        // Add to community_members table
        $now = date('Y-m-d H:i:s.000');
        $sqlAddMember = "INSERT INTO community_members (community_id, user_id, created_by, created_on, updated_by, updated_on, is_deleted) 
                        VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmtAddMember = $conn->prepare($sqlAddMember);
        $stmtAddMember->bind_param('iisisi', $community_id, $user_id, $user_id, $now, $user_id, $now);
        
        if (!$stmtAddMember->execute()) {
            throw new Exception("Failed to add user to community_members: " . $stmtAddMember->error);
        }
        $stmtAddMember->close();
        
        // Update community member count and check if full
        $new_member_count = $current_members + 1;
        $is_full = ($new_member_count >= 100) ? 1 : 0;
        
        $sqlUpdateCommunity = "UPDATE community SET current_members = ?, is_full = ?, updated_on = NOW(3) WHERE id = ?";
        $stmtUpdateCommunity = $conn->prepare($sqlUpdateCommunity);
        $stmtUpdateCommunity->bind_param('iii', $new_member_count, $is_full, $community_id);
        
        if (!$stmtUpdateCommunity->execute()) {
            throw new Exception("Failed to update community count: " . $stmtUpdateCommunity->error);
        }
        $stmtUpdateCommunity->close();
        
        // Create user role as member
        $sqlRole = "INSERT INTO user_roles (user_id, community_id, role_type, created_on, created_by, is_deleted) 
                   VALUES (?, ?, 'member', NOW(3), ?, 0)";
        $stmtRole = $conn->prepare($sqlRole);
        $stmtRole->bind_param('iii', $user_id, $community_id, $user_id);
        $stmtRole->execute();
        $stmtRole->close();
        
        $conn->commit();
        
        error_log("User $user_id successfully assigned to $communityName (ID: $community_id, Members: $new_member_count/100)");
        
        return [
            'status' => true,
            'community_id' => $community_id,
            'community_name' => $communityName,
            'member_count' => $new_member_count,
            'is_full' => $is_full
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in assignUserToCommunity: " . $e->getMessage());
        return [
            'status' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get community statistics
 * 
 * @param mysqli $conn Database connection
 * @return array Community stats
 */
function getCommunityStats($conn) {
    $sql = "SELECT 
                c.id,
                c.name,
                c.current_members,
                c.is_full,
                COUNT(cm.id) as actual_member_count
            FROM community c
            LEFT JOIN community_members cm ON c.id = cm.community_id AND cm.is_deleted = 0
            WHERE c.is_deleted = 0
            GROUP BY c.id
            ORDER BY c.id ASC";
    
    $result = $conn->query($sql);
    $stats = [];
    
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    
    return $stats;
}

/**
 * Fix existing users - assign them to communities based on their registration order
 * Run this once to migrate existing users
 * 
 * @param mysqli $conn Database connection
 * @return array Result
 */
function migrateExistingUsersToCommunities($conn) {
    try {
        // Get all users without community assignment, ordered by registration date
        $sql = "SELECT id FROM user_user 
                WHERE community_id IS NULL AND is_deleted = 0 
                ORDER BY created_on ASC, id ASC";
        
        $result = $conn->query($sql);
        $migratedCount = 0;
        $errors = [];
        
        while ($row = $result->fetch_assoc()) {
            $assignResult = assignUserToCommunity($conn, $row['id']);
            
            if ($assignResult['status']) {
                $migratedCount++;
            } else {
                $errors[] = "User ID {$row['id']}: " . $assignResult['error'];
            }
        }
        
        return [
            'status' => true,
            'migrated_count' => $migratedCount,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'status' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
