<?php 


// 2. Load the database configuration
require_once __DIR__ . '/../config/database.php'; 
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>AttendEase - University Attendance System</title> 
    <link rel="icon" type="image/png" href="/attendease/assets/images/my_logo.png"> 
    <link rel="stylesheet" href="/attendease/assets/css/style.css"> 
    <link rel="stylesheet" href="/attendease/assets/css/student_dashboard.css"> 
    <link rel="stylesheet" href="/attendease/assets/css/responsive.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
</head> 
<body> 
<header id="header"> 
    <div class="logo">AttendEase</div> 
    <nav class="nav"> 
        <ul> 
            <li><a href="/attendease/index.php">HOME</a></li> 
            <li><a href="/attendease/contact.php">CONTACT</a></li> 
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?> 
                <li><a href="/attendease/students/students_dashboard.php">STUDENT DASHBOARD</a></li> 
            <?php elseif (isset($_SESSION['role']) && ($_SESSION['role'] === 'lecturer' || $_SESSION['role'] === 'admin')): ?> 
                <li><a href="/attendease/admin/generate_qr_code.php">Generate QR</a></li> 
                <li><a href="/attendease/admin/admin_dashboard.php">ADMIN DASHBOARD</a></li> 
            <?php endif; ?> 
            
            <?php if (!isset($_SESSION['role'])): ?>
                <li> 
                    <a href="/attendease/auth/signin.php" style="color:#1abc9c; margin-left:15px; font-size:1.2em; font-weight:600; background-color:#105245dc; padding:12px 55px; border-radius:6px; max-width:fit-content;"> Sign In </a> 
                </li> 
            <?php else: ?>
                <li>
                    <a href="/attendease/auth/signout.php" style="color:#1abc9c; margin-left:15px; font-size:1.2em; font-weight:600; background-color:#105245dc; padding:12px 55px; border-radius:6px; max-width:fit-content;">Logout</a>
                </li> 
            <?php endif; ?>
        </ul> 
    </nav> 
    <div class="menu-toggle" id="menu-toggle1" aria-label="Open Menu"> 
        <i class="fa-solid fa-bars-staggered"></i>
    </div> 
    <div class="menu-toggle" id="menu-toggle2" aria-label="Close Menu">
        <i class="fa-sharp fa-solid fa-xmark"></i>
    </div> 
</header> 
<div class="sidebar-overlay" id="sidebar-overlay"></div>
