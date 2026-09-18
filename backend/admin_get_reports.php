<?php

require_once 'admin_guard.php';


function countRows(
    $conn,
    $sql
) {

    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}


$report = [

    'users' => [
        'students' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM users
             WHERE role = 'student'"
        ),

        'admins' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM users
             WHERE role = 'admin'"
        )
    ],


    'scholarships' => [

        'total' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships"
        ),

        'approved' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships
             WHERE verification_status = 'approved'"
        ),

        'pending' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships
             WHERE verification_status = 'pending'"
        ),

        'rejected' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships
             WHERE verification_status = 'rejected'"
        )
    ],


    'funding' => [

        'fully_funded' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships
             WHERE funding_type = 'fully_funded'"
        ),

        'partially_funded' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships
             WHERE funding_type = 'partially_funded'"
        ),

        'tuition_only' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM scholarships
             WHERE funding_type = 'tuition_only'"
        )
    ],


    'applications' => [

        'total' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM applications"
        ),

        'accepted' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM applications
             WHERE status = 'Accepted'"
        ),

        'rejected' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM applications
             WHERE status = 'Rejected'"
        ),

        'under_review' => countRows(
            $conn,
            "SELECT COUNT(*) AS total
             FROM applications
             WHERE status = 'Under Review'"
        )
    ]
];


$countries = [];


$result = $conn->query(
    "SELECT
        country,
        COUNT(*) AS total

     FROM scholarships

     WHERE country IS NOT NULL
     AND country <> ''

     GROUP BY country

     ORDER BY total DESC

     LIMIT 10"
);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $countries[] = [
            'country' => $row['country'],
            'total' => (int) $row['total']
        ];
    }
}


$report['countries'] = $countries;


echo json_encode([
    'success' => true,
    'generated_at' => date('Y-m-d H:i:s'),
    'report' => $report
]);