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

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (
    empty($current_password) ||
    empty($new_password) ||
    empty($confirm_password)
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all password fields."
    ]);

    exit();
}

if ($new_password !== $confirm_password) {

    echo json_encode([
        "success" => false,
        "message" => "New passwords do not match."
    ]);

    exit();
}

if (strlen($new_password) < 8) {

    echo json_encode([
        "success" => false,
        "message" => "Password must contain at least 8 characters."
    ]);

    exit();
}


// Get current password
$stmt = $conn->prepare(
    "SELECT password_hash
     FROM users
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();


// Verify current password
if (
    !$user ||
    !password_verify(
        $current_password,
        $user['password_hash']
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "Current password is incorrect."
    ]);

    exit();
}


// Hash new password
$new_hash = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);


$stmt = $conn->prepare(
    "UPDATE users
     SET password_hash = ?
     WHERE user_id = ?"
);

$stmt->bind_param(
    "si",
    $new_hash,
    $user_id
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Password changed successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to change password."
    ]);
}

?>
