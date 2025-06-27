<?php
session_start();
require 'db.php';
require 'csrf.php';

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ✅ Max 3 admin limit check
    $count_result = $mysqli->query("SELECT COUNT(*) as total FROM admins");
    $admin_count = $count_result->fetch_assoc()['total'];
    if ($admin_count >= 3) {
        echo "<script>alert('❌ Maximum of 3 admins allowed.'); window.location.href='admin_register.php';</script>";
        exit();
    }

    // ✅ Check if username already exists
    $stmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Username already exists!'); window.location.href='admin_register.php';</script>";
        exit();
    }

    // ✅ Insert new admin
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hash);

    if ($stmt->execute()) {
        // ✅ Set session after successful registration
        $_SESSION['is_admin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['last_activity'] = time(); // for auto logout

        echo "<script>alert('Admin registered successfully!'); window.location.href='form.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error registering admin.'); window.location.href='admin_register.php';</script>";
        exit();
    }
}
?>
