<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$username = $data["username"] ?? "";
$otp = $data["otp"] ?? "";

if (!$username || !$otp) {
    echo json_encode(["success" => false, "message" => "Missing username or OTP"]);
    exit;
}

$otp_file = "otp_log.json";
if (!file_exists($otp_file)) {
    echo json_encode(["success" => false, "message" => "OTP storage file missing"]);
    exit;
}

$otp_data = json_decode(file_get_contents($otp_file), true);

if (!isset($otp_data[$username])) {
    echo json_encode(["success" => false, "message" => "No OTP found for this user"]);
    exit;
}

$entry = $otp_data[$username];
if ($entry["otp"] !== $otp) {
    echo json_encode(["success" => false, "message" => "Invalid OTP"]);
    exit;
}

if (time() - strtotime($entry["time"]) > 600) {
    echo json_encode(["success" => false, "message" => "OTP expired"]);
    exit;
}

$token = base64_encode($username . '|' . time());
echo json_encode([
    "success" => true,
    "token" => $token
]);
