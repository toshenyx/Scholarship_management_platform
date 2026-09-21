<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once 'db.php';


function sendRemoveResponse(
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


/* LOGIN CHECK*/

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {

    sendRemoveResponse([
        'success' => false,
        'logged_in' => false,
        'message' =>
            'Please log in first.'
    ], 401);
}


$user_id =
    (int) $_SESSION['user_id'];


/* READ JSON REQUEST*/

$input =
    json_decode(
        file_get_contents(
            'php://input'
        ),
        true
    );


$scholarship_id =
    isset($input['scholarship_id'])
        ? (int) $input['scholarship_id']
        : 0;


/* VALIDATION*/

if ($scholarship_id <= 0) {

    sendRemoveResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Invalid scholarship ID.'
    ], 400);
}


/* DELETE FROM DATABASE */

$stmt =
    $conn->prepare("

        DELETE FROM saved_scholarships

        WHERE user_id = ?

        AND scholarship_id = ?

    ");


if (!$stmt) {

    sendRemoveResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Delete query failed: ' .
            $conn->error
    ], 500);
}


$stmt->bind_param(
    'ii',
    $user_id,
    $scholarship_id
);


if (!$stmt->execute()) {

    sendRemoveResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to remove scholarship: ' .
            $stmt->error
    ], 500);
}


$removed =
    $stmt->affected_rows > 0;


$stmt->close();


sendRemoveResponse([

    'success' => true,

    'logged_in' => true,

    'removed' => $removed,

    'scholarship_id' =>
        $scholarship_id,

    'message' =>
        $removed
            ? 'Scholarship removed successfully.'
            : 'Scholarship was not found in your saved list.'

]);

?>