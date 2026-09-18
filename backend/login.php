<?php

session_start();

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../signin/signin.html");
    exit();
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    die("Please enter your username/email and password.");
}

$stmt = $conn->prepare(
    "SELECT user_id, username, email, password_hash, role
     FROM users
     WHERE username = ? OR email = ?
     LIMIT 1"
);

$stmt->bind_param("ss", $login, $login);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Invalid username/email or password.");
}

$user = $result->fetch_assoc();


if (!password_verify($password, $user['password_hash'])) {
    die("Invalid username/email or password.");
}


$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'admin') {

    header(
        "Location: ../admin/dashboard.html"
    );

} else {

    header(
        "Location: ../home/dashboard.html"
    );
}

exit();

?>