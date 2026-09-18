<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

/*
|--------------------------------------------------------------------------
| RESPONSE HELPER
|--------------------------------------------------------------------------
*/

function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {
    sendResponse([
        'success' => false,
        'logged_in' => false,
        'profile_complete' => false,
        'message' => 'You must be logged in to generate a scholarship report.'
    ], 401);
}

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$userSql = "
    SELECT
        user_id,
        username,
        email,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$userStmt = $conn->prepare($userSql);

if (!$userStmt) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Unable to prepare user query.'
    ], 500);
}

$userStmt->bind_param(
    'i',
    $user_id
);

$userStmt->execute();

$userResult =
    $userStmt->get_result();

$user =
    $userResult->fetch_assoc();

$userStmt->close();

if (!$user) {
    sendResponse([
        'success' => false,
        'logged_in' => false,
        'message' => 'User account could not be found.'
    ], 404);
}


/*
|--------------------------------------------------------------------------
| GET PERSONAL PROFILE
|--------------------------------------------------------------------------
*/

$personalSql = "
    SELECT
        full_name,
        phone,
        date_of_birth,
        gender,
        nationality,
        country_of_residence,
        work_experience,
        achievements,
        additional_notes
    FROM student_profiles
    WHERE user_id = ?
    LIMIT 1
";

$personalStmt =
    $conn->prepare($personalSql);

if (!$personalStmt) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Unable to prepare profile query.'
    ], 500);
}

$personalStmt->bind_param(
    'i',
    $user_id
);

$personalStmt->execute();

$personalResult =
    $personalStmt->get_result();

$personal =
    $personalResult->fetch_assoc();

$personalStmt->close();


/*
|--------------------------------------------------------------------------
| GET EDUCATION
|--------------------------------------------------------------------------
|
| If the student has more than one education record,
| use the most recently added one.
|
*/

$educationSql = "
    SELECT
        education_level,
        graduation_year,
        institution,
        gpa,
        course,
        academic_achievements
    FROM education
    WHERE user_id = ?
    ORDER BY education_id DESC
    LIMIT 1
";

$educationStmt =
    $conn->prepare($educationSql);

if (!$educationStmt) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Unable to prepare education query.'
    ], 500);
}

$educationStmt->bind_param(
    'i',
    $user_id
);

$educationStmt->execute();

$educationResult =
    $educationStmt->get_result();

$education =
    $educationResult->fetch_assoc();

$educationStmt->close();


/*
|--------------------------------------------------------------------------
| GET STUDENT PREFERENCES
|--------------------------------------------------------------------------
*/

$preferencesSql = "
    SELECT
        funding_preference,
        work_study,
        research_grants,
        short_courses,
        exchange_programmes,
        living_stipend,
        preferred_country,
        preferred_field,
        other_requirements
    FROM student_preferences
    WHERE user_id = ?
    LIMIT 1
";

$preferencesStmt =
    $conn->prepare($preferencesSql);

if (!$preferencesStmt) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'message' => 'Unable to prepare preferences query.'
    ], 500);
}

$preferencesStmt->bind_param(
    'i',
    $user_id
);

$preferencesStmt->execute();

$preferencesResult =
    $preferencesStmt->get_result();

$preferences =
    $preferencesResult->fetch_assoc();

$preferencesStmt->close();


/*
|--------------------------------------------------------------------------
| CHECK REPORT ACCESS
|--------------------------------------------------------------------------
|
| Keep this consistent with the matching requirements.
|
| Required:
| - Nationality
| - Education level
| - Course
| - GPA
| - Funding preference
| - Preferred field
|
| Preferred country remains OPTIONAL.
|
*/

$missing_fields = [];

if (
    !$personal ||
    trim(
        (string) (
            $personal['nationality'] ?? ''
        )
    ) === ''
) {
    $missing_fields[] =
        'Nationality';
}

if (
    !$education ||
    trim(
        (string) (
            $education['education_level'] ?? ''
        )
    ) === ''
) {
    $missing_fields[] =
        'Education level';
}

