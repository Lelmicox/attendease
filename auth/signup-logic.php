<?php
require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $raw_pwd    = $_POST['password'] ?? ''; 
    $reg_number = trim($_POST['reg_number'] ?? '');
    $level      = $_POST['level'] ?? '';
    $faculty    = trim($_POST['faculty'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // 1. Check for empty fields
    if (empty($name) || empty($email) || empty($raw_pwd) || empty($reg_number) || empty($level) || empty($faculty) || empty($department)) {
        $_SESSION['signup'] = "Please fill in all empty fields.";
        $_SESSION['signup-data'] = $_POST;
        header("Location: signup.php");
        exit();
    }

    // 2. Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['signup'] = 'Please enter a valid email format.';
        $_SESSION['signup-data'] = $_POST;
        header("Location: signup.php");
        exit();
    }

    // 3. Check password length ON RAW INPUT (Before Hashing)
    if (strlen($raw_pwd) < 8) {
        $_SESSION['signup'] = "Password must be at least 8 characters.";
        $_SESSION['signup-data'] = $_POST;
        header("Location: signup.php");
        exit();
    }

    // Hash password ONLY AFTER validation passes
    $password_hash = password_hash($raw_pwd, PASSWORD_DEFAULT);

    try {
        // 4. Check if reg_number already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE reg_number = :reg_number LIMIT 1");
        $stmt->execute([':reg_number' => $reg_number]);
        if ($stmt->fetch()) {
            $_SESSION['signup'] = "Registration number already exists.";
            $_SESSION['signup-data'] = $_POST;
            header("Location: signup.php");
            exit();
        }

        // 5. Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]); 
        if ($stmt->fetch()) {
            $_SESSION['signup'] = "Email already exists.";
            $_SESSION['signup-data'] = $_POST;
            header("Location: signup.php");
            exit();
        }

        // 6. Insert user into the database
        $stmt = $conn->prepare("INSERT INTO users 
            (fullname, email, pwd, reg_number, level, faculty, department, role) 
            VALUES (:fullname, :email, :pwd, :reg_number, :level, :faculty, :department, 'student')");
            
        $stmt->execute([
            ':fullname'   => $name,
            ':email'      => $email,
            ':pwd'        => $password_hash,
            ':reg_number' => $reg_number,
            ':level'      => $level,
            ':faculty'    => $faculty,
            ':department' => $department
        ]);

        // Success redirect
        $_SESSION['signup-success'] = "Account created successfully!";
        header("Location: signin.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['signup'] = "Database error: " . $e->getMessage();
        $_SESSION['signup-data'] = $_POST;
        header("Location: signup.php");
        exit();
    }

} else {
    header("Location: signup.php");
    exit();
}