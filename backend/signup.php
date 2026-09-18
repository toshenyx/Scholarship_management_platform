<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../signup/signup.html");
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    die("Please fill in all required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

if (strlen($password) < 8) {
    die("Password must contain at least 8 characters.");
}

$check = $conn->prepare(
    "SELECT user_id FROM users WHERE username = ? OR email = ?"
);

$check->bind_param("ss", $username, $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Username or email already exists.");
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);


$stmt = $conn->prepare(
    "INSERT INTO users (username, email, password_hash)
     VALUES (?, ?, ?)"
);

$stmt->bind_param(
    "sss",
    $username,
    $email,
    $password_hash
);

if ($stmt->execute()) {

    $user_id = $stmt->insert_id;

    
    $settings = $conn->prepare(
        "INSERT INTO notification_settings (user_id)
         VALUES (?)"
    );

    $settings->bind_param("i", $user_id);
    $settings->execute();

    header("Location: ../signin/signin.html?registered=1");
    exit();

} else {

    die("Registration failed: " . $conn->error);
}

?>