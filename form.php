<?php 
session_start();
require 'csrf.php';

// Auto logout after 10 minutes of inactivity
$timeout_duration = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?logout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Security check
if (
    !isset($_SESSION['is_admin']) ||
    $_SESSION['is_admin'] !== true ||
    $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] ||
    $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']
) {
    header("Location: admin_login.php");
    exit();
}

// Handle logout via ?logout=1
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?logout=1");
    exit();
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Certificate Generator</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f8;
      padding: 40px;
      margin: 0;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #2980b9;
      padding: 10px 20px;
      color: white;
    }

    .logout-btn {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }

    .container {
      max-width: 600px;
      margin: 20px auto;
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    h1 {
      text-align: center;
      color: #2c3e50;
      margin-bottom: 25px;
    }

    label {
      display: block;
      margin-bottom: 10px;
      color: #34495e;
      font-weight: 500;
    }

    input[type="text"],
    input[type="number"],
    input[type="date"],
    textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s;
    }

    input:focus,
    textarea:focus {
      border-color: #3498db;
      outline: none;
    }

    textarea {
      resize: vertical;
      min-height: 80px;
    }

    button {
      display: block;
      width: 100%;
      padding: 12px;
      background-color: #2980b9;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: #1f6391;
    }
  </style>
</head>
<body>
  <div class="top-bar">
    <div>Logged in as: <strong><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?></strong></div>
    <form method="GET" action="form.php" style="margin:0;">
      <button name="logout" value="1" class="logout-btn">🚪 Logout</button>
    </form>
  </div>

  <div class="container">
    <h1>Generate Certificate</h1>
    <form action="generate.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      
      <label>Full Name:
        <input type="text" name="full_name" required>
      </label>

      <label>Reg Number:
        <input type="text" name="reg_number" required>
      </label>

      <label>Organization Name:
        <input type="text" name="organization_name" required>
      </label>

      <label>Course Name:
        <input type="text" name="course_name" required>
      </label>

      <label>Start Date:
        <input type="date" name="start_date" required>
      </label>

      <label>End Date:
        <input type="date" name="end_date" required>
      </label>

      <label>Issue Date:
        <input type="date" name="issue_date" required>
      </label>

      <label>Total Hours:
        <input type="number" name="total_hours">
      </label>

      <label>Course Content:
        <textarea name="course_content"></textarea>
      </label>

      <label>Activities (JSON format):
        <textarea name="activities" placeholder='[{"activity":"TryHackMe","marks":20}]' required></textarea>
      </label>

      <label>Custom Appreciation Message (optional):
        <textarea name="appreciation_message" placeholder="We appreciate your commitment towards enhancing knowledge and professional development in the domain of Cyber Security."></textarea>
      </label>

      <button type="submit">Generate Certificate</button>    
      <div class="text-center mt-3">
        <a href="bulk_upload.php" class="btn btn-outline-secondary w-100">📁 Switch to Bulk Upload</a>
      </div>
    </form>
  </div>
</body>
</html>
