<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__ . '/db.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'authorized' => false,
        'message' => 'Please log in first.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK ADMIN ROLE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'logged_in' => true,
        'authorized' => false,
        'message' => 'Administrator access required.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN AUTHORIZED
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'logged_in' => true,
    'authorized' => true,

    'admin' => [
        'user_id' =>
            (int) $_SESSION['user_id'],

        'username' =>
            $_SESSION['username'] ?? 'Admin',

        'email' =>
            $_SESSION['email'] ?? '',

        'role' =>
            $_SESSION['role']
    ]
]);

exit;