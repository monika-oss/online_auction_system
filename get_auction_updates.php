<?php
require 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$auctionIds = $input['auctionIds'] ?? [];

$counts = [];
foreach ($auctionIds as $id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bids WHERE auction_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    $counts[] = ['id' => $id, 'count' => $count];
}

echo json_encode($counts);
$conn->close();
?>