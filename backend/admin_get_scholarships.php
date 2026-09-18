<?php

require_once 'admin_guard.php';

$status = trim($_GET['status'] ?? 'all');

$allowed = [
    'all',
    'pending',
    'approved',
    'rejected'
];

if (!in_array($status, $allowed, true)) {
    $status = 'all';
}


$sql = "
    SELECT
        s.scholarship_id,
        s.title,
        s.provider,
        s.application_link,
        s.description,
        s.education_level,
        s.eligible_courses,
        s.minimum_gpa,
        s.eligible_nationalities,
        s.age_limit,
        s.ielts_required,
        s.funding_type,
        s.country,
        s.university,
        s.duration,
        s.deadline,
        s.verification_status,
        s.verified_by,
        s.verified_at,
        s.posted_by,
        s.created_at,

        u.username AS posted_by_username,
        u.email AS posted_by_email

    FROM scholarships s

    LEFT JOIN users u
        ON s.posted_by = u.user_id
";


if ($status !== 'all') {

    $sql .= "
        WHERE s.verification_status = ?
    ";
}


$sql .= "
    ORDER BY s.created_at DESC
";


if ($status === 'all') {

    $stmt = $conn->prepare($sql);

} else {

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        's',
        $status
    );
}


$stmt->execute();

$result = $stmt->get_result();

$scholarships = [];


while ($row = $result->fetch_assoc()) {

    $row['scholarship_id'] =
        (int) $row['scholarship_id'];

    $row['minimum_gpa'] =
        $row['minimum_gpa'] !== null
            ? (float) $row['minimum_gpa']
            : null;

    $row['age_limit'] =
        $row['age_limit'] !== null
            ? (int) $row['age_limit']
            : null;

    $row['ielts_required'] =
        (int) $row['ielts_required'];


    $benefits = [];

    $benefit_stmt = $conn->prepare(
        "SELECT
            benefit_type,
            description
         FROM scholarship_benefits
         WHERE scholarship_id = ?"
    );

    $benefit_stmt->bind_param(
        'i',
        $row['scholarship_id']
    );

    $benefit_stmt->execute();

    $benefit_result =
        $benefit_stmt->get_result();


    while (
        $benefit =
        $benefit_result->fetch_assoc()
    ) {
        $benefits[] = $benefit;
    }


    $benefit_stmt->close();

    $row['benefits'] = $benefits;

    $scholarships[] = $row;
}


$stmt->close();


echo json_encode([
    'success' => true,
    'count' => count($scholarships),
    'scholarships' => $scholarships
]);