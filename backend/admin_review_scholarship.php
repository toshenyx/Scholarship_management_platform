<?php

require_once 'admin_guard.php';


$data = json_decode(
    file_get_contents('php://input'),
    true
);


$scholarship_id =
    (int) ($data['scholarship_id'] ?? 0);

$action =
    strtolower(
        trim($data['action'] ?? '')
    );


if ($scholarship_id <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid scholarship.'
    ]);

    exit;
}


if (
    !in_array(
        $action,
        ['approve', 'reject'],
        true
    )
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid review action.'
    ]);

    exit;
}


$status =
    $action === 'approve'
        ? 'approved'
        : 'rejected';


$stmt = $conn->prepare(
    "UPDATE scholarships

     SET
        verification_status = ?,
        verified_by = ?,
        verified_at = NOW()

     WHERE scholarship_id = ?"
);


$stmt->bind_param(
    'sii',
    $status,
    $admin_id,
    $scholarship_id
);


if (!$stmt->execute()) {

    echo json_encode([
        'success' => false,
        'message' => 'Could not update scholarship.'
    ]);

    exit;
}


if ($stmt->affected_rows === 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Scholarship was not found or already has this status.'
    ]);

    exit;
}


echo json_encode([
    'success' => true,
    'message' =>
        $status === 'approved'
            ? 'Scholarship approved successfully.'
            : 'Scholarship rejected successfully.'
]);