if (
    !$education ||
    trim(
        (string) (
            $education['course'] ?? ''
        )
    ) === ''
) {
    $missing_fields[] =
        'Course';
}

if (
    !$education ||
    $education['gpa'] === null ||
    $education['gpa'] === ''
) {
    $missing_fields[] =
        'GPA';
}

if (
    !$preferences ||
    trim(
        (string) (
            $preferences['funding_preference']
            ?? ''
        )
    ) === ''
) {
    $missing_fields[] =
        'Funding preference';
}

if (
    !$preferences ||
    trim(
        (string) (
            $preferences['preferred_field']
            ?? ''
        )
    ) === ''
) {
    $missing_fields[] =
        'Preferred field';
}

$profile_complete =
    count($missing_fields) === 0;

if (!$profile_complete) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'profile_complete' => false,
        'missing_fields' => $missing_fields,
        'message' =>
            'Complete your profile before generating a scholarship report.'
    ], 403);
}


/*
|--------------------------------------------------------------------------
| GET PERSONALIZED RECOMMENDATIONS
|--------------------------------------------------------------------------
|
| This reproduces the SAME scoring rules currently used by
| get_recommendations.php.
|
|--------------------------------------------------------------------------
*/


function normalizeText($value)
{
    return strtolower(
        trim(
            (string) $value
        )
    );
}


function normalizeEducationLevel($level)
{
    $level =
        strtolower(
            trim(
                (string) $level
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
}


$student_nationality =
    normalizeText(
        $personal['nationality']
    );

$student_level =
    normalizeEducationLevel(
        $education['education_level']
    );

$student_course =
    normalizeText(
        $education['course']
    );

$student_gpa =
    (float) $education['gpa'];

$preferred_field =
    normalizeText(
        $preferences['preferred_field']
    );

$preferred_country =
    normalizeText(
        $preferences['preferred_country']
        ?? ''
    );

$funding_preference =
    normalizeText(
        $preferences['funding_preference']
    );


/*
|--------------------------------------------------------------------------
| GET APPROVED ACTIVE SCHOLARSHIPS
|--------------------------------------------------------------------------
*/

$scholarshipSql = "
    SELECT
        scholarship_id,
        title,
        provider,
        application_link,
        description,
        education_level,
        eligible_courses,
        minimum_gpa,
        eligible_nationalities,
        age_limit,
        ielts_required,
        funding_type,
        country,
        university,
        duration,
        deadline
    FROM scholarships
    WHERE verification_status = 'approved'
    AND (
        deadline IS NULL
        OR deadline >= CURDATE()
    )
    ORDER BY deadline ASC
";

$scholarshipResult =
    $conn->query(
        $scholarshipSql
    );

if (!$scholarshipResult) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'profile_complete' => true,
        'message' =>
            'Unable to load scholarships for the report.'
    ], 500);
}

$recommendations = [];

