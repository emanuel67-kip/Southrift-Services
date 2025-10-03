<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

$username = $_SESSION['username'];
$changeMsg = "";

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $current = trim($_POST['current_password']);
  $new     = trim($_POST['new_password']);
  $confirm = trim($_POST['confirm_password']);

  if ($new !== $confirm) {
    $changeMsg = "❌ New passwords do not match.";
  } else {
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($current, $user['password'])) {
      $changeMsg = "❌ Current password is incorrect.";
    } else {
      $hashed = password_hash($new, PASSWORD_DEFAULT);
      $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
      $update->bind_param("ss", $hashed, $username);
      if ($update->execute()) {
        $changeMsg = "✅ Password changed successfully.";
      } else {
        $changeMsg = "❌ Failed to update password.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Change Password – Super Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f4f4f4;
      padding: 40px;
      margin: 0;
    }
    .container {
      max-width: 500px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #6A0DAD;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 20px;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button, a {
      margin-top: 20px;
      padding: 10px 20px;
      background: #6A0DAD;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    button:hover, a:hover {
      background: #58009c;
    }
    .msg {
      margin-top: 15px;
      background: #e6f7f1;
      padding: 10px;
      border-radius: 6px;
      color: #1b5e20;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🔒 Change Password</h2>

    <?php if ($changeMsg): ?>
      <div class="msg"><?= $changeMsg ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Current Password</label>
      <input type="password" name="current_password" required>

      <label>New Password</label>
      <input type="password" name="new_password" required>

      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required>

      <button type="submit" name="change_password">Update Password</button>
      <a href="superadmin.php">← Back to Profile</a>
    </form>
  </div>
</bo
