<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

$name = $_SESSION['username'] ?? '';
$success = $error = "";

// Fetch admin details
$stmt = $conn->prepare("SELECT name, phone, email, password FROM users WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$stmt->bind_result($name, $phone, $email, $hashedPassword);
if (!$stmt->fetch()) {
    die("❌ Admin not found in database.");
}
$stmt->close();

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $error = "❌ New passwords do not match.";
    } elseif (!password_verify($current, $hashedPassword)) {
        $error = "❌ Current password is incorrect.";
    } else {
        $newHashed = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $newHashed, $email);
        if ($stmt->execute()) {
            $success = "✅ Password updated successfully.";
        } else {
            $error = "❌ Failed to update password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Profile – Southrift</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --purple: #6A0DAD;
      --purple-dark: #4e0b8a;
      --bg: #f4f4f4;
    }

    html {
      animation: fadeIn 0.7s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
    }

    nav{background:var(--purple);padding:1rem 2rem;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
.logo{font-size:1.5rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;animation:logoGlow 2s ease-in-out infinite alternate}
@keyframes logoGlow{0%{text-shadow:0 0 8px #fff,0 0 12px #0ff,0 0 20px #0ff}100%{text-shadow:0 0 12px #fff,0 0 20px #f0f,0 0 28px #f0f}}
.nav-right{display:flex;gap:20px;align-items:center}
.nav-right a{position:relative;color:paleturquoise;font-weight:600;text-decoration:none;padding:8px 10px;text-transform:uppercase;letter-spacing:1px;transition:color .3s}
.nav-right a::after{content:"";position:absolute;bottom:0;left:0;width:100%;height:2px;background:linear-gradient(to right,#ff6ec4,#7873f5);transform:scaleX(0);transform-origin:right;transition:transform .4s}
.nav-right a:hover{color:#00ffff;text-shadow:0 0 8px rgba(0,255,255,.6)}
.nav-right a:hover::after{transform:scaleX(1);transform-origin:left}
/* main */

    .container {
      max-width: 600px;
      margin: 50px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: var(--purple);
    }

    .info p {
      margin: 8px 0;
      font-weight: 500;
    }

    .info span {
      font-weight: bold;
      color: var(--purple);
    }

    .btn-toggle {
      display: block;
      margin: 30px auto 0;
      background: var(--purple);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
    }

    .password-form {
      margin-top: 25px;
      display: none;
    }

    .password-form input {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-top: 10px;
    }

    .password-form button {
      margin-top: 15px;
      background: var(--purple-dark);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    .success, .error {
      margin-top: 15px;
      padding: 10px;
      border-radius: 5px;
      text-align: center;
    }

    .success { background: #d4edda; color: #155724; }
    .error   { background: #f8d7da; color: #721c24; }

    .logout-link {
      display: block;
      text-align: center;
      margin-top: 40px;
      font-weight: bold;
      text-decoration: none;
      color: var(--purple);
    }

    footer{
        background:var(--purple);
        color:#fff;
        text-align:center;
        padding:1.5rem;
        margin-top:60px
    }

    @media (max-width: 600px) {
      .nav-right {
        display: block;
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>

<nav>
  <div class="logo">Southrift Services Limited</div>
  <div class="nav-right">
    <a href="index.php">Dashboard</a>
    <a href="#"><i class="fa fa-user-shield"></i> Super Admin</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<div class="container">
  <h2>Admin Profile</h2>

  <div class="info">
    <p><span>Name:</span> <?= htmlspecialchars($name) ?></p>
    <p><span>Email:</span> <?= htmlspecialchars($email) ?></p>
    <p><span>Phone:</span> <?= htmlspecialchars($phone) ?></p>
    <p><span>Role:</span> Admin</p>
  </div>

  <button class="btn-toggle" onclick="togglePasswordForm()">Change Password?</button>

  <div class="password-form" id="pwForm">
    <form method="POST">
      <input type="password" name="current_password" placeholder="Current Password" required>
      <input type="password" name="new_password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
      <button type="submit">Update Password</button>
    </form>
  </div>

  <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="error"><?= $error ?></div><?php endif; ?>

  <a class="logout-link" href="logout.php">🔒 Logout</a>
</div>

<footer>&copy; <?= date("Y") ?> Southrift Services Limited | Admin Panel</footer>

<script>
  function togglePasswordForm() {
    const form = document.getElementById("pwForm");
    form.style.display = form.style.display === "block" ? "none" : "block";
  }
</script>

</body>
</html>
