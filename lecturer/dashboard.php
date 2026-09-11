<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../admin/phpqrcode/qrlib.php";

// Authorization Guard
if (!isset($_SESSION['user-id']) || !in_array($_SESSION['role'] ?? '', ['lecturer', 'admin'])) {
    header("Location: ../auth/signin.php");
    exit;
}

define("QR_FOLDER", __DIR__ . "/../assets/qr_codes/");
define("QR_WEB_PATH", "../assets/qr_codes/");

/* ---------------------------
   1. Handle Add Department
---------------------------- */
if (isset($_POST['add_department'])) {
    $deptName = trim($_POST['department_name'] ?? '');

    if (!empty($deptName)) {
        $stmt = $conn->prepare('INSERT INTO departments (name) VALUES (:dname)');
        $stmt->execute([':dname' => $deptName]);
        $_SESSION['flash_msg'] = ["type" => "success", "text" => "Department added successfully!"];
    } else {
        $_SESSION['flash_msg'] = ["type" => "error", "text" => "Department name cannot be empty."];
    }
}

/* ---------------------------
   2. Handle Add Course
---------------------------- */
if (isset($_POST['add_course'])) {
    $courseName = trim($_POST['course_name'] ?? '');
    $courseCode = trim($_POST['course_code'] ?? '');
    $deptId     = $_POST['department_id'] ?? null;
    $lecturerId = $_SESSION['user-id'] ?? null;

    if ($courseName && $courseCode && $deptId && $lecturerId) {
        $stmt = $conn->prepare("INSERT INTO courses (name, code, department_id, lecturer_id) 
                                VALUES (:cname, :code, :dept, :lecturer)");
        $stmt->execute([
            ':cname'    => $courseName,
            ':code'     => $courseCode,
            ':dept'     => $deptId,
            ':lecturer' => $lecturerId
        ]);
        $_SESSION['flash_msg'] = ["type" => "success", "text" => "Course '$courseName' added successfully!"];
    } else {
        $_SESSION['flash_msg'] = ["type" => "error", "text" => "All fields are required to add a course."];
    }
}

/* ---------------------------
   3. Handle QR Generation
---------------------------- */
if (isset($_POST['generate_qr'])) {
    $courseId = $_POST['course_id'] ?? null;

    if (!$courseId) {
        $_SESSION['flash_msg'] = ["type" => "error", "text" => "Please select a course before generating QR."];
    } elseif (!function_exists('imagecreate')) {
        $_SESSION['flash_msg'] = ["type" => "error", "text" => "PHP GD extension is not enabled in php.ini."];
    } else {
        $token = bin2hex(random_bytes(16));
        $expiry_time = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        if (!file_exists(QR_FOLDER)) {
            mkdir(QR_FOLDER, 0777, true);
        }

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $url = "{$scheme}://{$_SERVER['HTTP_HOST']}/api/mark_attendance.php?token={$token}";

        $file = QR_FOLDER . "{$token}.png";

        // Correct parameter order: (text, file, errorCorrectionLevel, matrixPointSize, margin)
        QRcode::png($url, $file, QR_ECLEVEL_L, 6, 2);

        $stmt = $conn->prepare("INSERT INTO sessions (session_code, expiry_time, course_id, qr_path) 
                                VALUES (:session_code, :expiry_time, :course_id, :qr_path)");
        $stmt->execute([
            ':session_code' => $token,
            ':expiry_time'   => $expiry_time,
            ':course_id'     => $courseId,
            ':qr_path'       => QR_WEB_PATH . $token . ".png"
        ]);

        $_SESSION['qr_image'] = QR_WEB_PATH . $token . ".png";
        $_SESSION['qr_expiry'] = $expiry_time;
        $_SESSION['qr_image_time'] = time();
        $_SESSION['flash_msg'] = ["type" => "success", "text" => "QR Code Generated Successfully!"];
    }
}

/* ---------------------------
   4. Fetch Departments & Courses
---------------------------- */
$deptStmt = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedDept = $_POST['department_id'] ?? null;
$courses = [];
if ($selectedDept) {
    $courseStmt = $conn->prepare("SELECT id, name, code FROM courses WHERE department_id = :dept");
    $courseStmt->execute([':dept' => $selectedDept]);
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase - Lecturer Dashboard</title>
    <!-- Remix Icons & Font -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="ri-qr-code-line"></i>
            <span>AttendEase</span>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="#" class="sidebar-link active">
                    <i class="ri-qr-code-line"></i> Generate QR
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../backend/attendance_list.php" class="sidebar-link">
                    <i class="ri-file-list-3-line"></i> Attendance Logs
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../backend/analytics.php" class="sidebar-link">
                    <i class="ri-pie-chart-line"></i> Analytics
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../backend/manage_courses.php" class="sidebar-link">
                    <i class="ri-book-open-line"></i> Manage Courses
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="../auth/signin.php" class="sidebar-link" style="color: #ef4444;">
                <i class="ri-logout-box-r-line"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        
        <!-- Header -->
        <header class="top-header">
            <div style="display:flex; align-items:center; gap: 16px;">
                <button class="menu-toggle" id="menuToggle"><i class="ri-menu-line"></i></button>
                <div class="user-greeting">
                    <h2>Hello, 👋</h2>
                </div>
            </div>
            <div class="avatar-pill">
                <?= strtoupper(substr($_SESSION['email'] ?? 'L', 0, 2)) ?>
            </div>
        </header>

        <!-- Body -->
        <main class="content-area">
            <a href="#" class="back-link"><i class="ri-arrow-left-s-line"></i> Back to Dashboard</a>

            <!-- Flash Banner Message -->
            <?php if (isset($_SESSION['flash_msg'])): ?>
                <div class="toast-banner <?= $_SESSION['flash_msg']['type'] === 'error' ? 'error' : '' ?>" id="toastMsg">
                    <span><?= htmlspecialchars($_SESSION['flash_msg']['text']) ?></span>
                    <i class="ri-close-line" style="cursor:pointer;" onclick="document.getElementById('toastMsg').remove()"></i>
                </div>
                <?php unset($_SESSION['flash_msg']); ?>
            <?php endif; ?>

            <div class="dashboard-grid">
                
                <!-- 1. Generate QR Code Card -->
                <div class="card">
                    <h3 class="card-title">Generate Session QR</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="department_id">Department</label>
                            <select name="department_id" id="department_id" class="form-control" required onchange="this.form.submit()">
                                <option value="">-- Choose Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= ($selectedDept == $dept['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                <option value="">-- Choose Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>">
                                        <?= htmlspecialchars($course['code'] . ' - ' . $course['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="generate_qr" class="btn-primary">
                            <i class="ri-qr-code-line"></i> Generate Active QR
                        </button>
                    </form>
                </div>

                <!-- 2. Active QR Display Card -->
                <div class="card">
                    <h3 class="card-title">Active QR Code</h3>
                    <div class="qr-display-box">
                        <?php 
                        if (isset($_SESSION['qr_image_time']) && (time() - $_SESSION['qr_image_time'] > 1800)) {
                            unset($_SESSION['qr_image'], $_SESSION['qr_image_time'], $_SESSION['qr_expiry']);
                        }
                        ?>

                        <?php if (isset($_SESSION['qr_image'])): ?>
                            <img src="<?= htmlspecialchars($_SESSION['qr_image']) ?>" alt="QR Code" class="qr-img">
                            <div>
                                <span class="expiry-tag">
                                    <i class="ri-time-line"></i> Valid until: <?= date('h:i A', strtotime($_SESSION['qr_expiry'])) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div style="padding: 40px 0; color: var(--text-muted);">
                                <i class="ri-qr-scan-2-line" style="font-size: 3rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                Select a course and submit to create a fresh attendance QR session.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Add Department Card -->
                <div class="card">
                    <h3 class="card-title">Add Department</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="department_name">Department Name</label>
                            <input type="text" name="department_name" id="department_name" class="form-control" placeholder="e.g. Computer Science" required>
                        </div>
                        <button type="submit" name="add_department" class="btn-primary">
                            <i class="ri-add-line"></i> Add Department
                        </button>
                    </form>
                </div>

                <!-- 4. Add Course Card -->
                <div class="card">
                    <h3 class="card-title">Add Course</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="course_name">Course Name</label>
                            <input type="text" name="course_name" id="course_name" class="form-control" placeholder="e.g. Data Structures" required>
                        </div>
                        <div class="form-group">
                            <label for="course_code">Course Code</label>
                            <input type="text" name="course_code" id="course_code" class="form-control" placeholder="e.g. CSC201" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dept_select">Department</label>
                            <select name="department_id" id="dept_select" class="form-control" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= $department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_course" class="btn-primary">
                            <i class="ri-book-add-line"></i> Add Course
                        </button>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <!-- JavaScript Mobile Navigation Drawer -->
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', toggleMenu);
            sidebarOverlay.addEventListener('click', toggleMenu);
        }
    </script>
</body>
</html>