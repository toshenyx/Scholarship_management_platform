<?php

session_start();

require_once "db.php";

header(
    "Content-Type: application/json; charset=utf-8"
);


if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "message" => "Please log in first."
    ]);

    exit();
}


$user_id =
    (int) $_SESSION['user_id'];


$scholarship_id =
    (int) (
        $_POST['scholarship_id']
        ?? 0
    );


if ($scholarship_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Invalid scholarship."
    ]);

    exit();
}



$checkScholarship =
    $conn->prepare(
        "SELECT scholarship_id
         FROM scholarships
         WHERE scholarship_id = ?
         LIMIT 1"
    );


if (!$checkScholarship) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Unable to verify scholarship."
    ]);

    exit();
}


$checkScholarship->bind_param(
    "i",
    $scholarship_id
);


$checkScholarship->execute();


$scholarshipExists =
    $checkScholarship
        ->get_result()
        ->fetch_assoc();


$checkScholarship->close();


if (!$scholarshipExists) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Scholarship was not found."
    ]);

    exit();
}



$checkApplication =
    $conn->prepare(
        "SELECT application_id
         FROM applications
         WHERE user_id = ?
         AND scholarship_id = ?
         LIMIT 1"
    );


if (!$checkApplication) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Unable to check application."
    ]);

    exit();
}


$checkApplication->bind_param(
    "ii",
    $user_id,
    $scholarship_id
);


$checkApplication->execute();


$existingApplication =
    $checkApplication
        ->get_result()
        ->fetch_assoc();


$checkApplication->close();



if ($existingApplication) {

    echo json_encode([
        "success" => true,
        "logged_in" => true,
        "already_recorded" => true,
        "message" =>
            "Application already recorded."
    ]);

    exit();
}



$stmt =
    $conn->prepare(
        "INSERT INTO applications
        (
            user_id,
            scholarship_id,
            status,
            applied_at
        )
        VALUES
        (
            ?,
            ?,
            'Applied',
            NOW()
        )"
    );


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Unable to prepare application."
    ]);

    exit();
}


$stmt->bind_param(
    "ii",
    $user_id,
    $scholarship_id
);


if ($stmt->execute()) {

    $application_id =
        $stmt->insert_id;


    echo json_encode([
        "success" => true,
        "logged_in" => true,
        "already_recorded" => false,
        "application_id" =>
            $application_id,
        "scholarship_id" =>
            $scholarship_id,
        "status" => "Applied",
        "message" =>
            "Application recorded successfully."
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "message" =>
            "Could not record application."
    ]);
}


$stmt->close();

$conn->close();

?>