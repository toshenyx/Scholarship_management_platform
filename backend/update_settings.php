<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Not logged in."
    ]);

    exit();
}

$user_id = $_SESSION['user_id'];

$notifications_enabled =
    isset($_POST['notifications_enabled'])
    ? (int)$_POST['notifications_enabled']
    : 1;

$text_notifications =
    isset($_POST['text_notifications'])
    ? (int)$_POST['text_notifications']
    : 0;

$deadline_reminders =
    isset($_POST['deadline_reminders'])
    ? (int)$_POST['deadline_reminders']
    : 1;

$email_notifications =
    isset($_POST['email_notifications'])
    ? (int)$_POST['email_notifications']
    : 1;

$scholarship_recommendations =
    isset($_POST['scholarship_recommendations'])
    ? (int)$_POST['scholarship_recommendations']
    : 1;

$application_status_reminders =
    isset($_POST['application_status_reminders'])
    ? (int)$_POST['application_status_reminders']
    : 1;

$deadline_notifications =
    isset($_POST['deadline_notifications'])
    ? (int)$_POST['deadline_notifications']
    : 1;


$stmt = $conn->prepare(
    "INSERT INTO notification_settings
    (
        user_id,
        notifications_enabled,
        text_notifications,
        deadline_reminders,
        email_notifications,
        scholarship_recommendations,
        application_status_reminders,
        deadline_notifications
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)

    ON DUPLICATE KEY UPDATE
        notifications_enabled = VALUES(notifications_enabled),
        text_notifications = VALUES(text_notifications),
        deadline_reminders = VALUES(deadline_reminders),
        email_notifications = VALUES(email_notifications),
        scholarship_recommendations = VALUES(scholarship_recommendations),
        application_status_reminders = VALUES(application_status_reminders),
        deadline_notifications = VALUES(deadline_notifications)"
);

$stmt->bind_param(
    "iiiiiiii",
    $user_id,
    $notifications_enabled,
    $text_notifications,
    $deadline_reminders,
    $email_notifications,
    $scholarship_recommendations,
    $application_status_reminders,
    $deadline_notifications
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Settings updated."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not update settings."
    ]);
}

?>