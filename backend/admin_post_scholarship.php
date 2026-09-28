<?php

session_start();

require_once "db.php";

header("Content-Type: application/json; charset=utf-8");


function sendResponse(
    bool $success,
    string $message,
    array $extra = [],
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        )
    );

    exit();
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    sendResponse(
        false,
        "Invalid request method.",
        [],
        405
    );
}


if (!isset($_SESSION["user_id"])) {

    sendResponse(
        false,
        "Please log in first.",
        [
            "logged_in" => false
        ],
        401
    );
}


$userId = (int) $_SESSION["user_id"];


$adminCheck = $conn->prepare(
    "SELECT user_id, username, role
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);


if (!$adminCheck) {

    sendResponse(
        false,
        "Could not verify administrator.",
        [],
        500
    );
}


$adminCheck->bind_param(
    "i",
    $userId
);


$adminCheck->execute();


$adminResult =
    $adminCheck->get_result();


$admin =
    $adminResult->fetch_assoc();


$adminCheck->close();


if (!$admin) {

    sendResponse(
        false,
        "User account was not found.",
        [],
        404
    );
}


if (
    strtolower(
        trim($admin["role"] ?? "")
    ) !== "admin"
) {

    sendResponse(
        false,
        "Administrator access required.",
        [
            "authorized" => false
        ],
        403
    );
}


$title =
    trim(
        $_POST["title"] ?? ""
    );


$provider =
    trim(
        $_POST["provider"] ?? ""
    );


$description =
    trim(
        $_POST["description"] ?? ""
    );


$country =
    trim(
        $_POST["country"] ?? ""
    );


$university =
    trim(
        $_POST["university"] ?? ""
    );


$educationLevel =
    trim(
        $_POST["education_level"] ?? ""
    );


$eligibleCourses =
    trim(
        $_POST["eligible_courses"] ?? ""
    );


$eligibleNationalities =
    trim(
        $_POST["eligible_nationalities"] ?? ""
    );


$fundingType =
    trim(
        $_POST["funding_type"] ?? ""
    );


$duration =
    trim(
        $_POST["duration"] ?? ""
    );


$deadline =
    trim(
        $_POST["deadline"] ?? ""
    );


$applicationLink =
    trim(
        $_POST["application_link"] ?? ""
    );


$requirements =
    trim(
        $_POST["requirements"] ?? ""
    );


if ($title === "") {

    sendResponse(
        false,
        "Scholarship title is required.",
        [],
        400
    );
}


if ($provider === "") {

    sendResponse(
        false,
        "Scholarship provider is required.",
        [],
        400
    );
}


if ($description === "") {

    sendResponse(
        false,
        "Scholarship description is required.",
        [],
        400
    );
}


if ($country === "") {

    sendResponse(
        false,
        "Country is required.",
        [],
        400
    );
}


if ($deadline === "") {

    sendResponse(
        false,
        "Application deadline is required.",
        [],
        400
    );
}


if ($applicationLink === "") {

    sendResponse(
        false,
        "Application link is required.",
        [],
        400
    );
}


if (
    !filter_var(
        $applicationLink,
        FILTER_VALIDATE_URL
    )
) {

    sendResponse(
        false,
        "Please enter a valid application link.",
        [],
        400
    );
}


$deadlineDate =
    DateTime::createFromFormat(
        "Y-m-d",
        $deadline
    );


if (
    !$deadlineDate ||
    $deadlineDate->format("Y-m-d") !==
        $deadline
) {

    sendResponse(
        false,
        "Please enter a valid deadline.",
        [],
        400
    );
}


$verificationStatus =
    "approved";


$postedBy =
    $userId;


$stmt = $conn->prepare(
    "INSERT INTO scholarships
    (
        title,
        provider,
        description,
        country,
        university,
        education_level,
        eligible_courses,
        eligible_nationalities,
        funding_type,
        duration,
        deadline,
        application_link,
        requirements,
        verification_status,
        posted_by
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )"
);


if (!$stmt) {

    sendResponse(
        false,
        "Could not prepare scholarship publication.",
        [
            "error" => $conn->error
        ],
        500
    );
}


$stmt->bind_param(
    "ssssssssssssssi",

    $title,
    $provider,
    $description,
    $country,
    $university,
    $educationLevel,
    $eligibleCourses,
    $eligibleNationalities,
    $fundingType,
    $duration,
    $deadline,
    $applicationLink,
    $requirements,
    $verificationStatus,
    $postedBy
);


if (!$stmt->execute()) {

    $error =
        $stmt->error;


    $stmt->close();

    $conn->close();


    sendResponse(
        false,
        "Could not publish scholarship.",
        [
            "error" => $error
        ],
        500
    );
}


$scholarshipId =
    $stmt->insert_id;


$stmt->close();


$logStatement =
    $conn->prepare(
        "INSERT INTO admin_actions
        (
            admin_id,
            action_type,
            target_type,
            target_id,
            description
        )
        VALUES
        (
            ?,
            'post_scholarship',
            'scholarship',
            ?,
            ?
        )"
    );


if ($logStatement) {

    $logDescription =
        "Published scholarship: " .
        $title;


    $logStatement->bind_param(
        "iis",
        $userId,
        $scholarshipId,
        $logDescription
    );


    $logStatement->execute();

    $logStatement->close();
}


$conn->close();


sendResponse(
    true,
    "Scholarship published successfully.",
    [
        "scholarship_id" =>
            $scholarshipId,

        "verification_status" =>
            $verificationStatus
    ],
    201
);

?>