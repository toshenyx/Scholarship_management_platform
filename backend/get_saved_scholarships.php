<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once 'db.php';


/* =========================================================
   RESPONSE
========================================================= */

function sendSavedResponse(
    $data,
    $status = 200
) {

    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {

    sendSavedResponse([
        'success' => false,
        'logged_in' => false,
        'message' =>
            'Please log in first.'
    ], 401);
}


$user_id =
    (int) $_SESSION['user_id'];


/* =========================================================
   GET CURRENT USER
========================================================= */

$userStmt =
    $conn->prepare("

        SELECT
            user_id,
            username,
            email,
            role

        FROM users

        WHERE user_id = ?

        LIMIT 1

    ");


if (!$userStmt) {

    sendSavedResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'User query failed: ' .
            $conn->error
    ], 500);
}


$userStmt->bind_param(
    'i',
    $user_id
);


$userStmt->execute();


$user =
    $userStmt
        ->get_result()
        ->fetch_assoc();


$userStmt->close();


if (!$user) {

    sendSavedResponse([
        'success' => false,
        'logged_in' => false,
        'message' =>
            'Logged-in user was not found.'
    ], 404);
}


/* =========================================================
   GET ONLY THIS USER'S SAVED SCHOLARSHIPS
========================================================= */

$stmt =
    $conn->prepare("

        SELECT

            ss.saved_id,
            ss.saved_at,

            s.scholarship_id,
            s.title,
            s.provider,
            s.application_link,
            s.description,
            s.education_level,
            s.eligible_courses,
            s.minimum_gpa,
            s.eligible_nationalities,
            s.age_limit,
            s.ielts_required,
            s.funding_type,
            s.country,
            s.university,
            s.duration,
            s.deadline,
            s.verification_status

        FROM saved_scholarships ss

        INNER JOIN scholarships s
            ON s.scholarship_id =
               ss.scholarship_id

        WHERE ss.user_id = ?

        ORDER BY ss.saved_at DESC

    ");


if (!$stmt) {

    sendSavedResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Saved scholarship query failed: ' .
            $conn->error
    ], 500);
}


$stmt->bind_param(
    'i',
    $user_id
);


if (!$stmt->execute()) {

    sendSavedResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to retrieve saved scholarships: ' .
            $stmt->error
    ], 500);
}


$result =
    $stmt->get_result();


$saved_scholarships = [];


while (
    $row =
        $result->fetch_assoc()
) {

    $saved_scholarships[] =
        $row;
}


$stmt->close();


/* =========================================================
   RESPONSE
========================================================= */

sendSavedResponse([

    'success' => true,

    'logged_in' => true,

    'user' => [

        'user_id' =>
            (int) $user['user_id'],

        'username' =>
            $user['username'],

        'email' =>
            $user['email'],

        'role' =>
            $user['role']

    ],

    'count' =>
        count($saved_scholarships),

    'saved_scholarships' =>
        $saved_scholarships

]);

?>