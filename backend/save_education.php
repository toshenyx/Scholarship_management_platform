<?php

session_start();

require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$user_id = $_SESSION['user_id'];

$education_level = trim($_POST['edu-level'] ?? '');
$graduation_year = trim($_POST['grad-year'] ?? '');
$institution = trim($_POST['institution'] ?? '');
$gpa = trim($_POST['gpa'] ?? '');
$course = trim($_POST['course'] ?? '');
$achievements = trim($_POST['achievements'] ?? '');

if ($gpa === '') {
    $gpa = null;
}

$stmt = $conn->prepare(
    "INSERT INTO education
    (
        user_id,
        education_level,
        graduation_year,
        institution,
        gpa,
        course,
        academic_achievements
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "isssdss",
    $user_id,
    $education_level,
    $graduation_year,
    $institution,
    $gpa,
    $course,
    $achievements
);

if (!$stmt->execute()) {
    die("Failed to save education: " . $conn->error);
}


$ielts = trim($_POST['ielts'] ?? '');

if ($ielts !== '') {

    $stmt = $conn->prepare(
        "INSERT INTO proficiency_tests
        (user_id, test_type, score)
        VALUES (?, 'IELTS', ?)"
    );

    $stmt->bind_param("is", $user_id, $ielts);
    $stmt->execute();
}


$toefl = trim($_POST['toefl'] ?? '');

if ($toefl !== '') {

    $stmt = $conn->prepare(
        "INSERT INTO proficiency_tests
        (user_id, test_type, score)
        VALUES (?, 'TOEFL', ?)"
    );

    $stmt->bind_param("is", $user_id, $toefl);
    $stmt->execute();
}


$language = trim($_POST['languages'] ?? '');

if ($language !== '') {

    // Check if language exists
    $stmt = $conn->prepare(
        "SELECT language_id
         FROM languages
         WHERE language_name = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $language);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $language_row = $result->fetch_assoc();
        $language_id = $language_row['language_id'];

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO languages (language_name)
             VALUES (?)"
        );

        $stmt->bind_param("s", $language);
        $stmt->execute();

        $language_id = $stmt->insert_id;
    }

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO student_languages
        (user_id, language_id)
        VALUES (?, ?)"
    );

    $stmt->bind_param("ii", $user_id, $language_id);
    $stmt->execute();
}


$skill = trim($_POST['skills'] ?? '');

if ($skill !== '') {

    $stmt = $conn->prepare(
        "SELECT skill_id
         FROM skills
         WHERE skill_name = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $skill);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $skill_row = $result->fetch_assoc();
        $skill_id = $skill_row['skill_id'];

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO skills (skill_name)
             VALUES (?)"
        );

        $stmt->bind_param("s", $skill);
        $stmt->execute();

        $skill_id = $stmt->insert_id;
    }

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO student_skills
        (user_id, skill_id)
        VALUES (?, ?)"
    );

    $stmt->bind_param("ii", $user_id, $skill_id);
    $stmt->execute();
}

function saveDocument($file, $user_id, $document_type, $conn) 
{ 
    if (
         !isset($file) || 
         $file['error'] === UPLOAD_ERR_NO_FILE 
        ) {
            return; 
          } 
    if ($file['error'] !== UPLOAD_ERR_OK) 
        { 
          die("File upload failed."); 
        } 
    $allowed_extensions = ['pdf', 'doc', 'docx']; 
    
    $original_name = $file['name'];
    
    $extension = strtolower( 
        pathinfo($original_name, PATHINFO_EXTENSION)
    ); 
    
    if (!in_array($extension, $allowed_extensions))
         {
             die("Only PDF, DOC and DOCX files are allowed."); 
         }
    $upload_directory = __DIR__ . "/uploads/documents/";
    
    if (!is_dir($upload_directory))
         { 
            mkdir($upload_directory, 0777, true);
         } 
    $new_name = 
        $user_id . "_" .
        uniqid() . "." . 
        $extension; 
        
    $destination = 
        $upload_directory . $new_name; 
        
    if (!move_uploaded_file( 
        $file['tmp_name'], 
        $destination
        )
        ) { die("Could not upload file."); } $file_path = "backend/uploads/documents/" . $new_name; $stmt = $conn->prepare( "INSERT INTO documents (user_id, document_type, file_name, file_path) VALUES (?, ?, ?, ?)" ); $stmt->bind_param( "isss", $user_id, $document_type, $original_name, $file_path ); $stmt->execute(); $stmt->close(); } /* Transcript */ if (isset($_FILES['transcript'])) { saveDocument( $_FILES['transcript'], $user_id, 'transcript', $conn ); } /* Certificate */ if (isset($_FILES['certificate'])) { saveDocument( $_FILES['certificate'], $user_id, 'certificate', $conn ); } /* Detect SAVE or NEXT button */ $action = $_POST['action'] ?? 'next'; if ($action === 'save') { header("Location: ../profile/profile2.html?saved=1"); 
    exit(); 
} 


header("Location: ../profile/profile3.html?saved=1");
exit();

?>