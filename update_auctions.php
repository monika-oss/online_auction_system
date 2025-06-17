<?php
// update_auctions.php
require 'db.php';

// Find auctions where end_date has passed but status is still active
$current_time = date('Y-m-d H:i:s');
$query = "SELECT id FROM auctions WHERE end_date <= ? AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $current_time);
$stmt->execute();
$result = $stmt->get_result();

while ($auction = $result->fetch_assoc()) {
    // Determine winner for each auction
    $winner_query = "SELECT user_id, MAX(bid_amount) as max_bid 
                     FROM bids 
                     WHERE auction_id = ? 
                     GROUP BY auction_id";
    $stmt_winner = $conn->prepare($winner_query);
    $stmt_winner->bind_param("i", $auction['id']);
    $stmt_winner->execute();
    $winner_result = $stmt_winner->get_result();

    if ($winner_result->num_rows > 0) {
        $winner = $winner_result->fetch_assoc();
        $update_query = "UPDATE auctions 
                         SET status = 'ended', 
                             winner_id = ?,
                             current_bid = ?,
                             payment_status = 'pending'
                         WHERE id = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("idi", $winner['user_id'], $winner['max_bid'], $auction['id']);
        $stmt_update->execute();
    } else {
        // No bids, just mark as ended
        $update_query = "UPDATE auctions SET status = 'ended' WHERE id = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("i", $auction['id']);
        $stmt_update->execute();
    }
}

$conn->close();
echo "Auctions updated successfully.";
?>z