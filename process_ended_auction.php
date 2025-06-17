<?php
require 'db.php';

// Find auctions that have ended but haven't been processed
$query = "SELECT a.id, a.user_id as seller_id, a.current_bid, a.item_name, 
                 u.email as seller_email, u.username as seller_name
          FROM auctions a
          JOIN users u ON a.user_id = u.id
          WHERE a.status = 'active' 
          AND a.end_date <= NOW()";
$result = $conn->query($query);

while ($auction = $result->fetch_assoc()) {
    // Find the highest bid
    $bid_query = "SELECT b.user_id, b.bid_amount, u.email, u.username
                  FROM bids b
                  JOIN users u ON b.user_id = u.id
                  WHERE b.auction_id = ? 
                  ORDER BY b.bid_amount DESC 
                  LIMIT 1";
    $stmt = $conn->prepare($bid_query);
    $stmt->bind_param("i", $auction['id']);
    $stmt->execute();
    $bid_result = $stmt->get_result();
    
    if ($bid_result->num_rows > 0) {
        $winning_bid = $bid_result->fetch_assoc();
        
        // Update auction with winner info
        $update_auction = "UPDATE auctions 
                          SET status = 'ended', 
                              winner_id = ?, 
                              current_bid = ?,
                              payment_status = 'pending'
                          WHERE id = ?";
        $stmt = $conn->prepare($update_auction);
        $stmt->bind_param("idi", $winning_bid['user_id'], $winning_bid['bid_amount'], $auction['id']);
        $stmt->execute();
        
        // Mark bid as winning
        $update_bid = "UPDATE bids 
                      SET is_winning = TRUE 
                      WHERE auction_id = ? 
                      AND user_id = ? 
                      AND bid_amount = ?";
        $stmt = $conn->prepare($update_bid);
        $stmt->bind_param("iid", $auction['id'], $winning_bid['user_id'], $winning_bid['bid_amount']);
        $stmt->execute();
        
        // Send email to winner
        $to_winner = $winning_bid['email'];
        $subject = "You Won the Auction for {$auction['item_name']}!";
        $message = "Dear {$winning_bid['username']},\n\n"
                 . "Congratulations! You won the auction for \"{$auction['item_name']}\" "
                 . "with a winning bid of Rs. {$winning_bid['bid_amount']}.\n\n"
                 . "Please complete your payment within 48 hours at:\n"
                 . "http://yourauctionsite.com/payment.php?auction_id={$auction['id']}\n\n"
                 . "Thank you for using our service!\n"
                 . "The Auction Team";
        $headers = "From: auctions@yourauctionsite.com";
        
        mail($to_winner, $subject, $message, $headers);
        
        // Send email to seller
        $to_seller = $auction['seller_email'];
        $subject = "Your Auction for {$auction['item_name']} Has Ended";
        $message = "Dear {$auction['seller_name']},\n\n"
                 . "Your auction for \"{$auction['item_name']}\" has ended with a winning bid "
                 . "of Rs. {$winning_bid['bid_amount']} by {$winning_bid['username']}.\n\n"
                 . "You will be notified once the payment is completed.\n\n"
                 . "Thank you for using our service!\n"
                 . "The Auction Team";
        
        mail($to_seller, $subject, $message, $headers);
    } else {
        // No bids were placed - update auction status
        $update_auction = "UPDATE auctions SET status = 'ended' WHERE id = ?";
        $stmt = $conn->prepare($update_auction);
        $stmt->bind_param("i", $auction['id']);
        $stmt->execute();
        
        // Notify seller
        $to_seller = $auction['seller_email'];
        $subject = "Your Auction for {$auction['item_name']} Has Ended With No Bids";
        $message = "Dear {$auction['seller_name']},\n\n"
                 . "Your auction for \"{$auction['item_name']}\" has ended with no bids placed.\n\n"
                 . "You may want to relist the item with a lower starting bid or better description.\n\n"
                 . "Thank you for using our service!\n"
                 . "The Auction Team";
        
        mail($to_seller, $subject, $message, $headers);
    }
}

$conn->close();
?>