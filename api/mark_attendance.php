<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json");

// ✅ Only students can mark attendance
if (!isset($_SESSION['user-id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $student_id = $_SESSION['user-id'];
    $course_id  = $data['course_id'] ?? null;
    $token      = $data['token'] ?? null;
    $latitude   = $data['latitude'] ?? null;
    $longitude  = $data['longitude'] ?? null;

    if (!$course_id || !$token || !$latitude || !$longitude) {
        echo json_encode(["success" => false, "message" => "Missing required data"]);
        exit;
    }

    // ✅ Validate QR session
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE session_code = :token AND course_id = :course_id");
    $stmt->execute([':token' => $token, ':course_id' => $course_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(["success" => false, "message" => "Invalid QR code"]);
        exit;
    }

    // ✅ Check expiry
    if (strtotime($session['expiry_time']) < time()) {
        echo json_encode(["success" => false, "message" => "QR code expired"]);
        exit;
    }

    // ✅ Location validation
    $school_lat = 6.4474;   // Example: Enugu campus latitude
    $school_long = 7.5139;  // Example: Enugu campus longitude
    $allowed_radius = 0.2;  // km (~200m)

    function haversine($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earth_radius * $c;
    }

    $distance = haversine($latitude, $longitude, $school_lat, $school_long);

    if ($distance > $allowed_radius) {
        echo json_encode(["success" => false, "message" => "You are outside the allowed location"]);
        exit;
    }

    // ✅ Prevent duplicate attendance
    $checkStmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = :student_id AND course_id = :course_id AND date = CURDATE()");
    $checkStmt->execute([':student_id' => $student_id, ':course_id' => $course_id]);
    if ($checkStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Attendance already marked"]);
        exit;
    }

    // ✅ Insert attendance record
    $insertStmt = $conn->prepare("INSERT INTO attendance (student_id, course_id, date, status, latitude, longitude) 
                                  VALUES (:student_id, :course_id, CURDATE(), 'present', :latitude, :longitude)");
    $insertStmt->execute([
        ':student_id' => $student_id,
        ':course_id' => $course_id,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    echo json_encode(["success" => true, "message" => "Attendance marked successfully"]);
}
?>
