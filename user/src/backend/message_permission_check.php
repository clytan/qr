<?php
require_once('moderator_utils.php');

// Check if user can post in community
function canUserPostInCommunity($user_id, $community_id) {
    $penalties = checkUserPenalties($user_id, $community_id);
    if ($penalties['penalized']) {
        return [
            'can_post' => false,
            'reason' => $penalties['type'] === 'ban' ? 
                'You have been banned from this community.' : 
                'You are in timeout until ' . $penalties['end_time']
        ];
    }
    return ['can_post' => true];
}

// Add this to get_community_messages.php
$postCheck = canUserPostInCommunity($user_id, $community_id);
$canPost = $postCheck['can_post'];

// Add this to the message array:
'can_post' => $canPost,
'penalty_message' => $canPost ? null : $postCheck['reason'];