<?php

session_start();

header("Content-Type: application/json");

require_once "db.php";


if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "User not logged in."
    ]);

    exit();
}


$user_id = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| PERSONAL INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        u.username,
        u.email,
        p.full_name,
        p.phone,
        p.date_of_birth,
        p.gender,
        p.nationality,
        p.country_of_residence,
        p.work_experience,
        p.achievements,
        p.additional_notes

     FROM users u

     LEFT JOIN student_profiles p
        ON u.user_id = p.user_id

     WHERE u.user_id = ?"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$personal = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| EDUCATION
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
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

$stmt->bind_param("i", $user_id);

$stmt->execute();

$education = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| SKILLS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT s.skill_name

     FROM student_skills ss

     INNER JOIN skills s
        ON ss.skill_id = s.skill_id

     WHERE ss.user_id = ?"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$skills = [];

while ($row = $result->fetch_assoc()) {

    $skills[] = $row['skill_name'];

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| LANGUAGES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        l.language_name,
        sl.proficiency

     FROM student_languages sl

     INNER JOIN languages l
        ON sl.language_id = l.language_id

     WHERE sl.user_id = ?"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$languages = [];

while ($row = $result->fetch_assoc()) {

    $languages[] = [
        "name" => $row['language_name'],
        "proficiency" => $row['proficiency']
    ];

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| IELTS / TOEFL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        test_type,
        score

     FROM proficiency_tests

     WHERE user_id = ?

     ORDER BY test_id DESC"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$tests = [];

while ($row = $result->fetch_assoc()) {

    if (!isset($tests[$row['test_type']])) {

        $tests[$row['test_type']] = $row['score'];

    }

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| DOCUMENTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        document_type,
        file_name,
        file_path

     FROM documents

     WHERE user_id = ?

     ORDER BY uploaded_at DESC"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$documents = [];

while ($row = $result->fetch_assoc()) {

    if (!isset($documents[$row['document_type']])) {

        $documents[$row['document_type']] = [

            "file_name" => $row['file_name'],

            "file_path" => $row['file_path']

        ];

    }

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| SCHOLARSHIP PREFERENCES
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
        preferred_field,
        other_requirements

     FROM student_preferences

     WHERE user_id = ?

     LIMIT 1"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$preferences = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


$conn->close();


/*
|--------------------------------------------------------------------------
| SEND EVERYTHING TO JAVASCRIPT
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => true,

    "personal" => $personal ?: [],

    "education" => $education ?: [],

    "skills" => $skills,

    "languages" => $languages,

    "tests" => $tests,

    "documents" => $documents,

    "preferences" => $preferences ?: []

]);

?>