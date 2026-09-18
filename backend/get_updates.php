<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';


function sendResponse($data, $status = 200)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   LOGIN
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {

    sendResponse([
        'success' => false,
        'logged_in' => false,
        'message' => 'Please log in to view updates.'
    ], 401);
}


$user_id =
    (int) $_SESSION['user_id'];


/* =========================================================
   APPLICATION UPDATES
========================================================= */

$applicationSql = "

    SELECT

        a.application_id,
        a.scholarship_id,
        a.status,
        a.applied_at,
        a.updated_at,
        a.notes,

        s.title AS scholarship_title,
        s.provider,
        s.country,
        s.university,
        s.deadline

    FROM applications a

    INNER JOIN scholarships s
        ON s.scholarship_id =
           a.scholarship_id

    WHERE a.user_id = ?

    ORDER BY
        a.updated_at DESC,
        a.application_id DESC

";


$applicationStmt =
    $conn->prepare(
        $applicationSql
    );


if (!$applicationStmt) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to prepare application updates: ' .
            $conn->error
    ], 500);
}


$applicationStmt->bind_param(
    'i',
    $user_id
);


$applicationStmt->execute();


$applicationResult =
    $applicationStmt->get_result();


$applications = [];


while (
    $application =
        $applicationResult->fetch_assoc()
) {

    $applications[] =
        $application;
}


$applicationStmt->close();


/* =========================================================
   NOTIFICATIONS
========================================================= */

$notificationSql = "

    SELECT

        notification_id,
        title,
        message,
        is_read,
        created_at

    FROM notifications

    WHERE user_id = ?

    ORDER BY
        created_at DESC,
        notification_id DESC

";


$notificationStmt =
    $conn->prepare(
        $notificationSql
    );


if (!$notificationStmt) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to prepare notifications: ' .
            $conn->error
    ], 500);
}


$notificationStmt->bind_param(
    'i',
    $user_id
);


$notificationStmt->execute();


$notificationResult =
    $notificationStmt->get_result();


$notifications = [];

$unread_count = 0;


while (
    $notification =
        $notificationResult->fetch_assoc()
) {

    if (
        (int) $notification['is_read']
        === 0
    ) {

        $unread_count++;
    }


    $notifications[] =
        $notification;
}


$notificationStmt->close();


/* =========================================================
   RESPONSE
========================================================= */

sendResponse([
    'success' => true,
    'logged_in' => true,

    'applications' =>
        $applications,

    'notifications' =>
        $notifications,

    'unread_count' =>
        $unread_count,

    'application_count' =>
        count($applications),

    'notification_count' =>
        count($notifications),

    'message' =>
        'Updates loaded successfully.'
]);

?>