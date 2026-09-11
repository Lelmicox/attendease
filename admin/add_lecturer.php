<?php
require_once "../config/database.php";

// ✅ Only admins can add lecturers
if (!isset($_SESSION['user-id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if ($name && $email && $password) {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) 
                                VALUES (:name, :email, :password, 'lecturer')");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password
        ]);
        $_SESSION['message'] = "Lecturer account created successfully!";
    } else {
        $_SESSION['message'] = "All fields are required.";
    }
}
?>
<div class="bodyy">
<h3>Add Lecturer</h3>
<?php if (isset($_SESSION['message'])): ?>
    <p><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
<?php endif; ?>

<form method="POST">
    <label>Full Name:</label>
    <input type="text" name="full_name" required>
    <label>Email:</label>
    <input type="email" name="email" required>
    <label>Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Create Lecturer</button>
</form>
</div>
