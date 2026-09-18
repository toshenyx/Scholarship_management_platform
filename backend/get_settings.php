<?php

session_start();
require_once "db.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["success"=>false]);
    exit();
}

$user=$_SESSION['user_id'];

$stmt=$conn->prepare("
SELECT
u.username,
u.role,
COALESCE(s.two_factor,1) two_factor,
COALESCE(s.allow_notifications,1) allow_notifications,
COALESCE(s.text_notifications,0) text_notifications,
COALESCE(s.deadline_reminders,1) deadline_reminders,
COALESCE(s.email_notifications,1) email_notifications,
COALESCE(s.scholarship_recommendations,1) scholarship_recommendations,
COALESCE(s.application_status,1) application_status,
COALESCE(s.deadline_notifications,1) deadline_notifications,
COALESCE(s.dark_mode,0) dark_mode

FROM users u

LEFT JOIN user_settings s
ON u.user_id=s.user_id

WHERE u.user_id=?
");

$stmt->bind_param("i",$user);
$stmt->execute();

echo json_encode([
"success"=>true,
"data"=>$stmt->get_result()->fetch_assoc()
]);