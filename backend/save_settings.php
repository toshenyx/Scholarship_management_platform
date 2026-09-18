<?php

session_start();
require_once "db.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user_id'])) exit();

$user=$_SESSION['user_id'];

$stmt=$conn->prepare("
INSERT INTO user_settings(
user_id,
two_factor,
allow_notifications,
text_notifications,
deadline_reminders,
email_notifications,
scholarship_recommendations,
application_status,
deadline_notifications,
dark_mode
)

VALUES(?,?,?,?,?,?,?,?,?,?)

ON DUPLICATE KEY UPDATE

two_factor=VALUES(two_factor),
allow_notifications=VALUES(allow_notifications),
text_notifications=VALUES(text_notifications),
deadline_reminders=VALUES(deadline_reminders),
email_notifications=VALUES(email_notifications),
scholarship_recommendations=VALUES(scholarship_recommendations),
application_status=VALUES(application_status),
deadline_notifications=VALUES(deadline_notifications),
dark_mode=VALUES(dark_mode)
");

$stmt->bind_param(
"iiiiiiiiii",
$user,
$_POST['two_factor'],
$_POST['allow_notifications'],
$_POST['text_notifications'],
$_POST['deadline_reminders'],
$_POST['email_notifications'],
$_POST['scholarship_recommendations'],
$_POST['application_status'],
$_POST['deadline_notifications'],
$_POST['dark_mode']
);

$stmt->execute();

echo json_encode(["success"=>true]);