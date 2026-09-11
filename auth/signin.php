<?php
require "../config/database.php";

$email = $_SESSION['signin-data']['email'] ?? "";
$pwd = $_SESSION['signin-data']['pwd'] ?? "";

unset($_SESSION['signin-data']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signin - Attendance</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">

  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/line.css">
</head>

<body>
  <div class="auth-body">

   <div class="auth-hero">
    <div class="hero-content">
      <span class="badge"><i class="fa-solid fa-graduation-cap"></i> AttendEase Portal</span>
      <h1>Welcome Back to Campus</h1>
      <p>Streamline your academic journey. Track attendance, manage course schedules, and view real-time reports with ease.</p>
      
      <div class="features-list">
        <div class="feature-item">
          <i class="fa-solid fa-qrcode"></i>
          <span>Instant QR Check-ins</span>
        </div>
        <div class="feature-item">
          <i class="fa-solid fa-chart-line"></i>
          <span>Live Analytics</span>
        </div>
        <div class="feature-item">
          <i class="fa-solid fa-shield-halved"></i>
          <span>Secure Access</span>
        </div>
      </div>
    </div>
  </div>



    <?php if (isset($_SESSION['signup-success'])): ?>
      <div class="error-message" id="message">
        <p><?= $_SESSION['signup-success'];
        unset($_SESSION['signup-success']); ?></p>
        <i class="uil uil-times" id="error-cancel"></i>
      </div>

    <?php elseif (isset($_SESSION['signin'])): ?>
      <div class="error-message" id="message">
        <p><?= $_SESSION['signin'];
        unset($_SESSION['signin']); ?></p>
        <i class="uil uil-times" id="error-cancel"></i>
      </div>
    <?php endif; ?>

    <script>
      let errorCancel = document.getElementById('error-cancel');
      let message = document.getElementById('message');

      if (message) {
        message.classList.add("show");
        setTimeout(() => {
          message.classList.remove("show");
          message.classList.add("hide");
        }, 10000);
        errorCancel.addEventListener("click", () => {
          message.classList.remove('show');
          message.classList.add('hide');
        });
      }
    </script>

    <div class="form-container">
      <h2>Login</h2>
      <form id="signinForm" action="./signin-logic.php" enctype="multipart/form-data" method="POST">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= $email ?>" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="pwd" placeholder="Enter your password" required>

        <button type="submit">Sign In</button>
      </form>
      <p>Don’t have an account? <a href="signup.php">Sign Up</a></p>
    </div>

    
  </div>
</body>

</html>