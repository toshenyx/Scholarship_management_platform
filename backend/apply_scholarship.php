<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please log in first."
    ]);

    exit();
}

$user_id = $_SESSION['user_id'];

$scholarship_id = intval(
    $_POST['scholarship_id'] ?? 0
);

if ($scholarship_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid scholarship."
    ]);

    exit();
}

$stmt = $conn->prepare(
    "INSERT INTO applications
    (
        user_id,
        scholarship_id,
        status,
        applied_at
    )
    VALUES (?, ?, 'Applied', NOW())

    ON DUPLICATE KEY UPDATE
        status = 'Applied',
        applied_at = NOW()"
);

$stmt->bind_param(
    "ii",
    $user_id,
    $scholarship_id
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Application recorded."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not record application."
    ]);
}

?>