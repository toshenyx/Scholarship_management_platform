<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "message" => "Not logged in."
    ]);

    exit();
}


$user_id =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Invalid request method."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| READ JSON BODY
|--------------------------------------------------------------------------
|
| Updates page sends:
|
| {
|     "notification_id": 5
| }
|
*/

$input =
    json_decode(
        file_get_contents("php://input"),
        true
    );


$notification_id =
    isset($input['notification_id'])
        ? (int) $input['notification_id']
        : 0;


/*
|--------------------------------------------------------------------------
| VALIDATE NOTIFICATION ID
|--------------------------------------------------------------------------
*/

if ($notification_id <= 0) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Invalid notification ID."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| CHECK THAT NOTIFICATION BELONGS TO USER
|--------------------------------------------------------------------------
*/

$check_stmt =
    $conn->prepare(
        "SELECT notification_id

         FROM notifications

         WHERE notification_id = ?
         AND user_id = ?

         LIMIT 1"
    );


if (!$check_stmt) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Unable to check notification."
    ]);

    exit();
}


$check_stmt->bind_param(
    "ii",
    $notification_id,
    $user_id
);


$check_stmt->execute();


$check_result =
    $check_stmt->get_result();


if (
    $check_result->num_rows === 0
) {

    $check_stmt->close();


    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Notification not found."
    ]);

    exit();
}


$check_stmt->close();


/*
|--------------------------------------------------------------------------
| MARK NOTIFICATION AS READ
|--------------------------------------------------------------------------
*/

$stmt =
    $conn->prepare(
        "UPDATE notifications

         SET is_read = 1

         WHERE notification_id = ?
         AND user_id = ?"
    );


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Unable to prepare notification update."
    ]);

    exit();
}


$stmt->bind_param(
    "ii",
    $notification_id,
    $user_id
);


if (!$stmt->execute()) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Failed to mark notification as read."
    ]);

    $stmt->close();

    exit();
}


$stmt->close();


/*
|--------------------------------------------------------------------------
| GET NEW UNREAD COUNT
|--------------------------------------------------------------------------
*/

$count_stmt =
    $conn->prepare(
        "SELECT COUNT(*) AS unread_count

         FROM notifications

         WHERE user_id = ?
         AND is_read = 0"
    );


$unread_count = 0;


if ($count_stmt) {

    $count_stmt->bind_param(
        "i",
        $user_id
    );


    $count_stmt->execute();


    $count_result =
        $count_stmt->get_result();


    if (
        $count_row =
            $count_result->fetch_assoc()
    ) {

        $unread_count =
            (int) $count_row['unread_count'];
    }


    $count_stmt->close();
}


/*
|--------------------------------------------------------------------------
| RETURN SUCCESS
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "logged_in" => true,
    "message" => "Notification marked as read.",
    "notification_id" => $notification_id,
    "unread_count" => $unread_count
]);

?>