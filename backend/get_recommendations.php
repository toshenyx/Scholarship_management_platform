<?php

session_start();

header("Content-Type: application/json");

require_once "db.php";


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    echo json_encode([

        "success" => false,

        "logged_in" => false,

        "profile_complete" => false,

        "message" => "You must log in first.",

        "recommendations" => []

    ]);

    exit();
}


$user_id =
    (int) $_SESSION['user_id'];



/*
|--------------------------------------------------------------------------
| NORMALIZE EDUCATION LEVEL
|--------------------------------------------------------------------------
|
| Students and scholarship posters may use different names for the
| same education level.
|
| Examples:
|
| Undergraduate / Bachelor / Bachelor's
|      -> undergraduate
|
| Postgraduate / Master / Master's
|      -> postgraduate
|
| PhD / Doctorate / Doctoral
|      -> doctorate
|
*/

function normalizeEducationLevel($level)
{

    $level = strtolower(
        trim(
            (string) $level
        )
    );


    if ($level === '') {

        return '';

    }


    /*
    |--------------------------------------------------------------------------
    | UNDERGRADUATE / BACHELOR
    |--------------------------------------------------------------------------
    */

    if (

        strpos(
            $level,
            'undergraduate'
        ) !== false

        ||

        strpos(
            $level,
            'bachelor'
        ) !== false

    ) {

        return 'undergraduate';

    }


    /*
    |--------------------------------------------------------------------------
    | POSTGRADUATE / MASTER
    |--------------------------------------------------------------------------
    */

    if (

        strpos(
            $level,
            'postgraduate'
        ) !== false

        ||

        strpos(
            $level,
            'master'
        ) !== false

        ||

        strpos(
            $level,
            'graduate'
        ) !== false

    ) {

        return 'postgraduate';

    }


    /*
    |--------------------------------------------------------------------------
    | DOCTORATE / PHD
    |--------------------------------------------------------------------------
    */

    if (

        strpos(
            $level,
            'phd'
        ) !== false

        ||

        strpos(
            $level,
            'ph.d'
        ) !== false

        ||

        strpos(
            $level,
            'doctorate'
        ) !== false

        ||

        strpos(
            $level,
            'doctoral'
        ) !== false

    ) {

        return 'doctorate';

    }


    /*
    |--------------------------------------------------------------------------
    | DIPLOMA
    |--------------------------------------------------------------------------
    */

    if (

        strpos(
            $level,
            'diploma'
        ) !== false

    ) {

        return 'diploma';

    }


    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE
    |--------------------------------------------------------------------------
    */

    if (

        strpos(
            $level,
            'certificate'
        ) !== false

    ) {

        return 'certificate';

    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    */

    return $level;

}



/*
|--------------------------------------------------------------------------
| GET STUDENT PERSONAL PROFILE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(

    "SELECT

        full_name,
        date_of_birth,
        nationality,
        country_of_residence

     FROM student_profiles

     WHERE user_id = ?

     LIMIT 1"

);


if (!$stmt) {

    echo json_encode([

        "success" => false,

        "logged_in" => true,

        "profile_complete" => false,

        "message" => "Unable to load personal profile.",

        "recommendations" => []

    ]);

    exit();
}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$personal =

    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();



/*
|--------------------------------------------------------------------------
| GET STUDENT EDUCATION
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(

    "SELECT

        education_level,
        graduation_year,
        institution,
        gpa,
        course

     FROM education

     WHERE user_id = ?

     ORDER BY education_id DESC

     LIMIT 1"

);


if (!$stmt) {

    echo json_encode([

        "success" => false,

        "logged_in" => true,

        "profile_complete" => false,

        "message" => "Unable to load education information.",

        "recommendations" => []

    ]);

    exit();
}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$education =

    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();



/*
|--------------------------------------------------------------------------
| GET STUDENT PREFERENCES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(

    "SELECT

        funding_preference,
        work_study,
        research_grants,
        short_courses,
        exchange_programmes,
        living_stipend,
        preferred_country,
        preferred_field

     FROM student_preferences

     WHERE user_id = ?

     LIMIT 1"

);


if (!$stmt) {

    echo json_encode([

        "success" => false,

        "logged_in" => true,

        "profile_complete" => false,

        "message" => "Unable to load scholarship preferences.",

        "recommendations" => []

    ]);

    exit();
}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$preferences =

    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();



/*
|--------------------------------------------------------------------------
| CHECK PROFILE COMPLETION
|--------------------------------------------------------------------------
|
| preferred_country is NOT required.
|
| If preferred_country is empty, the student can still receive
| recommendations from every available country.
|
*/

$missing_fields = [];



/*
|--------------------------------------------------------------------------
| PERSONAL PROFILE
|--------------------------------------------------------------------------
*/

if (!$personal) {

    $missing_fields[] =
        "Personal profile";

}
else {

    if (

        empty(

            trim(

                $personal['nationality'] ?? ''

            )

        )

    ) {

        $missing_fields[] =
            "Nationality";

    }

}



/*
|--------------------------------------------------------------------------
| EDUCATION
|--------------------------------------------------------------------------
*/

if (!$education) {

    $missing_fields[] =
        "Education";

}
else {

    if (

        empty(

            trim(

                $education['education_level'] ?? ''

            )

        )

    ) {

        $missing_fields[] =
            "Education level";

    }


    if (

        empty(

            trim(

                $education['course'] ?? ''

            )

        )

    ) {

        $missing_fields[] =
            "Course";

    }


    if (

        !array_key_exists(
            'gpa',
            $education
        )

        ||

        $education['gpa'] === null

        ||

        trim(
            (string) $education['gpa']
        ) === ''

    ) {

        $missing_fields[] =
            "GPA";

    }

}



/*
|--------------------------------------------------------------------------
| SCHOLARSHIP PREFERENCES
|--------------------------------------------------------------------------
*/

if (!$preferences) {

    $missing_fields[] =
        "Scholarship preferences";

}
else {

    /*
    |--------------------------------------------------------------------------
    | FUNDING PREFERENCE
    |--------------------------------------------------------------------------
    */

    if (

        empty(

            trim(

                $preferences['funding_preference'] ?? ''

            )

        )

    ) {

        $missing_fields[] =
            "Funding preference";

    }


    /*
    |--------------------------------------------------------------------------
    | PREFERRED FIELD
    |--------------------------------------------------------------------------
    */

    if (

        empty(

            trim(

                $preferences['preferred_field'] ?? ''

            )

        )

    ) {

        $missing_fields[] =
            "Preferred field";

    }


    /*
    |--------------------------------------------------------------------------
    | PREFERRED COUNTRY
    |--------------------------------------------------------------------------
    |
    | NOT REQUIRED.
    |
    | A blank value means scholarships from all available
    | countries can be recommended.
    |
    */

}



/*
|--------------------------------------------------------------------------
| STOP IF REQUIRED PROFILE INFORMATION IS MISSING
|--------------------------------------------------------------------------
*/

if (count($missing_fields) > 0) {

    echo json_encode([

        "success" => false,

        "logged_in" => true,

        "profile_complete" => false,

        "message" =>
            "Complete your profile before viewing personalized scholarship recommendations.",

        "missing_fields" =>
            $missing_fields,

        "recommendations" => []

    ]);

    exit();
}



/*
|--------------------------------------------------------------------------
| STUDENT MATCHING VALUES
|--------------------------------------------------------------------------
*/

$student_nationality =

    strtolower(

        trim(

            $personal['nationality'] ?? ''

        )

    );



/*
|--------------------------------------------------------------------------
| NORMALIZED STUDENT EDUCATION LEVEL
|--------------------------------------------------------------------------
|
| Example:
|
| Undergraduate -> undergraduate
| Bachelor      -> undergraduate
|
*/

$student_level =

    normalizeEducationLevel(

        $education['education_level'] ?? ''

    );


$student_course =

    strtolower(

        trim(

            $education['course'] ?? ''

        )

    );


$student_gpa =

    (float) $education['gpa'];



/*
|--------------------------------------------------------------------------
| OPTIONAL PREFERRED COUNTRY
|--------------------------------------------------------------------------
*/

$preferred_country =

    strtolower(

        trim(

            $preferences['preferred_country'] ?? ''

        )

    );



/*
|--------------------------------------------------------------------------
| REQUIRED PREFERRED FIELD
|--------------------------------------------------------------------------
*/

$preferred_field =

    strtolower(

        trim(

            $preferences['preferred_field'] ?? ''

        )

    );



/*
|--------------------------------------------------------------------------
| FUNDING PREFERENCE
|--------------------------------------------------------------------------
*/

$preferred_funding =

    strtolower(

        trim(

            $preferences['funding_preference'] ?? ''

        )

    );



/*
|--------------------------------------------------------------------------
| GET AVAILABLE SCHOLARSHIPS
|--------------------------------------------------------------------------
|
| Only approved scholarships are recommended.
|
| Expired scholarships are excluded.
|
*/

$stmt = $conn->prepare(

    "SELECT

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
        deadline,
        verification_status

     FROM scholarships

     WHERE verification_status = 'approved'

     AND (

        deadline IS NULL

        OR deadline >= CURDATE()

     )

     ORDER BY deadline ASC"

);


if (!$stmt) {

    echo json_encode([

        "success" => false,

        "logged_in" => true,

        "profile_complete" => true,

        "message" => "Unable to load scholarships.",

        "recommendations" => []

    ]);

    exit();
}


$stmt->execute();


$result =

    $stmt->get_result();


$recommendations = [];



/*
|--------------------------------------------------------------------------
| MATCHING ALGORITHM
|--------------------------------------------------------------------------
|
| CORE MATCH SCORE = 100 POINTS
|
| Education level       = 25
| Field / Course        = 25
| GPA                   = 20
| Nationality           = 20
| Funding preference    = 10
|                       ----
|                       100
|
|
| PREFERRED COUNTRY
| -----------------
|
| Country is NOT part of the 100-point score.
|
| If the student has no preferred country:
|     All countries are treated equally.
|
| If a preferred country exists:
|     Scholarships from that country receive a ranking bonus.
|
*/

while (

    $scholarship =
        $result->fetch_assoc()

) {


    /*
    |--------------------------------------------------------------------------
    | START SCORE
    |--------------------------------------------------------------------------
    */

    $score = 0;


    /*
    |--------------------------------------------------------------------------
    | OPTIONAL COUNTRY RANKING BONUS
    |--------------------------------------------------------------------------
    */

    $country_bonus = 0;


    $country_preference_match =
        false;


    $match_reasons = [];


    $warnings = [];



    /*
    |--------------------------------------------------------------------------
    | SCHOLARSHIP VALUES
    |--------------------------------------------------------------------------
    |
    | Education level is normalized before comparison.
    |
    */

    $scholarship_level =

        normalizeEducationLevel(

            $scholarship['education_level'] ?? ''

        );


    $eligible_courses =

        strtolower(

            trim(

                $scholarship['eligible_courses'] ?? ''

            )

        );


    $minimum_gpa =

        $scholarship['minimum_gpa'] !== null

            ? (float) $scholarship['minimum_gpa']

            : null;


    $eligible_nationalities =

        strtolower(

            trim(

                $scholarship['eligible_nationalities'] ?? ''

            )

        );


    $scholarship_country =

        strtolower(

            trim(

                $scholarship['country'] ?? ''

            )

        );


    $funding_type =

        strtolower(

            trim(

                $scholarship['funding_type'] ?? ''

            )

        );



    /*
    |--------------------------------------------------------------------------
    | 1. EDUCATION LEVEL — 25 POINTS
    |--------------------------------------------------------------------------
    |
    | Common equivalent terms are normalized before comparison.
    |
    | Examples:
    |
    | Student: Undergraduate
    | Scholarship: Bachelor
    |
    | Both become:
    | undergraduate
    |
    */

    if (

        $scholarship_level === ''

        ||

        $scholarship_level ===
            $student_level

    ) {

        $score += 25;


        $match_reasons[] =
            "Education level matches";

    }
    else {

        $warnings[] =
            "Education level may not match";

    }



    /*
    |--------------------------------------------------------------------------
    | 2. COURSE / FIELD — 25 POINTS
    |--------------------------------------------------------------------------
    |
    | Compare against:
    |
    | 1. Student's current course
    | 2. Student's preferred field
    |
    */

    if (

        $eligible_courses === ''

        ||

        strpos(
            $eligible_courses,
            'all fields'
        ) !== false

        ||

        strpos(
            $eligible_courses,
            'all courses'
        ) !== false

    ) {

        $score += 25;


        $match_reasons[] =
            "Open to multiple fields";

    }

    elseif (

        stripos(
            $eligible_courses,
            $student_course
        ) !== false

        ||

        stripos(
            $student_course,
            $eligible_courses
        ) !== false

        ||

        stripos(
            $eligible_courses,
            $preferred_field
        ) !== false

        ||

        stripos(
            $preferred_field,
            $eligible_courses
        ) !== false

    ) {

        $score += 25;


        $match_reasons[] =
            "Field of study matches";

    }

    else {

        $warnings[] =
            "Check eligible fields";

    }



    /*
    |--------------------------------------------------------------------------
    | 3. GPA — 20 POINTS
    |--------------------------------------------------------------------------
    */

    if (

        $minimum_gpa === null

        ||

        $minimum_gpa <= 0

    ) {

        $score += 20;


        $match_reasons[] =
            "No minimum GPA specified";

    }

    elseif (

        $student_gpa >=
        $minimum_gpa

    ) {

        $score += 20;


        $match_reasons[] =
            "Meets minimum GPA";

    }

    else {

        $warnings[] =
            "GPA is below the listed minimum";

    }



    /*
    |--------------------------------------------------------------------------
    | 4. NATIONALITY — 20 POINTS
    |--------------------------------------------------------------------------
    */

    if (

        $eligible_nationalities === ''

        ||

        strpos(
            $eligible_nationalities,
            'all'
        ) !== false

        ||

        strpos(
            $eligible_nationalities,
            'international'
        ) !== false

        ||

        strpos(
            $eligible_nationalities,
            $student_nationality
        ) !== false

    ) {

        $score += 20;


        $match_reasons[] =
            "Nationality is eligible";

    }

    else {

        $warnings[] =
            "Check nationality requirements";

    }



    /*
    |--------------------------------------------------------------------------
    | 5. FUNDING PREFERENCE — 10 POINTS
    |--------------------------------------------------------------------------
    */

    if (

        $preferred_funding !== ''

        &&

        $funding_type ===
            $preferred_funding

    ) {

        $score += 10;


        $match_reasons[] =
            "Matches funding preference";

    }

    else {

        /*
        Funding preference is a preference,
        not automatic disqualification.
        */

        if ($funding_type !== '') {

            $warnings[] =
                "Funding type differs from your preference";

        }

    }



    /*
    |--------------------------------------------------------------------------
    | OPTIONAL COUNTRY PREFERENCE
    |--------------------------------------------------------------------------
    |
    | Country does NOT affect the displayed 0-100 match percentage.
    |
    | If no country was selected, scholarships from every country
    | remain equally available.
    |
    */

    if (

        $preferred_country !== ''

        &&

        $scholarship_country !== ''

        &&

        (

            stripos(
                $scholarship_country,
                $preferred_country
            ) !== false

            ||

            stripos(
                $preferred_country,
                $scholarship_country
            ) !== false

        )

    ) {

        $country_bonus = 5;


        $country_preference_match =
            true;


        $match_reasons[] =
            "Matches preferred destination";

    }



    /*
    |--------------------------------------------------------------------------
    | MAKE SURE SCORE NEVER EXCEEDS 100
    |--------------------------------------------------------------------------
    */

    $score = min(
        100,
        $score
    );



    /*
    |--------------------------------------------------------------------------
    | MATCH CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($score >= 85) {

        $match_label =
            "Excellent Match";

    }

    elseif ($score >= 70) {

        $match_label =
            "Strong Match";

    }

    elseif ($score >= 55) {

        $match_label =
            "Possible Match";

    }

    else {

        $match_label =
            "Low Match";

    }



    /*
    |--------------------------------------------------------------------------
    | ONLY SHOW REASONABLE MATCHES
    |--------------------------------------------------------------------------
    |
    | Scholarships below 55 remain available on the normal
    | Scholarships page but are not shown as personalized matches.
    |
    */

    if ($score < 55) {

        continue;

    }



    /*
    |--------------------------------------------------------------------------
    | RETURN SCHOLARSHIP
    |--------------------------------------------------------------------------
    */

    $recommendations[] = [

        "scholarship_id" =>

            (int) $scholarship['scholarship_id'],


        "title" =>

            $scholarship['title'],


        "provider" =>

            $scholarship['provider'],


        "description" =>

            $scholarship['description'],


        "application_link" =>

            $scholarship['application_link'],


        "education_level" =>

            $scholarship['education_level'],


        "eligible_courses" =>

            $scholarship['eligible_courses'],


        "minimum_gpa" =>

            $scholarship['minimum_gpa'],


        "eligible_nationalities" =>

            $scholarship['eligible_nationalities'],


        "age_limit" =>

            $scholarship['age_limit'],


        "ielts_required" =>

            $scholarship['ielts_required'],


        "funding_type" =>

            $scholarship['funding_type'],


        "country" =>

            $scholarship['country'],


        "university" =>

            $scholarship['university'],


        "duration" =>

            $scholarship['duration'],


        "deadline" =>

            $scholarship['deadline'],


        /*
        -------------------------------------------------------------
        SCORE
        -------------------------------------------------------------
        */

        "match_score" =>

            $score,


        "match_label" =>

            $match_label,


        /*
        -------------------------------------------------------------
        OPTIONAL COUNTRY INFORMATION
        -------------------------------------------------------------
        */

        "country_preference_match" =>

            $country_preference_match,


        /*
        -------------------------------------------------------------
        INTERNAL SORT VALUE
        -------------------------------------------------------------
        */

        "ranking_score" =>

            $score + $country_bonus,


        /*
        -------------------------------------------------------------
        EXPLANATION
        -------------------------------------------------------------
        */

        "match_reasons" =>

            $match_reasons,


        "warnings" =>

            $warnings

    ];

}



/*
|--------------------------------------------------------------------------
| CLOSE SCHOLARSHIP STATEMENT
|--------------------------------------------------------------------------
*/

$stmt->close();



/*
|--------------------------------------------------------------------------
| SORT RECOMMENDATIONS
|--------------------------------------------------------------------------
|
| First:
|     Match score
|
| Second:
|     Optional country ranking bonus
|
| Third:
|     Earliest deadline
|
*/

usort(

    $recommendations,

    function ($a, $b) {


        /*
        -------------------------------------------------------------
        RANKING SCORE
        -------------------------------------------------------------
        */

        if (

            $a['ranking_score'] !==
            $b['ranking_score']

        ) {

            return

                $b['ranking_score']

                <=>

                $a['ranking_score'];

        }


        /*
        -------------------------------------------------------------
        DEADLINE
        -------------------------------------------------------------
        */

        $deadline_a =

            !empty($a['deadline'])

                ? strtotime(
                    $a['deadline']
                )

                : PHP_INT_MAX;


        $deadline_b =

            !empty($b['deadline'])

                ? strtotime(
                    $b['deadline']
                )

                : PHP_INT_MAX;


        return

            $deadline_a

            <=>

            $deadline_b;

    }

);



/*
|--------------------------------------------------------------------------
| REMOVE INTERNAL RANKING SCORE
|--------------------------------------------------------------------------
*/

foreach (

    $recommendations as &$recommendation

) {

    unset(

        $recommendation['ranking_score']

    );

}


/*
|--------------------------------------------------------------------------
| BREAK REFERENCE
|--------------------------------------------------------------------------
*/

unset($recommendation);



/*
|--------------------------------------------------------------------------
| FINAL RESPONSE
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => true,

    "logged_in" => true,

    "profile_complete" => true,

    "message" =>
        "Personalized scholarship recommendations loaded successfully.",


    /*
    |--------------------------------------------------------------------------
    | NUMBER OF RECOMMENDATIONS
    |--------------------------------------------------------------------------
    */

    "count" =>

        count($recommendations),


    /*
    |--------------------------------------------------------------------------
    | STUDENT INFORMATION USED FOR MATCHING
    |--------------------------------------------------------------------------
    */

    "student" => [

        "name" =>

            $personal['full_name'] ?? '',


        "nationality" =>

            $personal['nationality'] ?? '',


        /*
        -------------------------------------------------------------
        ORIGINAL EDUCATION LEVEL
        -------------------------------------------------------------
        |
        | We return the original database value to the frontend.
        | Normalization is only used internally for matching.
        |
        */

        "education_level" =>

            $education['education_level'] ?? '',


        "course" =>

            $education['course'] ?? '',


        "gpa" =>

            $education['gpa'] ?? null,


        /*
        -------------------------------------------------------------
        COUNTRY IS OPTIONAL
        -------------------------------------------------------------
        */

        "preferred_country" =>

            $preferences['preferred_country'] ?? '',


        "preferred_field" =>

            $preferences['preferred_field'] ?? '',


        "funding_preference" =>

            $preferences['funding_preference'] ?? ''

    ],


    /*
    |--------------------------------------------------------------------------
    | MATCHING INFORMATION
    |--------------------------------------------------------------------------
    */

    "matching" => [

        "education_weight" => 25,

        "field_weight" => 25,

        "gpa_weight" => 20,

        "nationality_weight" => 20,

        "funding_weight" => 10,

        "preferred_country_required" =>
            false,

        "education_level_normalization" =>
            true

    ],


    /*
    |--------------------------------------------------------------------------
    | RECOMMENDATIONS
    |--------------------------------------------------------------------------
    */

    "recommendations" =>

        $recommendations

]);


$conn->close();

?>