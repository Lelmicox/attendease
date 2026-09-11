<?php
require_once "../config/database.php";

// Authorization Guard
if (!isset($_SESSION['user-id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: ../auth/signin.php");
    exit;
}

// Fetch student details
$student_id = $_SESSION['user-id'];
$stmt = $conn->prepare("SELECT fullname, reg_number, level, faculty, department FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Extract First Name for Greeting
$fullName = $student['fullname'] ?? 'Student';
$firstName = explode(' ', trim($fullName))[0];

// Extract Initials for Profile Avatar Pill
$nameParts = explode(' ', trim($fullName));
$initials = strtoupper(substr($nameParts[0] ?? 'S', 0, 1) . substr($nameParts[1] ?? '', 0, 1));

// Fetch attendance history
$history_stmt = $conn->prepare("
    SELECT a.date, c.code AS course_code, c.name AS course_name, a.status
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
");
$history_stmt->execute([$student_id]);
$attendance_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Quick Stats
$totalClasses = count($attendance_history);
$presentClasses = count(array_filter($attendance_history, fn($row) => strtolower($row['status']) === 'present'));
$attendanceRate = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase - Student Dashboard</title>
    <!-- Remix Icons & Inter Font -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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
       .sidebar-overlay { 
    position: fixed; 
    inset: 0; 
    background: rgba(0,0,0,0.4); 
    opacity: 0; 
    pointer-events: none; 
    transition: 0.3s; 
    z-index: 101; 
}
       .sidebar { 
    width: var(--sidebar-width); 
    background: var(--bg-card); 
    border-right: 1px solid var(--border-color); 
    display: flex; 
    flex-direction: column; 
    position: fixed; 
    top: 0; 
    bottom: 0; 
    left: 0; 
    z-index: 102; 
    transition: transform 0.3s ease; 
}
        .sidebar-brand { padding: 40px 24px; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.3rem; color: var(--primary); }
        .sidebar-menu { list-style: none; padding: 10px 16px; flex: 1; }
        .sidebar-item { margin-bottom: 2em; }
        .sidebar-link { display: flex; align-items: center; gap: 14px; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-size: 0.95rem; font-weight: 500; border-radius: var(--radius-sm); transition: 0.2s; }
        .sidebar-link:hover, .sidebar-link.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
        .sidebar-footer { padding: 20px 16px; border-top: 1px solid var(--border-color); }

        /* Main Wrapper */
        .main-wrapper { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; width: calc(100% - var(--sidebar-width)); transition: margin 0.3s; }

        /* Top Header */
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

        /* Top Dashboard Grid (Hero Card + Scanner Card) */
        .dashboard-top-grid { display: grid; grid-template-columns: 360px 1fr; gap: 24px; margin-bottom: 32px; }

        /* Orange Main Feature Card (Matching UI Image) */
        .student-id-card { background: var(--primary); color: white; border-radius: var(--radius-lg); padding: 28px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 10px 25px rgba(255, 102, 0, 0.2); }
        .card-top-tag { font-size: 0.85rem; font-weight: 500; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-reg-number { font-size: 1.25rem; font-weight: 700; margin: 4px 0 20px; letter-spacing: 1px; }
        
        .pill-select-container { background: rgba(0, 0, 0, 0.15); border-radius: 30px; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; backdrop-filter: blur(5px); }
        .pill-select-container span { font-weight: 600; font-size: 0.9rem; }

        .stat-value-display { font-size: 1.8rem; font-weight: 800; display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .stat-value-display i { font-size: 1.2rem; cursor: pointer; opacity: 0.8; }

        .card-action-btns { display: flex; gap: 16px; }
        .action-btn-item { display: flex; flex-direction: column; align-items: center; gap: 6px; text-decoration: none; color: white; font-size: 0.8rem; font-weight: 600; }
        .action-icon-circle { background: white; color: var(--text-dark); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .action-btn-item:hover .action-icon-circle { transform: translateY(-3px); }

        /* Scanner Panel Container */
        .scanner-panel { background: var(--bg-card); border-radius: var(--radius-lg); padding: 28px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .panel-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); }
        
        .video-scanner-wrapper { position: relative; width: 100%; height: 210px; background: #0f172a; border-radius: var(--radius-md); overflow: hidden; display: flex; align-items: center; justify-content: center; border: 2px dashed var(--border-color); }
        #sd-qr-preview { width: 100%; height: 100%; object-fit: cover; }
        .scanner-overlay-guide { position: absolute; width: 140px; height: 140px; border: 2px solid var(--primary); border-radius: 12px; box-shadow: 0 0 0 4000px rgba(0,0,0,0.4); pointer-events: none; }

        /* Quick Info Tabs Section */
        .info-tabs-bar { display: flex; gap: 24px; border-bottom: 1px solid var(--border-color); margin-bottom: 24px; padding-bottom: 12px; overflow-x: auto; }
        .tab-item { font-size: 0.95rem; font-weight: 600; color: var(--text-muted); text-decoration: none; padding-bottom: 12px; position: relative; cursor: pointer; white-space: nowrap; }
        .tab-item.active { color: var(--primary); }
        .tab-item.active::after { content: ''; position: absolute; bottom: -13px; left: 0; right: 0; height: 3px; background: var(--primary); border-radius: 3px 3px 0 0; }

        /* Student Detail Cards Grid */
        .details-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .info-mini-card { background: var(--bg-card); border: 1px solid var(--border-color); padding: 18px 20px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .info-icon { width: 44px; height: 44px; border-radius: var(--radius-sm); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .info-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; margin-bottom: 2px; }
        .info-val { font-size: 0.95rem; font-weight: 700; color: var(--text-dark); }

        /* Attendance Table Card */
        .table-card { background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .table-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        .history-table { width: 100%; border-collapse: collapse; text-align: left; }
        .history-table th { padding: 14px 16px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-color); text-transform: uppercase; letter-spacing: 0.5px; }
        .history-table td { padding: 16px; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); color: var(--text-dark); font-weight: 500; }
        .history-table tbody tr:last-child td { border-bottom: none; }
        
        /* Status Badges */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-badge.present { background: #dcfce7; color: #15803d; }
        .status-badge.absent { background: #fee2e2; color: #b91c1c; }
        .status-badge.late { background: #fef3c7; color: #b45309; }

        .empty-state { text-align: center; padding: 40px 0; color: var(--text-muted); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 8px; color: #cbd5e1; display: block; }

        /* Responsive Layout Breakpoints */
        @media (max-width: 1024px) {
            .dashboard-top-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); width: 300px; }
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
                <a href="student_dashboard.php" class="sidebar-link active">
                    <i class="ri-home-5-line"></i> Home
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#scanner-section" class="sidebar-link">
                    <i class="ri-qr-scan-2-line"></i> Scan Attendance
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#history-section" class="sidebar-link">
                    <i class="ri-history-line"></i> Logs & Records
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link">
                    <i class="ri-user-3-line"></i> Profile Settings
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
        
        <!-- Header Bar -->
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

            <!-- Top Dashboard Hero Section -->
            <div class="dashboard-top-grid">
                
                <!-- Orange Student Overview Card -->
                <div class="student-id-card">
                    <div>
                        <div class="card-top-tag">Student Matric ID</div>
                        <div class="card-reg-number"><?= htmlspecialchars($student['reg_number'] ?? 'N/A') ?></div>
                        
                        <div class="pill-select-container">
                            <span><?= htmlspecialchars($student['department'] ?? 'Department') ?></span>
                            <i class="ri-arrow-down-s-line"></i>
                        </div>

                        <div class="card-top-tag">Overall Attendance Rate</div>
                        <div class="stat-value-display">
                            <span><?= $attendanceRate ?>%</span>
                            <i class="ri-eye-line"></i>
                        </div>
                    </div>

                    <div class="card-action-btns">
                        <a href="#scanner-section" class="action-btn-item">
                            <div class="action-icon-circle"><i class="ri-qr-scan-line"></i></div>
                            <span>Scan QR</span>
                        </a>
                        <a href="#history-section" class="action-btn-item">
                            <div class="action-icon-circle"><i class="ri-file-list-3-line"></i></div>
                            <span>View Logs</span>
                        </a>
                    </div>
                </div>

                <!-- Camera QR Scanner Container -->
                <div class="scanner-panel" id="scanner-section">
                    <div class="panel-header">
                        <div class="panel-title"><i class="ri-camera-lens-line" style="color: var(--primary);"></i> Attendance Scanner</div>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Active Camera</span>
                    </div>

                    <div class="video-scanner-wrapper">
                        <video id="sd-qr-preview"></video>
                        <div class="scanner-overlay-guide"></div>
                    </div>

                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 12px; text-align: center;">
                        <i class="ri-shield-check-line" style="color: var(--primary);"></i> Point your camera at the lecturer's QR code to log entry.
                    </p>
                </div>

            </div>

            <!-- Quick Navigation Tabs -->
            <div class="info-tabs-bar">
                <span class="tab-item active">Overview</span>
                <span class="tab-item">Enrolled Courses</span>
                <span class="tab-item">Attendance Analytics</span>
            </div>

            <!-- Student Profile Information Badges -->
            <div class="details-cards-grid">
                <div class="info-mini-card">
                    <div class="info-icon"><i class="ri-award-line"></i></div>
                    <div>
                        <span class="info-label">Academic Level</span>
                        <span class="info-val"><?= htmlspecialchars($student['level'] ?? 'N/A') ?> Level</span>
                    </div>
                </div>

                <div class="info-mini-card">
                    <div class="info-icon"><i class="ri-building-4-line"></i></div>
                    <div>
                        <span class="info-label">Faculty</span>
                        <span class="info-val"><?= htmlspecialchars($student['faculty'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div class="info-mini-card">
                    <div class="info-icon"><i class="ri-checkbox-circle-line"></i></div>
                    <div>
                        <span class="info-label">Classes Attended</span>
                        <span class="info-val"><?= $presentClasses ?> / <?= $totalClasses ?> Sessions</span>
                    </div>
                </div>
            </div>

            <!-- Attendance History Table -->
            <div class="table-card" id="history-section">
                <div class="table-card-header">
                    <div class="panel-title">Recent Attendance Logs</div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendance_history): ?>
                                <?php foreach ($attendance_history as $row): ?>
                                    <?php $statusLower = strtolower($row['status']); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['course_code'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($row['course_name']) ?></td>
                                        <td>
                                            <span class="status-badge <?= $statusLower ?>">
                                                <i class="ri-checkbox-blank-circle-fill" style="font-size: 0.5rem;"></i>
                                                <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="ri-inbox-archive-line"></i>
                                            <p>No attendance records logged yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- JavaScript Drawer & Camera Init -->
    <script src="../assets/js/qr-scanner.js"></script>
    <script>
        // Responsive Mobile Menu Handler
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