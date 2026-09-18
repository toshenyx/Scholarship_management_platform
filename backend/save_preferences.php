<?php

session_start();

require_once "db.php";


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        "Location: ../signin/signin.html"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| CHECK REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: ../profile/profile3.html"
    );

    exit();

}



$user_id =
    (int) $_SESSION['user_id'];



/*
|--------------------------------------------------------------------------
| GET FORM VALUES
|--------------------------------------------------------------------------
*/

$preferences =

    $_POST['preference'] ?? [];



$preferred_field = trim(

    $_POST['preferred-field'] ?? ''

);



$work_study =

    $_POST['work-study'] ?? 'no';



$research_grants =

    $_POST['research-grants'] ?? 'no';



$short_courses =

    $_POST['short-courses'] ?? 'no';



$exchange_programmes =

    $_POST['exchange-programmes'] ?? 'no';



$living_stipend =

    $_POST['living-stipend'] ?? 'no';



$other_requirements = trim(

    $_POST['other-requirements'] ?? ''

);



$action =

    $_POST['action'] ?? 'complete';



/*
|--------------------------------------------------------------------------
| VALIDATE PREFERRED FIELD
|--------------------------------------------------------------------------
|
| Preferred field is required because it will be used by the
| scholarship recommendation system.
|
*/

if ($preferred_field === '') {

    header(
        "Location: ../profile/profile3.html?error=preferred_field"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| YES / NO TO 1 / 0
|--------------------------------------------------------------------------
*/

$work_study_value =

    $work_study === 'yes'
        ? 1
        : 0;



$research_grants_value =

    $research_grants === 'yes'
        ? 1
        : 0;



$short_courses_value =

    $short_courses === 'yes'
        ? 1
        : 0;



$exchange_programmes_value =

    $exchange_programmes === 'yes'
        ? 1
        : 0;



$living_stipend_value =

    $living_stipend === 'yes'
        ? 1
        : 0;



/*
|--------------------------------------------------------------------------
| FUNDING PREFERENCE
|--------------------------------------------------------------------------
*/

$funding_preference = null;



if (
    in_array(
        'fully-funded',
        $preferences,
        true
    )
) {

    $funding_preference =
        'fully_funded';

}

elseif (
    in_array(
        'partial-funding',
        $preferences,
        true
    )
) {

    $funding_preference =
        'partially_funded';

}

elseif (
    in_array(
        'tuition-only',
        $preferences,
        true
    )
) {

    $funding_preference =
        'tuition_only';

}



/*
|--------------------------------------------------------------------------
| VALIDATE FUNDING PREFERENCE
|--------------------------------------------------------------------------
*/

if ($funding_preference === null) {

    header(
        "Location: ../profile/profile3.html?error=funding_preference"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| PREFERRED COUNTRY
|--------------------------------------------------------------------------
|
| We intentionally DO NOT require a preferred country.
|
| The database column can remain available for future use,
| but a blank value means:
|
| "Show scholarship opportunities from all countries."
|
*/

$preferred_country = null;



/*
|--------------------------------------------------------------------------
| CHECK EXISTING RECORD
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(

    "SELECT preference_id

     FROM student_preferences

     WHERE user_id = ?

     LIMIT 1"

);



if (!$stmt) {

    die(
        "Database error: " .
        $conn->error
    );

}



$stmt->bind_param(

    "i",

    $user_id

);



$stmt->execute();



$result =

    $stmt->get_result();



$exists =

    $result->num_rows > 0;



$stmt->close();



/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

if ($exists) {


    $stmt = $conn->prepare(

        "UPDATE student_preferences

         SET

            funding_preference = ?,

            work_study = ?,

            research_grants = ?,

            short_courses = ?,

            exchange_programmes = ?,

            living_stipend = ?,

            preferred_country = ?,

            preferred_field = ?,

            other_requirements = ?

         WHERE user_id = ?"

    );



    if (!$stmt) {

        die(
            "Database error: " .
            $conn->error
        );

    }



    $stmt->bind_param(

        "siiiiisssi",

        $funding_preference,

        $work_study_value,

        $research_grants_value,

        $short_courses_value,

        $exchange_programmes_value,

        $living_stipend_value,

        $preferred_country,

        $preferred_field,

        $other_requirements,

        $user_id

    );



    $stmt->execute();



    $stmt->close();

}



/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

else {


    $stmt = $conn->prepare(

        "INSERT INTO student_preferences

        (

            user_id,

            funding_preference,

            work_study,

            research_grants,

            short_courses,

            exchange_programmes,

            living_stipend,

            preferred_country,

            preferred_field,

            other_requirements

        )

        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"

    );



    if (!$stmt) {

        die(
            "Database error: " .
            $conn->error
        );

    }



    $stmt->bind_param(

        "isiiiiisss",

        $user_id,

        $funding_preference,

        $work_study_value,

        $research_grants_value,

        $short_courses_value,

        $exchange_programmes_value,

        $living_stipend_value,

        $preferred_country,

        $preferred_field,

        $other_requirements

    );



    $stmt->execute();



    $stmt->close();

}



$conn->close();



/*
|--------------------------------------------------------------------------
| SAVE ONLY
|--------------------------------------------------------------------------
*/

if ($action === 'save') {

    header(
        "Location: ../profile/profile3.html?saved=1"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| REGISTRATION COMPLETE
|--------------------------------------------------------------------------
*/

header(

    "Location: ../profile/profile_summary.html?completed=1"

);


exit();

?>