<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';


function sendResponse($data, $status = 200)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* LOGIN REQUIRED*/

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {

    sendResponse([
        'success' => false,
        'logged_in' => false,
        'message' =>
            'Please log in to search scholarships.'
    ], 401);
}


/* GET SEARCH PARAMETERS*/

$study =
    trim(
        $_GET['study'] ?? ''
    );


$country =
    trim(
        $_GET['country'] ?? ''
    );


$funding =
    trim(
        $_GET['funding'] ?? ''
    );


$study_level =
    trim(
        $_GET['study_level'] ?? ''
    );


$accommodation =
    trim(
        $_GET['accommodation'] ?? ''
    );


/* BASE QUERY*/

$sql = "

    SELECT DISTINCT

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
        s.verification_status

    FROM scholarships s

    WHERE
        s.verification_status = 'approved'

    AND
        (
            s.deadline IS NULL
            OR
            s.deadline >= CURDATE()
        )

";


$params = [];

$types = '';


/* STUDY / COURSE*/

if ($study !== '') {

    $sql .= "

        AND
        (
            s.title LIKE ?
            OR
            s.eligible_courses LIKE ?
            OR
            s.description LIKE ?
            OR
            s.university LIKE ?
        )

    ";


    $studySearch =
        '%' . $study . '%';


    $params[] =
        $studySearch;

    $params[] =
        $studySearch;

    $params[] =
        $studySearch;

    $params[] =
        $studySearch;


    $types .= 'ssss';
}


/* COUNTRY*/

if (
    $country !== '' &&
    strtolower($country) !== 'other'
) {

    $sql .= "

        AND s.country LIKE ?

    ";


    $params[] =
        '%' . $country . '%';

    $types .= 's';
}


/* FUNDING*/

if ($funding !== '') {

    $sql .= "

        AND s.funding_type = ?

    ";


    $params[] =
        $funding;

    $types .= 's';
}


/* STUDY LEVEL*/

if ($study_level !== '') {

    /*
     Bachelor/Undergraduate need to be
     treated as equivalent.

     Masters/Postgraduate are also
     treated as equivalent.*/

    $level =
        strtolower(
            $study_level
        );


    if (
        $level === 'undergraduate' ||
        $level === 'bachelor'
    ) {

        $sql .= "

            AND
            (
                LOWER(s.education_level)
                    LIKE '%undergraduate%'

                OR

                LOWER(s.education_level)
                    LIKE '%bachelor%'
            )

        ";

    }
    elseif (
        $level === 'masters' ||
        $level === 'master' ||
        $level === 'postgraduate'
    ) {

        $sql .= "

            AND
            (
                LOWER(s.education_level)
                    LIKE '%master%'

                OR

                LOWER(s.education_level)
                    LIKE '%postgraduate%'
            )

        ";

    }
    elseif (
        $level === 'phd' ||
        $level === 'doctorate'
    ) {

        $sql .= "

            AND
            (
                LOWER(s.education_level)
                    LIKE '%phd%'

                OR

                LOWER(s.education_level)
                    LIKE '%doctor%'
            )

        ";

    }
    else {

        $sql .= "

            AND s.education_level LIKE ?

        ";


        $params[] =
            '%' .
            $study_level .
            '%';

        $types .= 's';
    }
}



if (
    $accommodation === 'included'
) {

    $sql .= "

        AND EXISTS
        (
            SELECT 1

            FROM scholarship_benefits sb

            WHERE
                sb.scholarship_id =
                s.scholarship_id

            AND
                sb.benefit_type =
                'accommodation'
        )

    ";
}


elseif (
    $accommodation === 'not-included'
) {

    $sql .= "

        AND NOT EXISTS
        (
            SELECT 1

            FROM scholarship_benefits sb

            WHERE
                sb.scholarship_id =
                s.scholarship_id

            AND
                sb.benefit_type =
                'accommodation'
        )

    ";
}



$sql .= "

    ORDER BY

        CASE
            WHEN s.deadline IS NULL
            THEN 1
            ELSE 0
        END,

        s.deadline ASC,

        s.created_at DESC

    LIMIT 20

";



$stmt =
    $conn->prepare(
        $sql
    );


if (!$stmt) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to prepare scholarship search: ' .
            $conn->error
    ], 500);
}



if (
    !empty($params)
) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


if (!$stmt->execute()) {

    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' =>
            'Unable to search scholarships: ' .
            $stmt->error
    ], 500);
}


$result =
    $stmt->get_result();


$scholarships = [];


while (
    $row =
        $result->fetch_assoc()
) {

    /*Load scholarship benefits*/

    $benefitStmt =
        $conn->prepare("

            SELECT
                benefit_type,
                description

            FROM scholarship_benefits

            WHERE scholarship_id = ?

            ORDER BY benefit_id ASC

        ");


    $benefits = [];


    if ($benefitStmt) {

        $scholarshipId =
            (int)
            $row['scholarship_id'];


        $benefitStmt->bind_param(
            'i',
            $scholarshipId
        );


        $benefitStmt->execute();


        $benefitResult =
            $benefitStmt->get_result();


        while (
            $benefit =
                $benefitResult->fetch_assoc()
        ) {

            $benefits[] =
                $benefit;
        }


        $benefitStmt->close();
    }


    $row['benefits'] =
        $benefits;


    $scholarships[] =
        $row;
}


$stmt->close();


sendResponse([
    'success' => true,
    'logged_in' => true,

    'count' =>
        count($scholarships),

    'scholarships' =>
        $scholarships,

    'filters' => [
        'study' =>
            $study,

        'country' =>
            $country,

        'funding' =>
            $funding,

        'study_level' =>
            $study_level,

        'accommodation' =>
            $accommodation
    ],

    'message' =>
        count($scholarships) > 0
            ? 'Scholarships found successfully.'
            : 'No scholarships matched your search.'
]);

?>