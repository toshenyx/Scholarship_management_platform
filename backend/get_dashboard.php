<?php

session_start();

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "db.php";


/*RESPONSE HELPER*/

function sendDashboardResponse(
    $data,
    $status = 200
) {
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* LOGIN CHECK*/

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {

    sendDashboardResponse([
        "success" => false,
        "logged_in" => false,
        "message" =>
            "User is not logged in."
    ], 401);
}


$user_id =
    (int) $_SESSION['user_id'];


/* GET USER DETAILS*/

$stmt =
    $conn->prepare("

        SELECT
            user_id,
            username,
            email,
            role

        FROM users

        WHERE user_id = ?

        LIMIT 1

    ");


if (!$stmt) {

    sendDashboardResponse([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Unable to load user details."
    ], 500);
}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$user =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (!$user) {

    sendDashboardResponse([
        "success" => false,
        "logged_in" => false,
        "message" =>
            "User was not found."
    ], 404);
}


/* SAVED SCHOLARSHIPS COUNT*/

$stmt =
    $conn->prepare("

        SELECT COUNT(*) AS total

        FROM saved_scholarships

        WHERE user_id = ?

    ");


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$row =
    $stmt
        ->get_result()
        ->fetch_assoc();


$saved_scholarships =
    (int) ($row['total'] ?? 0);


$stmt->close();


/*APPLICATION COUNT*/

$stmt =
    $conn->prepare("

        SELECT COUNT(*) AS total

        FROM applications

        WHERE user_id = ?

    ");


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$row =
    $stmt
        ->get_result()
        ->fetch_assoc();


$applications =
    (int) ($row['total'] ?? 0);


$stmt->close();


/*UNREAD NOTIFICATIONS*/

$stmt =
    $conn->prepare("

        SELECT COUNT(*) AS total

        FROM notifications

        WHERE user_id = ?

        AND is_read = 0

    ");


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$row =
    $stmt
        ->get_result()
        ->fetch_assoc();


$unread_notifications =
    (int) ($row['total'] ?? 0);


$stmt->close();


/* RECOMMENDED SCHOLARSHIPS */


$recommended_scholarships = 0;

$profile_complete = false;


/* GET STUDENT MATCHING PROFILE*/

$profileStmt =
    $conn->prepare("

        SELECT

            sp.full_name,
            sp.nationality,
            sp.country_of_residence,

            e.education_level,
            e.institution,
            e.course,
            e.gpa,

            pref.funding_preference,
            pref.preferred_country,
            pref.preferred_field

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id =
               u.user_id

        LEFT JOIN education e
            ON e.education_id =
            (
                SELECT e2.education_id

                FROM education e2

                WHERE e2.user_id =
                      u.user_id

                ORDER BY
                    e2.education_id DESC

                LIMIT 1
            )

        LEFT JOIN student_preferences pref
            ON pref.user_id =
               u.user_id

        WHERE u.user_id = ?

        LIMIT 1

    ");


if ($profileStmt) {

    $profileStmt->bind_param(
        "i",
        $user_id
    );


    $profileStmt->execute();


    $profile =
        $profileStmt
            ->get_result()
            ->fetch_assoc();


    $profileStmt->close();


    /* CHECK PROFILE COMPLETION*/

    if ($profile) {

        $requiredFields = [

            'full_name',

            'nationality',

            'country_of_residence',

            'education_level',

            'institution',

            'course',

            'gpa',

            'funding_preference',

            'preferred_field'
        ];


        $profile_complete =
            true;


        foreach (
            $requiredFields
            as $field
        ) {

            if (
                !isset($profile[$field]) ||
                trim(
                    (string)
                    $profile[$field]
                ) === ''
            ) {

                $profile_complete =
                    false;

                break;
            }
        }

    }


    /* ONLY MATCH IF PROFILE COMPLETE*/

    if (
        $profile_complete === true
    ) {

        /* NORMALIZATION HELPERS*/

        $normalizeEducation =
            function ($level) {

                $level =
                    strtolower(
                        trim(
                            (string)
                            $level
                        )
                    );


                if ($level === '') {
                    return '';
                }


                if (
                    strpos(
                        $level,
                        'undergraduate'
                    ) !== false ||
                    strpos(
                        $level,
                        'bachelor'
                    ) !== false
                ) {

                    return 'undergraduate';
                }


                if (
                    strpos(
                        $level,
                        'postgraduate'
                    ) !== false ||
                    strpos(
                        $level,
                        'master'
                    ) !== false ||
                    strpos(
                        $level,
                        'graduate'
                    ) !== false
                ) {

                    return 'postgraduate';
                }


                if (
                    strpos(
                        $level,
                        'phd'
                    ) !== false ||
                    strpos(
                        $level,
                        'ph.d'
                    ) !== false ||
                    strpos(
                        $level,
                        'doctorate'
                    ) !== false ||
                    strpos(
                        $level,
                        'doctoral'
                    ) !== false
                ) {

                    return 'doctorate';
                }


                if (
                    strpos(
                        $level,
                        'diploma'
                    ) !== false
                ) {

                    return 'diploma';
                }


                if (
                    strpos(
                        $level,
                        'certificate'
                    ) !== false
                ) {

                    return 'certificate';
                }


                return $level;
            };


        /*STUDENT DATA*/

        $studentEducation =
            $normalizeEducation(
                $profile[
                    'education_level'
                ]
            );


        $studentCourse =
            strtolower(
                trim(
                    (string)
                    $profile['course']
                )
            );


        $preferredField =
            strtolower(
                trim(
                    (string)
                    $profile[
                        'preferred_field'
                    ]
                )
            );


        $studentNationality =
            strtolower(
                trim(
                    (string)
                    $profile[
                        'nationality'
                    ]
                )
            );


        $studentGpa =
            (float)
            $profile['gpa'];


        $fundingPreference =
            strtolower(
                trim(
                    (string)
                    $profile[
                        'funding_preference'
                    ]
                )
            );


        /* GET ACTIVE APPROVED SCHOLARSHIPS*/

        $scholarshipStmt =
            $conn->prepare("

                SELECT

                    scholarship_id,
                    education_level,
                    eligible_courses,
                    minimum_gpa,
                    eligible_nationalities,
                    funding_type

                FROM scholarships

                WHERE
                    verification_status =
                    'approved'

                AND
                (
                    deadline IS NULL
                    OR
                    deadline >= CURDATE()
                )

            ");


        if ($scholarshipStmt) {

            $scholarshipStmt->execute();


            $scholarshipResult =
                $scholarshipStmt
                    ->get_result();


            while (
                $scholarship =
                    $scholarshipResult
                        ->fetch_assoc()
            ) {

                /*MATCH SCORE*/

                $score = 0;


                /* education level - 25 */

                $scholarshipEducation =
                    $normalizeEducation(
                        $scholarship[
                            'education_level'
                        ]
                    );


                if (
                    $studentEducation !== '' &&
                    $studentEducation ===
                    $scholarshipEducation
                ) {

                    $score += 25;
                }


                /* field / course - 25 */

                $eligibleCourses =
                    strtolower(
                        trim(
                            (string)
                            $scholarship[
                                'eligible_courses'
                            ]
                        )
                    );


                if (
                    $eligibleCourses === '' ||
                    strpos(
                        $eligibleCourses,
                        'all fields'
                    ) !== false ||
                    strpos(
                        $eligibleCourses,
                        'all courses'
                    ) !== false
                ) {

                    $score += 25;

                } else {

                    $fieldMatches =
                        false;


                    if (
                        $studentCourse !== '' &&
                        (
                            strpos(
                                $eligibleCourses,
                                $studentCourse
                            ) !== false ||
                            strpos(
                                $studentCourse,
                                $eligibleCourses
                            ) !== false
                        )
                    ) {

                        $fieldMatches =
                            true;
                    }


                    if (
                        !$fieldMatches &&
                        $preferredField !== '' &&
                        (
                            strpos(
                                $eligibleCourses,
                                $preferredField
                            ) !== false ||
                            strpos(
                                $preferredField,
                                $eligibleCourses
                            ) !== false
                        )
                    ) {

                        $fieldMatches =
                            true;
                    }


                    if ($fieldMatches) {

                        $score += 25;
                    }
                }


                /* gpa - 20*/

                $minimumGpa =
                    $scholarship[
                        'minimum_gpa'
                    ];


                if (
                    $minimumGpa === null ||
                    $minimumGpa === '' ||
                    $studentGpa >=
                    (float) $minimumGpa
                ) {

                    $score += 20;
                }


                /* nationality - 20 */

                $eligibleNationalities =
                    strtolower(
                        trim(
                            (string)
                            $scholarship[
                                'eligible_nationalities'
                            ]
                        )
                    );


                if (
                    $eligibleNationalities === '' ||
                    strpos(
                        $eligibleNationalities,
                        'international'
                    ) !== false ||
                    strpos(
                        $eligibleNationalities,
                        'all nationalities'
                    ) !== false ||
                    (
                        $studentNationality !== '' &&
                        strpos(
                            $eligibleNationalities,
                            $studentNationality
                        ) !== false
                    )
                ) {

                    $score += 20;
                }


                /* funding -10 */

                $scholarshipFunding =
                    strtolower(
                        trim(
                            (string)
                            $scholarship[
                                'funding_type'
                            ]
                        )
                    );


                if (
                    $fundingPreference !== '' &&
                    $fundingPreference ===
                    $scholarshipFunding
                ) {

                    $score += 10;
                }


                /* recommendation */

                if ($score >= 55) {

                    $recommended_scholarships++;
                }
            }


            $scholarshipStmt->close();
        }
    }
}


/* RETURN DASHBOARD DATA */

echo json_encode([

    "success" =>
        true,

    "logged_in" =>
        true,

    "user_id" =>
        $user['user_id'],

    "username" =>
        $user['username'],

    "email" =>
        $user['email'],

    "role" =>
        $user['role'],

    "profile_complete" =>
        $profile_complete,

    "recommended_scholarships" =>
        $recommended_scholarships,

    "saved_scholarships" =>
        $saved_scholarships,

    "applications" =>
        $applications,

    "unread_notifications" =>
        $unread_notifications

]);


$conn->close();

?>