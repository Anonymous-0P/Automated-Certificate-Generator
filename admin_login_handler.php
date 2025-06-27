<?php
session_start();
require 'db.php';
require 'csrf.php';

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token");
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $mysqli->prepare("SELECT password_hash FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($hash);
    $stmt->fetch();

    if (password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $username;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        header("Location: form.php");
        exit();
    }
}

echo "<script>alert('Invalid credentials');window.location.href='admin_login.php';</script>";
?>
