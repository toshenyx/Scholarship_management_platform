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
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {
    sendResponse([
        'success' => false,
        'logged_in' => false,
        'message' => 'Please log in before saving scholarships.'
    ], 401);
}


$user_id =
    (int) $_SESSION['user_id'];


/* =========================================================
   READ REQUEST
   Supports BOTH JSON and normal POST
========================================================= */

$input =
    json_decode(
        file_get_contents('php://input'),
        true
    );


$scholarship_id = 0;


if (
    is_array($input) &&
    isset($input['scholarship_id'])
) {
    $scholarship_id =
        (int) $input['scholarship_id'];
}
elseif (
    isset($_POST['scholarship_id'])
) {
    $scholarship_id =
        (int) $_POST['scholarship_id'];
}


if ($scholarship_id <= 0) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Invalid scholarship ID.'
    ], 400);
}


/* =========================================================
   CHECK SCHOLARSHIP EXISTS
========================================================= */

$checkScholarship =
    $conn->prepare("
        SELECT scholarship_id
        FROM scholarships
        WHERE scholarship_id = ?
        LIMIT 1
    ");


if (!$checkScholarship) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Unable to check scholarship.'
    ], 500);
}


$checkScholarship->bind_param(
    'i',
    $scholarship_id
);

$checkScholarship->execute();

$scholarshipResult =
    $checkScholarship->get_result();


if (
    $scholarshipResult->num_rows === 0
) {

    $checkScholarship->close();

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Scholarship does not exist.'
    ], 404);
}


$checkScholarship->close();


/* =========================================================
   CHECK IF ALREADY SAVED
========================================================= */

$checkSaved =
    $conn->prepare("
        SELECT saved_id
        FROM saved_scholarships
        WHERE user_id = ?
        AND scholarship_id = ?
        LIMIT 1
    ");


if (!$checkSaved) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Unable to check saved scholarship.'
    ], 500);
}


$checkSaved->bind_param(
    'ii',
    $user_id,
    $scholarship_id
);

$checkSaved->execute();

$savedResult =
    $checkSaved->get_result();


if (
    $savedResult->num_rows > 0
) {

    $checkSaved->close();

    sendResponse([
        'success' => true,
        'logged_in' => true,
        'already_saved' => true,
        'scholarship_id' => $scholarship_id,
        'message' => 'Scholarship is already saved.'
    ]);
}


$checkSaved->close();


/* =========================================================
   SAVE SCHOLARSHIP
========================================================= */

$save =
    $conn->prepare("
        INSERT INTO saved_scholarships
        (
            user_id,
            scholarship_id,
            saved_at
        )
        VALUES (?, ?, NOW())
    ");


if (!$save) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to prepare save request: ' .
            $conn->error
    ], 500);
}


$save->bind_param(
    'ii',
    $user_id,
    $scholarship_id
);


if (!$save->execute()) {

    $error =
        $save->error;

    $save->close();

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to save scholarship: ' .
            $error
    ], 500);
}


$saved_id =
    $save->insert_id;


$save->close();


/* =========================================================
   SUCCESS
========================================================= */

sendResponse([
    'success' => true,
    'logged_in' => true,
    'already_saved' => false,
    'saved_id' => $saved_id,
    'scholarship_id' => $scholarship_id,
    'message' => 'Scholarship saved successfully.'
]);

?>