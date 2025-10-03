<!-- signup.php -->
<?php
$message = '';
if (isset($_GET['status'])) {
  if ($_GET['status'] === 'success') {
    $message = "<div style='color: green; font-weight: bold; margin-bottom: 10px;'>Your account has been successfully created. Proceed to login.</div>";
  } elseif ($_GET['status'] === 'exists') {
    $message = "<div style='color: red; font-weight: bold; margin-bottom: 10px;'>You already have an account. Proceed to login.</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- ... [UNCHANGED HEAD CONTENT] ... -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up - Southrift Services Limited</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
   * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #6a1b9a, #8e24aa);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }

    .container {
      background: #fff;
      border-radius: 30px;
      box-shadow: 0 30px 50px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
      width: 850px;
      max-width: 100%;
      height: 550px;
      display: flex;
      animation: fadeIn 0.8s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-container {
      width: 50%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 0 50px;
      text-align: center;
    }

    form {
      width: 100%;
      display: flex;
      flex-direction: column;
    }

    .form-container h1 {
      margin-bottom: 20px;
      color: #333;
    }

    .input-box {
      position: relative;
      margin: 10px 0;
      width: 100%;
    }

    .input-box input {
      padding: 12px 15px;
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
    }

    .input-box i {
      position: absolute;
      top: 50%;
      right: 15px;
      transform: translateY(-50%);
      color: #999;
    }

    button {
      border: none;
      padding: 12px 45px;
      background-color: #6a1b9a;
      color: white;
      font-size: 14px;
      border-radius: 20px;
      cursor: pointer;
      margin-top: 15px;
      transition: background 0.3s;
    }

    button:hover {
      background-color: #4a148c;
    }

    .overlay-container {
      width: 50%;
      background: linear-gradient(135deg, #6a1b9a, #8e24aa);
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 0 40px;
      border-radius: 100% 0 0 100% / 50% 0 0 50%;
    }

    .overlay-container h2 {
      margin-bottom: 10px;
    }

    .overlay-container p {
      font-size: 15px;
      margin-bottom: 20px;
    }

    .toggle-btn {
      background-color: transparent;
      border: 2px solid #fff;
      color: #fff;
      padding: 10px 30px;
      border-radius: 20px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s;
      text-decoration: none;
      display: inline-block;
    }

    .toggle-btn:hover {
      background-color: #ffffff22;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        height: auto;
        width: 95%;
        border-radius: 20px;
      }

      .form-container,
      .overlay-container {
        width: 100%;
        border-radius: 0;
        padding: 30px;
      }

      .overlay-container {
        border-radius: 0 0 20px 20px;
      }
    }
    <?php // you can keep your CSS here unchanged ?>
  </style>
</head>
<body>

  <div class="container">
    <div class="form-container">
      <form action="register.php" method="POST">
        <!-- ✅ Show message just above "Create Account" heading -->
        <?php echo $message; ?>
        <h1>Create Account</h1>
        <div class="input-box">
          <input type="text" name="name" placeholder="Full Name" required>
          <i class='bx bxs-user'></i>
        </div>
        <div class="input-box">
          <input type="email" name="email" placeholder="Email" required>
          <i class='bx bxs-envelope'></i>
        </div>
        <div class="input-box">
          <input type="text" name="phone" placeholder="Phone Number" required>
          <i class='bx bxs-phone'></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required>
          <i class='bx bxs-lock-alt'></i>
        </div>
        <button type="submit">Register</button>
      </form>
    </div>

    <div class="overlay-container">
      <h2>Welcome to Southrift Services Limited</h2>
      <p>Thank you for traveling with us! We value your trust.</p>
      <a href="login.html" class="toggle-btn">Login</a>
    </div>
  </div>

</body>
</html>