while (
    $scholarship =
        $scholarshipResult->fetch_assoc()
) {

    $score = 0;

    $country_bonus = 0;

    $match_reasons = [];

    $warnings = [];


    /*
    |--------------------------------------------------------------------------
    | EDUCATION LEVEL - 25
    |--------------------------------------------------------------------------
    */

    $scholarship_level =
        normalizeEducationLevel(
            $scholarship[
                'education_level'
            ] ?? ''
        );

    if (
        $scholarship_level === '' ||
        $scholarship_level ===
        $student_level
    ) {

        $score += 25;

        $match_reasons[] =
            'Education level matches';

    } else {

        $warnings[] =
            'Education level may not match';
    }


    /*
    |--------------------------------------------------------------------------
    | FIELD / COURSE - 25
    |--------------------------------------------------------------------------
    */

    $eligible_courses =
        normalizeText(
            $scholarship[
                'eligible_courses'
            ] ?? ''
        );

    if (
        $eligible_courses === '' ||
        strpos(
            $eligible_courses,
            'all fields'
        ) !== false ||
        strpos(
            $eligible_courses,
            'all courses'
        ) !== false
    ) {

        $score += 25;

        $match_reasons[] =
            'Open to multiple fields';

    } elseif (
        (
            $preferred_field !== '' &&
            (
                strpos(
                    $eligible_courses,
                    $preferred_field
                ) !== false ||
                strpos(
                    $preferred_field,
                    $eligible_courses
                ) !== false
            )
        ) ||
        (
            $student_course !== '' &&
            (
                strpos(
                    $eligible_courses,
                    $student_course
                ) !== false ||
                strpos(
                    $student_course,
                    $eligible_courses
                ) !== false
            )
        )
    ) {

        $score += 25;

        $match_reasons[] =
            'Field of study matches';

    } else {

        $warnings[] =
            'Field of study may not match';
    }


    /*
    |--------------------------------------------------------------------------
    | GPA - 20
    |--------------------------------------------------------------------------
    */

    $minimum_gpa =
        $scholarship[
            'minimum_gpa'
        ];

    if (
        $minimum_gpa === null ||
        $minimum_gpa === '' ||
        (float) $minimum_gpa <= 0
    ) {

        $score += 20;

        $match_reasons[] =
            'No minimum GPA restriction listed';

    } elseif (
        $student_gpa >=
        (float) $minimum_gpa
    ) {

        $score += 20;

        $match_reasons[] =
            'Meets minimum GPA';

    } else {

        $warnings[] =
            'GPA is below the listed minimum';
    }


    /*
    |--------------------------------------------------------------------------
    | NATIONALITY - 20
    |--------------------------------------------------------------------------
    */

    $eligible_nationalities =
        normalizeText(
            $scholarship[
                'eligible_nationalities'
            ] ?? ''
        );

    if (
        $eligible_nationalities === '' ||
        strpos(
            $eligible_nationalities,
            'international'
        ) !== false ||
        strpos(
            $eligible_nationalities,
            'all nationalities'
        ) !== false ||
        strpos(
            $eligible_nationalities,
            'all countries'
        ) !== false ||
        (
            $student_nationality !== '' &&
            strpos(
                $eligible_nationalities,
                $student_nationality
            ) !== false
        )
    ) {

        $score += 20;

        $match_reasons[] =
            'Nationality is eligible';

    } else {

        $warnings[] =
            'Nationality eligibility should be checked';
    }


    /*
    |--------------------------------------------------------------------------
    | FUNDING - 10
    |--------------------------------------------------------------------------
    */

    $scholarship_funding =
        normalizeText(
            $scholarship[
                'funding_type'
            ] ?? ''
        );

    if (
        $scholarship_funding ===
        $funding_preference
    ) {

        $score += 10;

        $match_reasons[] =
            'Matches funding preference';

    } else {

        $warnings[] =
            'Funding type differs from your preference';
    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONAL COUNTRY BONUS
    |--------------------------------------------------------------------------
    */

    $scholarship_country =
        normalizeText(
            $scholarship[
                'country'
            ] ?? ''
        );

    $country_match = false;

    if (
        $preferred_country !== '' &&
        $scholarship_country !== '' &&
        (
            strpos(
                $scholarship_country,
                $preferred_country
            ) !== false ||
            strpos(
                $preferred_country,
                $scholarship_country
            ) !== false
        )
    ) {

        $country_bonus = 5;

        $country_match = true;

        $match_reasons[] =
            'Matches preferred country';
    }


    /*
    |--------------------------------------------------------------------------
    | MATCH LABEL
    |--------------------------------------------------------------------------
    */

    if ($score >= 85) {

        $match_label =
            'Excellent Match';

    } elseif ($score >= 70) {

        $match_label =
            'Strong Match';

    } elseif ($score >= 55) {

        $match_label =
            'Possible Match';

    } else {

        $match_label =
            'Low Match';
    }


    /*
    |--------------------------------------------------------------------------
    | ONLY REPORT MATCHES >= 55
    |--------------------------------------------------------------------------
    */

    if ($score < 55) {
        continue;
    }


    $scholarship[
        'match_score'
    ] = $score;

    $scholarship[
        'match_label'
    ] = $match_label;

    $scholarship[
        'country_preference_match'
    ] = $country_match;

    $scholarship[
        'match_reasons'
    ] = $match_reasons;

    $scholarship[
        'warnings'
    ] = $warnings;

    $scholarship[
        'ranking_score'
    ] =
        $score +
        $country_bonus;

    $recommendations[] =
        $scholarship;
}


/*
|--------------------------------------------------------------------------
| SORT RECOMMENDATIONS
|--------------------------------------------------------------------------
*/

usort(
    $recommendations,
    function ($a, $b) {

        $rankingDifference =
            (
                $b['ranking_score']
                ?? 0
            )
            -
            (
                $a['ranking_score']
                ?? 0
            );

        if (
            $rankingDifference !== 0
        ) {
            return $rankingDifference;
        }

        return strcmp(
            (string) (
                $a['deadline']
                ?? ''
            ),
            (string) (
                $b['deadline']
                ?? ''
            )
        );
    }
);

foreach (
    $recommendations
    as &$recommendation
) {
    unset(
        $recommendation[
            'ranking_score'
        ]
    );
}

unset($recommendation);


/*
|--------------------------------------------------------------------------
| GET SAVED SCHOLARSHIPS
|--------------------------------------------------------------------------
*/

$savedSql = "
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
        ss.saved_at
    FROM saved_scholarships ss
    INNER JOIN scholarships s
        ON s.scholarship_id =
           ss.scholarship_id
    WHERE ss.user_id = ?
    ORDER BY ss.saved_at DESC
";

$savedStmt =
    $conn->prepare(
        $savedSql
    );

if (!$savedStmt) {
    sendResponse([
        'success' => false,
        'logged_in' => true,
        'profile_complete' => true,
        'message' =>
            'Unable to prepare saved scholarships query.'
    ], 500);
}

$savedStmt->bind_param(
    'i',
    $user_id
);

$savedStmt->execute();

$savedResult =
    $savedStmt->get_result();

$saved_scholarships = [];

while (
    $saved =
        $savedResult->fetch_assoc()
) {
    $saved_scholarships[] =
        $saved;
}

$savedStmt->close();


/*
|--------------------------------------------------------------------------
| FINAL REPORT RESPONSE
|--------------------------------------------------------------------------
*/

sendResponse([
    'success' => true,
    'logged_in' => true,
    'profile_complete' => true,

    'report' => [
        'generated_at' =>
            date('Y-m-d H:i:s'),

        'student' => [
            'username' =>
                $user['username'],

            'email' =>
                $user['email'],

            'full_name' =>
                $personal[
                    'full_name'
                ] ?? '',

            'nationality' =>
                $personal[
                    'nationality'
                ] ?? '',

            'country_of_residence' =>
                $personal[
                    'country_of_residence'
                ] ?? '',

            'education_level' =>
                $education[
                    'education_level'
                ] ?? '',

            'institution' =>
                $education[
                    'institution'
                ] ?? '',

            'course' =>
                $education[
                    'course'
                ] ?? '',

            'gpa' =>
                $education[
                    'gpa'
                ] ?? '',

            'graduation_year' =>
                $education[
                    'graduation_year'
                ] ?? '',

            'preferred_field' =>
                $preferences[
                    'preferred_field'
                ] ?? '',

            'preferred_country' =>
                $preferences[
                    'preferred_country'
                ] ?? '',

            'funding_preference' =>
                $preferences[
                    'funding_preference'
                ] ?? ''
        ],

        'statistics' => [
            'recommendation_count' =>
                count(
                    $recommendations
                ),

            'saved_count' =>
                count(
                    $saved_scholarships
                )
        ],

        'recommendations' =>
            $recommendations,

        'saved_scholarships' =>
            $saved_scholarships
    ],

    'message' =>
        'Scholarship report data loaded successfully.'
]);
?>