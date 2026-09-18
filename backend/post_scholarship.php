<?php

session_start();

require_once "db.php";


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
| Both students and administrators can post scholarships.
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../signin/signin.html");
    exit();
}


$posted_by = (int) $_SESSION['user_id'];

$role = $_SESSION['role'] ?? 'student';


/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile/postscholarship.html");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$title = trim($_POST['title'] ?? '');

$provider = trim($_POST['provider'] ?? '');

$application_link = trim(
    $_POST['application_link'] ?? ''
);

$description = trim(
    $_POST['description'] ?? ''
);

$education_level = trim(
    $_POST['education_level'] ?? ''
);

$eligible_courses = trim(
    $_POST['eligible_courses'] ?? ''
);

$minimum_gpa = trim(
    $_POST['minimum_gpa'] ?? ''
);

$eligible_nationalities = trim(
    $_POST['eligible_nationalities'] ?? ''
);

$age_limit = trim(
    $_POST['age_limit'] ?? ''
);

$ielts_required = isset(
    $_POST['ielts_required']
)
    ? (int) $_POST['ielts_required']
    : 0;

$funding_type = trim(
    $_POST['funding_type'] ?? ''
);

$country = trim(
    $_POST['country'] ?? ''
);

$university = trim(
    $_POST['university'] ?? ''
);

$duration = trim(
    $_POST['duration'] ?? ''
);

$deadline = !empty(
    $_POST['deadline']
)
    ? $_POST['deadline']
    : null;

$benefits = $_POST['benefits'] ?? [];


/*
|--------------------------------------------------------------------------
| VALIDATE REQUIRED FIELDS
|--------------------------------------------------------------------------
*/

if ($title === '') {
    die("Scholarship title is required.");
}

if ($provider === '') {
    die("Scholarship provider is required.");
}

if ($application_link === '') {
    die("Scholarship application link is required.");
}

if (
    !filter_var(
        $application_link,
        FILTER_VALIDATE_URL
    )
) {
    die("Invalid scholarship application link.");
}

if ($education_level === '') {
    die("Education level is required.");
}

if ($eligible_courses === '') {
    die("Eligible courses are required.");
}

if ($eligible_nationalities === '') {
    die("Eligible nationalities are required.");
}

if ($funding_type === '') {
    die("Funding type is required.");
}

if ($country === '') {
    die("Country is required.");
}

if ($deadline === null) {
    die("Application deadline is required.");
}


/*
|--------------------------------------------------------------------------
| FUNDING TYPE VALIDATION
|--------------------------------------------------------------------------
*/

$allowed_funding_types = [
    'fully_funded',
    'partially_funded',
    'tuition_only'
];

if (
    !in_array(
        $funding_type,
        $allowed_funding_types,
        true
    )
) {
    die("Invalid funding type.");
}


/*
|--------------------------------------------------------------------------
| OPTIONAL NUMERIC VALUES
|--------------------------------------------------------------------------
*/

$minimum_gpa =
    $minimum_gpa !== ''
        ? (float) $minimum_gpa
        : null;

$age_limit =
    $age_limit !== ''
        ? (int) $age_limit
        : null;


/*
|--------------------------------------------------------------------------
| VERIFICATION
|--------------------------------------------------------------------------
|
| Admin posts:
|     Approved immediately.
|
| Student posts:
|     Pending approval.
*/

if ($role === 'admin') {

    $verification_status = 'approved';
    $verified_by = $posted_by;
    $verified_at = date('Y-m-d H:i:s');

} else {

    $verification_status = 'pending';
    $verified_by = null;
    $verified_at = null;

}


/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();


try {


    /*
    |--------------------------------------------------------------------------
    | INSERT SCHOLARSHIP
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO scholarships
        (
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
            verification_status,
            verified_by,
            verified_at,
            posted_by
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )"
    );


    if (!$stmt) {
        throw new Exception(
            "Prepare failed: " . $conn->error
        );
    }


    $stmt->bind_param(
        "ssssssdsiissssssisi",
        $title,
        $provider,
        $application_link,
        $description,
        $education_level,
        $eligible_courses,
        $minimum_gpa,
        $eligible_nationalities,
        $age_limit,
        $ielts_required,
        $funding_type,
        $country,
        $university,
        $duration,
        $deadline,
        $verification_status,
        $verified_by,
        $verified_at,
        $posted_by
    );


    if (!$stmt->execute()) {
        throw new Exception(
            "Failed to save scholarship: "
            . $stmt->error
        );
    }


    $scholarship_id =
        $stmt->insert_id;


    $stmt->close();



    /*
    |--------------------------------------------------------------------------
    | SAVE BENEFITS
    |--------------------------------------------------------------------------
    */

    if (
        is_array($benefits) &&
        count($benefits) > 0
    ) {


        $benefit_map = [

            'tuition' =>
                'tuition',

            'accommodation' =>
                'accommodation',

            'monthly_stipend' =>
                'monthly_stipend',

            'airfare' =>
                'airfare',

            'medical_insurance' =>
                'medical_insurance',

            'books_allowance' =>
                'books'

        ];


        $benefit_stmt =
            $conn->prepare(
                "INSERT INTO scholarship_benefits
                (
                    scholarship_id,
                    benefit_type
                )
                VALUES (?, ?)"
            );


        if (!$benefit_stmt) {
            throw new Exception(
                "Benefit prepare failed: "
                . $conn->error
            );
        }


        foreach ($benefits as $benefit) {


            $benefit =
                strtolower(
                    trim($benefit)
                );


            if (
                !isset(
                    $benefit_map[$benefit]
                )
            ) {
                continue;
            }


            $benefit_type =
                $benefit_map[$benefit];


            $benefit_stmt->bind_param(
                "is",
                $scholarship_id,
                $benefit_type
            );


            if (
                !$benefit_stmt->execute()
            ) {
                throw new Exception(
                    "Failed to save scholarship benefit."
                );
            }

        }


        $benefit_stmt->close();

    }



    /*
    |--------------------------------------------------------------------------
    | COMMIT CHANGES
    |--------------------------------------------------------------------------
    */

    $conn->commit();



    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO SCHOLARSHIPS PAGE
    |--------------------------------------------------------------------------
    */

    if ($role === 'admin') {

        header(
            "Location: ../home/scholarships.html?posted=1"
        );

    } else {

        header(
            "Location: ../home/scholarships.html?posted=pending"
        );

    }


    exit();


} catch (Exception $e) {


    $conn->rollback();


    die(
        "Unable to post scholarship: "
        . $e->getMessage()
    );

}
?>