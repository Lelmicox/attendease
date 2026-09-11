<?php
require_once __DIR__ . "/../config/database.php";

$fullname = $_SESSION['signup-data']['name'] ?? '';
$email = $_SESSION['signup-data']['email'] ?? '';
$pwd = $_SESSION['signup-data']['pwd'] ?? '';
$role = $_SESSION['signup-data']['role'] ?? '';

unset($_SESSION['signup-data']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signup - Attendance</title>
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



    <?php if (isset($_SESSION['signup'])): ?>
      <div class="error-message" id="message">
        <p><?= $_SESSION['signup'];
        unset($_SESSION['signup']); ?></p>
        <i class="uil uil-times" id="error-cancel"></i>
      </div>
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
    <?php endif; ?>

    <div class="form-container">
      <h2>Create Account</h2>

      <form id="signupForm" action="./signup-logic.php" method="POST">
        <!-- Full Name -->
        <label for="name">Full Name:</label>
        <input type="text" name="name" id="name" required>

        <!-- Email -->
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>

        <!-- Password -->
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <!-- Registration Number -->
        <label for="reg_number">Registration Number:</label>
        <input type="text" name="reg_number" id="reg_number" required>

        <!-- Level -->
        <label for="level">Level:</label>
        <select name="level" id="level" required>
          <option value="">-- Select Level --</option>
          <option value="100">100</option>
          <option value="200">200</option>
          <option value="300">300</option>
          <option value="400">400</option>
        </select>

        <!-- Faculty -->
        <label for="faculty">Faculty:</label>
        <input type="text" name="faculty" id="faculty" required>

        <!-- Department -->
        <label for="department">Department:</label>
        <input type="text" name="department" id="department" required>

        <button type="submit">Sign Up</button>

      </form>
      <p>Already have an account? <a href="signin.php">Sign in</a></p>
    </div>

 
  </div>
</body>

</html>