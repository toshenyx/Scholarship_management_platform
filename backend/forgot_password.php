<?php

require_once "db.php";

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| READ INPUT
|--------------------------------------------------------------------------
*/

$identifier = trim($_POST['identifier'] ?? '');

$new_password = $_POST['new_password'] ?? '';

$confirm_password = $_POST['confirm_password'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $identifier === '' ||
    $new_password === '' ||
    $confirm_password === ''
) {

    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all fields.'
    ]);

    exit;
}


if ($new_password !== $confirm_password) {

    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);

    exit;
}


if (strlen($new_password) < 8) {

    echo json_encode([
        'success' => false,
        'message' => 'Password must contain at least 8 characters.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| FIND THE ACCOUNT
|--------------------------------------------------------------------------
| Users can identify themselves with either their username or email.
*/

$stmt = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE username = ? OR email = ?
     LIMIT 1"
);

$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    echo json_encode([
        'success' => false,
        'message' => 'No account found with that username or email.'
    ]);

    exit;
}

$user = $result->fetch_assoc();

$user_id = (int) $user['user_id'];


/*
|--------------------------------------------------------------------------
| UPDATE THE PASSWORD
|--------------------------------------------------------------------------
*/

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

$update = $conn->prepare(
    "UPDATE users
     SET password_hash = ?
     WHERE user_id = ?"
);

$update->bind_param("si", $new_hash, $user_id);

if (!$update->execute()) {

    echo json_encode([
        'success' => false,
        'message' => 'Could not reset password. Please try again.'
    ]);

    exit;
}


echo json_encode([
    'success' => true,
    'message' => 'Your password has been reset successfully. You can now sign in.'
]);
