<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");


/*CHECK LOGIN*/

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "message" => "Not logged in.",
        "notifications" => []
    ]);

    exit();
}


$user_id =
    (int) $_SESSION['user_id'];


/*GET NOTIFICATIONS*/

$stmt = $conn->prepare(
    "SELECT

        notification_id,
        title,
        message,
        notification_type,
        is_read,
        created_at

     FROM notifications

     WHERE user_id = ?

     ORDER BY created_at DESC"
);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Failed to prepare notifications query.",
        "notifications" => []
    ]);

    exit();
}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$notifications = [];

$unread_count = 0;


/*BUILD NOTIFICATION LIST*/

while ($row = $result->fetch_assoc()) {

    $row['notification_id'] =
        (int) $row['notification_id'];

    $row['is_read'] =
        (int) $row['is_read'];


    if ($row['is_read'] === 0) {
        $unread_count++;
    }


    $notifications[] = $row;
}


$stmt->close();


/*RETURN JSON*/

echo json_encode([
    "success" => true,
    "logged_in" => true,
    "count" => count($notifications),
    "unread_count" => $unread_count,
    "notifications" => $notifications
]);

?>