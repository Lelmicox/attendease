<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    
    // Trim input and validate email format
    $email = trim($_POST['email'] ?? '');
    $pwd   = trim($_POST['pwd'] ?? '');

    // Validate empty inputs
    if (empty($email) || empty($pwd)) {
        $_SESSION['signin'] = "Please fill in all fields.";
        $_SESSION['signin-data'] = ['email' => $email];
        header("Location: signin.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['signin'] = "Invalid email format.";
        $_SESSION['signin-data'] = ['email' => $email];
        header("Location: signin.php");
        exit;
    }

    try {
        // Fetch user record
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify user and password hash
        if ($user && password_verify($pwd, $user['pwd'])) {
            
            // Prevent Session Fixation attacks
            session_regenerate_id(true);

            $role = strtolower(trim($user['role'] ?? ''));

            // Set session variables
            $_SESSION['user-id'] = $user['id'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $role;

            // Redirect based on role
          
if ($role === 'student') {
    header("Location: ../students/students_dashboard.php");
    exit;
} elseif ($role === 'lecturer') {
    header("Location: ../lecturer/dashboard.php");
    exit;
} elseif ($role === 'admin') {
    header("Location: ../admin/admin_dashboard.php");
    exit;
}
else {
    $_SESSION['signin'] = "Unauthorized role.";
    header("Location: ./signin.php");
    exit;
}
        } else {
            $_SESSION['signin'] = "Invalid email or password.";
            $_SESSION['signin-data'] = ['email' => $email];
            header("Location: signin.php");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['signin'] = "Database error. Please try again.";
        $_SESSION['signin-data'] = ['email' => $email];
        header("Location: signin.php");
        exit;
    }

} else {
    header("Location: signin.php");
    exit;
}