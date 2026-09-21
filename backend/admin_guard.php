<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';


/*ADMIN SECURITY GUARD
| This file is INCLUDED by protected admin PHP endpoints.
| It does not output JSON itself.
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


/*ADMIN INFORMATION*/

$admin_id =
    (int) $_SESSION['user_id'];

$admin_username =
    $_SESSION['username'] ?? 'Admin';