<?php

session_start();

require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$user_id = $_SESSION['user_id'];

$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$dob = $_POST['dob'] ?? null;
$gender = trim($_POST['gender'] ?? '');
$nationality = trim($_POST['nationality'] ?? '');
$residence = trim($_POST['residence'] ?? '');
$workexp = trim($_POST['workexp'] ?? '');
$achievements = trim($_POST['achievements'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$stmt = $conn->prepare(
    "INSERT INTO student_profiles
    (
        user_id,
        full_name,
        phone,
        date_of_birth,
        gender,
        nationality,
        country_of_residence,
        work_experience,
        achievements,
        additional_notes
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

    ON DUPLICATE KEY UPDATE
        full_name = VALUES(full_name),
        phone = VALUES(phone),
        date_of_birth = VALUES(date_of_birth),
        gender = VALUES(gender),
        nationality = VALUES(nationality),
        country_of_residence = VALUES(country_of_residence),
        work_experience = VALUES(work_experience),
        achievements = VALUES(achievements),
        additional_notes = VALUES(additional_notes)"
);

$stmt->bind_param(
    "isssssssss",
    $user_id,
    $fullname,
    $phone,
    $dob,
    $gender,
    $nationality,
    $residence,
    $workexp,
    $achievements,
    $notes
);

if ($stmt->execute()) {

    if (!empty($_POST['email'])) {

        $email = trim($_POST['email']);

        $email_stmt = $conn->prepare(
            "UPDATE users SET email = ? WHERE user_id = ?"
        );

        $email_stmt->bind_param("si", $email, $user_id);
        $email_stmt->execute();
    }

    header("Location: ../profile/profile2.html?saved=1");
    exit();

} else {

    die("Failed to save profile: " . $conn->error);
}

?>