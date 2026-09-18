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
        "message" => "Not logged in.",
        "applications" => []
    ]);

    exit();
}


$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET USER APPLICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT

        a.application_id,
        a.status,
        a.applied_at,
        a.updated_at,
        a.notes,

        s.scholarship_id,
        s.title,
        s.provider,
        s.application_link,
        s.deadline,
        s.country,
        s.university,
        s.funding_type

     FROM applications a

     INNER JOIN scholarships s
        ON a.scholarship_id = s.scholarship_id

     WHERE a.user_id = ?

     ORDER BY
        a.updated_at DESC,
        a.applied_at DESC"
);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" => "Failed to prepare applications query.",
        "applications" => []
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


$applications = [];


/*
|--------------------------------------------------------------------------
| BUILD APPLICATION LIST
|--------------------------------------------------------------------------
*/

while ($row = $result->fetch_assoc()) {

    $row['application_id'] =
        (int) $row['application_id'];

    $row['scholarship_id'] =
        (int) $row['scholarship_id'];

    $applications[] = $row;
}


$stmt->close();


/*
|--------------------------------------------------------------------------
| RETURN JSON
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "logged_in" => true,
    "count" => count($applications),
    "applications" => $applications
]);

?>