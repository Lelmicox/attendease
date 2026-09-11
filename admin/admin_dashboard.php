<?php
require_once "../config/database.php";

// Authorization Guard
if (!isset($_SESSION['user-id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/signin.php");
    exit;
}

// Fetch lecturer/admin info
$user_id = $_SESSION['user-id'];
$stmt = $conn->prepare("SELECT fullname, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Extract First Name & Initials
$fullName = $user['fullname'] ?? 'Admin';
$firstName = explode(' ', trim($fullName))[0];
$nameParts = explode(' ', trim($fullName));
$initials = strtoupper(substr($nameParts[0] ?? 'A', 0, 1) . substr($nameParts[1] ?? '', 0, 1));

// Messages feedback
$action_msg = "";
$action_msg_type = "";

// ---------------------------
// Action 1: Handle Add Lecturer Post Action (Admins Only)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lecturer') {
    if ($user['role'] === 'admin') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $raw_pass = $_POST['password'] ?? '';

        if ($name && $email && $raw_pass) {
            $password = password_hash($raw_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, pwd, role) VALUES (:name, :email, :password, 'lecturer')");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $password
            ]);
            $action_msg = "Lecturer account created successfully!";
            $action_msg_type = "success";
        } else {
            $action_msg = "All lecturer fields are required.";
            $action_msg_type = "error";
        }
    } else {
        $action_msg = "Unauthorized action.";
        $action_msg_type = "error";
    }
}

// ---------------------------
// Action 2: Handle Add Course Action (Fixes Foreign Key Issue)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $courseName = trim($_POST['course_name'] ?? '');
    $courseCode = trim($_POST['course_code'] ?? '');
    $deptId     = $_POST['department_id'] ?? null;
    $lecturerId = $_SESSION['user-id'] ?? null; // Passes valid integer ID to satisfy foreign key

    if ($courseName && $courseCode && $deptId && $lecturerId) {
        $stmt = $conn->prepare("INSERT INTO courses (name, code, department_id, lecturer_id) VALUES (:cname, :code, :dept, :lecturer)");
        $stmt->execute([
            ':cname'    => $courseName,
            ':code'     => $courseCode,
            ':dept'     => $deptId,
            ':lecturer' => $lecturerId
        ]);
        $action_msg = "Course '$courseName' ($courseCode) added successfully!";
        $action_msg_type = "success";
    } else {
        $action_msg = "All course fields are required.";
        $action_msg_type = "error";
    }
}

