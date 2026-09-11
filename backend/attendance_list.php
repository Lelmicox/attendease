<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/attendease/config/database.php";


if (!isset($_SESSION['user-id'])) {
    die("Access denied. You must log in.");
}

if ($_SESSION['user-id'] !== 'lecturer' || $_SESSION['user-id'] !== 'admin') {
    die("Access denied. Only lecturers and admins can view this page.");
}



$session_id = $_GET['session_id'] ?? null;
if (!$session_id) {
    die("No session ID provided.");
}


$stmt = $conn->prepare("SELECT a.id, s.name AS student_name,
 a.timestamp, a.longitude, a.latitude FROM attendance a  JOIN students s ON a.students = s.id 
 WHERE a.session_id = :session_id 
 ORDER BY a.timestamp ASC");

$stmt->execute([':session_id' => $session_id]);
$record = $stmt->fetchAll();


?>

<?php include __DIR__ . "/../partials/header.php";?>

<section class="attendance">
    <div class="contain_atten">
        <h1>Attendance List</h1>
        <p>Session ID: <?= htmlspecialchars($session_id) ?></p>

        <table>
            <thead>
                <tr>
                    <th>S/n</th>
                    <th>Student</th>
                    <th>Time</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($record as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= $row['timestamp'] ?></td>
                        <td><?= $row['latitude'] ?></td>
                        <td><?= $row['longitude'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


<footer>
    <p>&copy; 2026 AttendEase | Designed for University Attendance</p>
</footer>

<script src="../assets/js/main.js"></script>