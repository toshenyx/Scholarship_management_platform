<?php

require_once 'admin_guard.php';

function getCount($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}


$total_students = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

$total_admins = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'admin'"
);

$total_scholarships = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM scholarships"
);

$pending = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM scholarships
     WHERE verification_status = 'pending'"
);

$approved = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM scholarships
     WHERE verification_status = 'approved'"
);

$rejected = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM scholarships
     WHERE verification_status = 'rejected'"
);

$total_applications = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications"
);


$recent = [];

$sql = "
    SELECT
        s.scholarship_id,
        s.title,
        s.provider,
        s.country,
        s.deadline,
        s.verification_status,
        s.created_at,
        u.username AS posted_by_username

    FROM scholarships s

    LEFT JOIN users u
        ON s.posted_by = u.user_id

    ORDER BY s.created_at DESC

    LIMIT 6
";


$result = $conn->query($sql);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $row['scholarship_id'] =
            (int) $row['scholarship_id'];

        $recent[] = $row;
    }
}


echo json_encode([
    'success' => true,

    'admin' => [
        'user_id' => $admin_id,
        'username' => $admin_username ?? ''
    ],

    'stats' => [
        'students' => $total_students,
        'admins' => $total_admins,
        'scholarships' => $total_scholarships,
        'pending' => $pending,
        'approved' => $approved,
        'rejected' => $rejected,
        'applications' => $total_applications
    ],

    'recent_scholarships' => $recent
]);