// Fetch departments for dropdown select
$dept_stmt = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses assigned to current user
$courses_stmt = $conn->prepare("
    SELECT c.id, c.name, c.code, d.name AS department_name
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    WHERE c.lecturer_id = ?
");
$courses_stmt->execute([$user_id]);
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCourses = count($courses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase - Admin Dashboard</title>
    <!-- Remix Icons & Inter Font -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff6600;
            --primary-dark: #e05500;
            --primary-light: #fff2e6;
            --bg-main: #f4f6f8;
            --bg-card: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-width: 240px;
            --radius-lg: 20px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar Navigation */
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); opacity: 0; pointer-events: none; transition: 0.3s; z-index: 101; }
        .sidebar { width: var(--sidebar-width); background: var(--bg-card); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 102; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 28px 24px; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.3rem; color: var(--primary); }
        .sidebar-menu { list-style: none; padding: 10px 16px; flex: 1; }
        .sidebar-item { margin-bottom: 1em; }
        .sidebar-link { display: flex; align-items: center; gap: 14px; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-size: 0.95rem; font-weight: 500; border-radius: var(--radius-sm); transition: 0.2s; }
        .sidebar-link:hover, .sidebar-link.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
        .sidebar-footer { padding: 20px 16px; border-top: 1px solid var(--border-color); }

        /* Main Wrapper */
        .main-wrapper { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; width: calc(100% - var(--sidebar-width)); transition: margin 0.3s; }

        /* Fixed Top Header */
        .top-header { 
            position: sticky;
            top: 0;
            z-index: 90;
            background: var(--bg-card); 
            border-bottom: 1px solid var(--border-color); 
            padding: 20px 40px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.5rem; color: var(--text-dark); cursor: pointer; }
        .user-greeting h2 { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-dark); }
        .user-profile-badge { display: flex; align-items: center; gap: 12px; }
        .avatar-circle { background: #ffe6d5; color: var(--primary); width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; }
        .profile-name { font-size: 0.95rem; font-weight: 600; color: var(--text-dark); }

        /* Content Area Layout */
        .content-area { padding: 32px 40px; max-width: 1280px; width: 100%; margin: 0 auto; }

        /* Top Dashboard Grid */
        .dashboard-top-grid { display: grid; grid-template-columns: 360px 1fr; gap: 24px; margin-bottom: 32px; }

        /* Orange Main Feature Card */
        .admin-id-card { background: var(--primary); color: white; border-radius: var(--radius-lg); padding: 28px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 10px 25px rgba(255, 102, 0, 0.2); }
        .card-top-tag { font-size: 0.85rem; font-weight: 500; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-role-title { font-size: 1.25rem; font-weight: 700; margin: 4px 0 20px; letter-spacing: 0.5px; }

        .stat-value-display { font-size: 1.8rem; font-weight: 800; display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .stat-value-display i { font-size: 1.2rem; opacity: 0.8; }

        /* Form Controls */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; }
        .form-control-custom {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            outline: none;
            font-size: 0.95rem;
            background: #f8fafc;
            transition: border-color 0.2s;
        }
        .form-control-custom:focus { border-color: var(--primary); }
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-primary-custom:hover { background: var(--primary-dark); }

        /* Admin Panels Grid */
        .admin-panel { background: var(--bg-card); border-radius: var(--radius-lg); padding: 28px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 32px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }

        /* Alert Feedback Boxes */
        .alert-box { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 500; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .alert-box.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-box.error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .dashboard-top-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .main-wrapper { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; }
            .content-area { padding: 20px; }
            .top-header { padding: 16px 20px; }
        }
    </style>
</head>
<body>

    <!-- Mobile Drawer Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation Menu -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="ri-qr-code-line"></i>
            <span>AttendEase</span>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="admin_dashboard.php" class="sidebar-link active">
                    <i class="ri-home-5-line"></i> Home
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#add-course-section" class="sidebar-link">
                    <i class="ri-book-add-line"></i> Add Course
                </a>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li class="sidebar-item">
                <a href="#add-lecturer-section" class="sidebar-link">
                    <i class="ri-user-add-line"></i> Add Lecturer
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-item">
                <a href="#qr-generator-section" class="sidebar-link">
                    <i class="ri-qr-code-line"></i> Generate QR
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#analytics-section" class="sidebar-link">
                    <i class="ri-bar-chart-box-line"></i> Analytics
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="../auth/signin.php" class="sidebar-link" style="color: #ef4444;">
                <i class="ri-logout-box-r-line"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Container -->
    <div class="main-wrapper">
        
        <!-- Fixed Header Bar -->
        <header class="top-header">
            <div style="display:flex; align-items:center; gap: 16px;">
                <button class="menu-toggle" id="menuToggle"><i class="ri-menu-line"></i></button>
                <div class="user-greeting">
                    <h2>Hello, <?= htmlspecialchars($firstName) ?> 👋</h2>
                </div>
            </div>
            <div class="user-profile-badge">
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <span class="profile-name"><?= htmlspecialchars($fullName) ?></span>
            </div>
        </header>

        <!-- Content Area -->
        <main class="content-area">

            <!-- Flash Alert Box -->
            <?php if ($action_msg): ?>
                <div class="alert-box <?= $action_msg_type ?>">
                    <i class="<?= $action_msg_type === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line' ?>"></i>
                    <?= htmlspecialchars($action_msg) ?>
                </div>
            <?php endif; ?>

            <!-- Top Overview & Main Actions Grid -->
            <div class="dashboard-top-grid">
                
                <!-- Orange Role Card -->
                <div class="admin-id-card">
                    <div>
                        <div class="card-top-tag">Account Role</div>
                        <div class="card-role-title"><?= htmlspecialchars(ucfirst($user['role'])) ?> Dashboard</div>

                        <div class="card-top-tag">Assigned Courses</div>
                        <div class="stat-value-display">
                            <span><?= $totalCourses ?> Active Courses</span>
                            <i class="ri-book-open-line"></i>
                        </div>
                    </div>

                    <div style="font-size: 0.85rem; opacity: 0.9;">
                        <i class="ri-mail-line"></i> <?= htmlspecialchars($user['email']) ?>
                    </div>
                </div>

                <!-- QR Generator Form Panel -->
                <div class="admin-panel" id="qr-generator-section" style="margin-bottom:0;">
                    <div class="panel-header">
                        <div class="panel-title"><i class="ri-qr-code-line" style="color: var(--primary);"></i> Generate QR Code</div>
                    </div>
                    <form action="generate_qr_code.php" method="POST">
                        <div class="form-group">
                            <label>Course Name</label>
                            <select name="course_id" class="form-control-custom" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['name']); ?> (<?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['department_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary-custom">
                            <i class="ri-qr-scan-2-line"></i> Generate Live QR Code
                        </button>
                    </form>
                </div>

            </div>

            <!-- Add Course Section -->
            <div class="admin-panel" id="add-course-section">
                <div class="panel-header">
                    <div class="panel-title"><i class="ri-book-add-line" style="color: var(--primary);"></i> Add New Course</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_course">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label>Course Name</label>
                            <input type="text" name="course_name" class="form-control-custom" placeholder="e.g. Software Engineering" required>
                        </div>
                        <div class="form-group">
                            <label>Course Code</label>
                            <input type="text" name="course_code" class="form-control-custom" placeholder="e.g. CSC 401" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control-custom" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-custom" style="max-width: 200px; margin-top: 8px;">
                        <i class="ri-add-line"></i> Add Course
                    </button>
                </form>
            </div>

            <?php if ($user['role'] === 'admin'): ?>
            <!-- Add Lecturer Section -->
            <div class="admin-panel" id="add-lecturer-section">
                <div class="panel-header">
                    <div class="panel-title"><i class="ri-user-add-line" style="color: var(--primary);"></i> Add New Lecturer</div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="add_lecturer">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control-custom" placeholder="e.g. Dr. John Doe" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control-custom" placeholder="e.g. lecturer@esut.edu.ng" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-custom" style="max-width: 200px; margin-top: 8px;">
                        <i class="ri-add-line"></i> Create Lecturer
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Attendance Analytics Chart Panel -->
            <div class="admin-panel" id="analytics-section">
                <div class="panel-header">
                    <div class="panel-title"><i class="ri-bar-chart-grouped-line" style="color: var(--primary);"></i> Attendance Analytics</div>
                </div>
                <canvas id="attendanceChart" style="max-height: 380px; width: 100%;"></canvas>
            </div>

        </main>
    </div>

    <!-- JavaScript Drawer & Chart Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script>
        // Mobile Sidebar Drawer Toggle
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