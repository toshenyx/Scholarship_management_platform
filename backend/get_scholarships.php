<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "message" => "You must be logged in."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| GET APPROVED SCHOLARSHIPS
|--------------------------------------------------------------------------
|
| Only approved scholarships appear publicly.
*/

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
        s.posted_by,
        s.created_at,

        u.username AS posted_by_username,
        u.role AS posted_by_role

    FROM scholarships s

    LEFT JOIN users u
        ON s.posted_by = u.user_id

    WHERE 
        s.verification_status IN ('approved')

    ORDER BY
        s.created_at DESC
";


$result = $conn->query($sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to load scholarships.",
        "error" => $conn->error
    ]);

    exit();
}


$scholarships = [];


/*
|--------------------------------------------------------------------------
| LOOP THROUGH SCHOLARSHIPS
|--------------------------------------------------------------------------
*/

while ($row = $result->fetch_assoc()) {


    $scholarship_id =
        (int) $row['scholarship_id'];


    /*
    |--------------------------------------------------------------------------
    | GET BENEFITS
    |--------------------------------------------------------------------------
    */

    $benefits = [];


    $benefit_stmt =
        $conn->prepare(
            "SELECT
                benefit_type,
                description
             FROM scholarship_benefits
             WHERE scholarship_id = ?"
        );


    if ($benefit_stmt) {


        $benefit_stmt->bind_param(
            "i",
            $scholarship_id
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

    }


    /*
    |--------------------------------------------------------------------------
    | ADD BENEFITS
    |--------------------------------------------------------------------------
    */

    $row['benefits'] = $benefits;


    /*
    |--------------------------------------------------------------------------
    | FIX DATA TYPES
    |--------------------------------------------------------------------------
    */

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


    $row['posted_by'] =
        $row['posted_by'] !== null
            ? (int) $row['posted_by']
            : null;


    $scholarships[] = $row;

}


/*
|--------------------------------------------------------------------------
| RETURN JSON
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "logged_in" => true,
    "count" => count($scholarships),
    "scholarships" => $scholarships
]);

?>