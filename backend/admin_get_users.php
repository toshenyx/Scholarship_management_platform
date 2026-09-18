<?php

require_once 'admin_guard.php';


$sql = "
    SELECT
        u.user_id,
        u.username,
        u.email,
        u.role,
        u.is_verified,
        u.created_at,

        sp.full_name,
        sp.nationality,
        sp.country_of_residence,

        e.education_level,
        e.course,
        e.gpa

    FROM users u

    LEFT JOIN student_profiles sp
        ON u.user_id = sp.user_id

    LEFT JOIN education e
        ON u.user_id = e.user_id

    ORDER BY u.created_at DESC
";


$result = $conn->query($sql);


if (!$result) {

    echo json_encode([
        'success' => false,
        'message' => 'Could not load users.'
    ]);

    exit;
}


$users = [];


while ($row = $result->fetch_assoc()) {

    $row['user_id'] =
        (int) $row['user_id'];

    $row['is_verified'] =
        (int) $row['is_verified'];

    $row['gpa'] =
        $row['gpa'] !== null
            ? (float) $row['gpa']
            : null;

    $users[] = $row;
}


echo json_encode([
    'success' => true,
    'count' => count($users),
    'users' => $users
]);