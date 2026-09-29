<?php
require_once "../config/database.php";

header("Content-Type: application/json");

// only students can mark attendance

if (!isset($_SESSION["user-id"]) || $_SESSION["role"] !== "student"){
    echo json_encode(["success" => false, "message" => "Unauthorized access. Please log in."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    $student_id = $_SESSION["user-id"];
    $token = $data["token"] ?? null;
    $latitude = $data["latitude"] ?? null;
    $longitude = $data["longitude"] ?? null;


    if(!$token || !$latitude || !$longitude){
    echo json_encode(["success"=> false,"message"=> "Missing required scanning or location data."]);
exit;
    }


    // 1. Validate QR session token
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE session_code = :token LIMIT 1");
    $stmt->execute([":token" => $token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$session){echo json_encode(["success" => false, "message" => "Invalid or non-existent QR code."]);}
$course_id = $session["course_id"];

if(strtotime($session["expiry_time"]) < time()){
    echo json_encode(["success"=> false, "message"=> "This QR code session has expired."]);
    exit;
}

// 3. Haversine Location Validation
$school_lat = 6.4474;   
    $school_long = 7.5139;  
    $allowed_radius = 0.2;  // 200 meters

    function haversine($lat1, $lon1, $lat2, $lon2){
        $earth_radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earth_radius * $c;
    }

    // 4. Check duplicate attendance

    $checkStmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = :studentId AND course_id = courseId AND date = CURDATE() ");
    $checkStmt->execute(["studentId" => $student_id, ":courseId" => $course_id]);

    if($checkStmt->fetch()){
echo json_encode(["success"=> false,"message"=> "You have already registered attendance for this class today."]);
    }

    // 5. Insert record
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, course_id, date, longitude, latitude) VALUES (:sI, :cI, :d, :lon, :lat)");

    $stmt->execute([":sI" => $student_id,
    ":cI" => $course_id, 
    ":lon" => $longitude,":lat" => $latitude]);

    echo json_encode(["success" => true, "message" => "Successfully registered your attendance!"]);
    exit;
}

