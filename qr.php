<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . "/includes/header.php";
require_once __DIR__ . "/config/database.php";

$session = null;

try {

$stmt = $conn->prepare("SELECT session_code FROM sessions WHERE expiry_time > NOW() 
    ORDER BY id DESC LIMIT 1");
$stmt->execute();
$session = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div style='color:red; background:#fff; padding:10px;'>Database Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<section class="qr" style="margin-top: 100px;">
  <?php if (isset($_SESSION['qr-code'])): ?>
    <div class="error-message" id="message">
      <p><?= htmlspecialchars($_SESSION['qr-code']); ?></p>
      <?php unset($_SESSION['qr-code']); ?>
    </div>
  <?php endif; ?>

  <div class="container">
    <h1>QR Attendance</h1>
    <p>Scan the QR code below to mark your attendance. Make sure you are on campus for validation.</p>

    <div class="qr-box" style="margin: 20px auto;">
      <?php 
        $qrFile = "";
        if ($session && !empty($session['session_code'])) {
            $qrFile = "assets/qr_codes/" . $session['session_code'] . ".png";
        }
      ?>

      <?php if (!empty($qrFile) && file_exists(__DIR__ . "/" . $qrFile)): ?>
        <img src="<?= htmlspecialchars($qrFile) ?>" alt="QR Code" class="qr-img" style="max-width: 250px; height: auto;">
      <?php else: ?>
        <p>No active QR code image found: <?= htmlspecialchars($qrFile ?: '') ?></p>
      <?php endif; ?>
    </div>

    <div class="qr-instructions">
      <h2>How It Works</h2>
      <ul style="list-style: none; padding: 0; margin-top: 3em;">
        <li><i class="fa-solid fa-qrcode"></i> Open AttendEase and scan the QR code.</li>
        <li><i class="fa-solid fa-location-dot"></i> Ensure you are physically present on campus.</li>
        <li><i class="fa-solid fa-check"></i> Your attendance will be recorded instantly.</li>
      </ul>
    </div>
  </div>
</section>

<footer>
  <p>&copy; 2026 AttendEase | Designed for University Attendance</p>
</footer>

<script src="./assets/js/main.js"></script>
<script src="../assets/js/qr.js"></script>

</body>
</html>