<?php

session_start();

header("Content-Type: application/json");

require_once "db.php";


/*CHECK SESSION*/

if (!isset($_SESSION['user_id'])) {

    echo json_encode([

        "success" => false,

        "logged_in" => false,

        "completed" => false,

        "profile_complete" => false,

        "message" => "No user session found.",

        "next_step" => "login"

    ]);

    exit();
}


$user_id = (int) $_SESSION['user_id'];



/*CHECK USER*/

$stmt = $conn->prepare(

    "SELECT
        user_id,
        username,
        email,
        role

     FROM users

     WHERE user_id = ?

     LIMIT 1"

);


if (!$stmt) {

    echo json_encode([

        "success" => false,

        "logged_in" => true,

        "completed" => false,

        "profile_complete" => false,

        "message" => "Unable to check user account.",

        "next_step" => "login"

    ]);

    exit();

}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$user = $stmt
    ->get_result()
    ->fetch_assoc();


$stmt->close();



if (!$user) {

    echo json_encode([

        "success" => false,

        "logged_in" => false,

        "completed" => false,

        "profile_complete" => false,

        "message" => "User does not exist.",

        "next_step" => "login"

    ]);

    exit();

}



/*PROFILE 1
| Keep your original check:
| Does a student_profiles record exist?*/

$stmt = $conn->prepare(

    "SELECT COUNT(*) AS total

     FROM student_profiles

     WHERE user_id = ?"

);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$row = $stmt
    ->get_result()
    ->fetch_assoc();


$has_personal =
    (int) $row['total'] > 0;


$stmt->close();



/*PROFILE 2/ Keep your original education check.*/

$stmt = $conn->prepare(

    "SELECT COUNT(*) AS total

     FROM education

     WHERE user_id = ?"

);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$row = $stmt
    ->get_result()
    ->fetch_assoc();


$has_education =
    (int) $row['total'] > 0;


$stmt->close();



/*PROFILE 3/Keep your original preferences check.*/

$stmt = $conn->prepare(

    "SELECT COUNT(*) AS total

     FROM student_preferences

     WHERE user_id = ?"

);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$row = $stmt
    ->get_result()
    ->fetch_assoc();


$has_preferences =
    (int) $row['total'] > 0;


$stmt->close();



/*ORIGINAL COMPLETION CHECK*/

$completed =

    $has_personal &&

    $has_education &&

    $has_preferences;



/*ORIGINAL NEXT STEP LOGIC*/

if ($completed) {

    $next_step = "summary";

}
elseif (!$has_personal) {

    $next_step = "profile1";

}
elseif (!$has_education) {

    $next_step = "profile2";

}
else {

    $next_step = "profile3";

}



/* GET PERSONAL PROFILE DETAILS*/

$personal = null;


if ($has_personal) {

    $stmt = $conn->prepare(

        "SELECT

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

         LIMIT 1"

    );


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );


        $stmt->execute();


        $personal = $stmt
            ->get_result()
            ->fetch_assoc();


        $stmt->close();

    }

}



/*GET EDUCATION DETAILS*/

$education = null;


if ($has_education) {

    $stmt = $conn->prepare(

        "SELECT

            education_id,
            education_level,
            graduation_year,
            institution,
            gpa,
            course,
            academic_achievements

         FROM education

         WHERE user_id = ?

         ORDER BY education_id DESC

         LIMIT 1"

    );


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );


        $stmt->execute();


        $education = $stmt
            ->get_result()
            ->fetch_assoc();


        $stmt->close();

    }

}



/*GET STUDENT PREFERENCES*/

$preferences = null;


if ($has_preferences) {

    $stmt = $conn->prepare(

        "SELECT

            preference_id,
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

         LIMIT 1"

    );


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );


        $stmt->execute();


        $preferences = $stmt
            ->get_result()
            ->fetch_assoc();


        $stmt->close();

    }

}



/*CHECK IMPORTANT MATCHING FIELDS*/

$missing_fields = [];



/*PERSONAL INFORMATION CHECK*/

if (!$personal) {

    $missing_fields[] =
        "Personal information";

}
else {

    if (
        empty(
            trim(
                $personal['full_name'] ?? ''
            )
        )
    ) {

        $missing_fields[] =
            "Full name";

    }


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


    if (
        empty(
            trim(
                $personal['country_of_residence'] ?? ''
            )
        )
    ) {

        $missing_fields[] =
            "Country of residence";

    }

}



/*EDUCATION INFORMATION CHECK*/

if (!$education) {

    $missing_fields[] =
        "Education information";

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
                $education['institution'] ?? ''
            )
        )
    ) {

        $missing_fields[] =
            "Institution";

    }


    if (
        empty(
            trim(
                $education['course'] ?? ''
            )
        )
    ) {

        $missing_fields[] =
            "Course / field of study";

    }


    /*GPA CHECK*/

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



/*SCHOLARSHIP PREFERENCES CHECK*/

if (!$preferences) {

    $missing_fields[] =
        "Scholarship preferences";

}
else {

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

}



/* determine if profile is completed to access recommendations and file download*/

$profile_complete =

    $completed &&

    count($missing_fields) === 0;



/*feature acess: community, recommendations and file download*/

$feature_access = [

    "community" => true,

    "scholarship_matching" =>
        $profile_complete,

    "pdf_export" =>
        $profile_complete

];



/*profile completion percentage*/

$completed_sections = 0;


if ($has_personal) {

    $completed_sections++;

}


if ($has_education) {

    $completed_sections++;

}


if ($has_preferences) {

    $completed_sections++;

}


$profile_percentage =

    (int) round(

        ($completed_sections / 3) * 100

    );



/*MESSAGE*/

if ($profile_complete) {

    $message =
        "Profile complete. Personalized scholarship features are unlocked.";

}
elseif (!$completed) {

    $message =
        "Complete all profile sections to unlock scholarship matching and PDF export.";

}
else {

    $message =
        "Your profile sections exist, but some information required for scholarship matching is missing.";

}



echo json_encode([

    "success" => true,

    "logged_in" => true,

    "user_id" => $user_id,

    "username" => $user['username'],

    "email" => $user['email'],

    "role" => $user['role'] ?? "student",


    /*EXISTING COMPLETION DATA*/

    "completed" => $completed,

    "personal_completed" =>
        $has_personal,

    "education_completed" =>
        $has_education,

    "preferences_completed" =>
        $has_preferences,

    "next_step" =>
        $next_step,


    /*NEW PROFILE ACCESS DATA*/

    "profile_complete" =>
        $profile_complete,

    "profile_percentage" =>
        $profile_percentage,

    "missing_fields" =>
        $missing_fields,

    "feature_access" =>
        $feature_access,

    "message" =>
        $message

]);


$conn->close();

?>