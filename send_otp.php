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
$honeypot = $data["honeypot"] ?? "";

if ($honeypot !== "") {
    echo json_encode(["success" => false, "message" => "Bot detected"]);
    exit;
}
if (!$username) {
    echo json_encode(["success" => false, "message" => "Missing username"]);
    exit;
}

$now = time();
$log_file = "otp_times.json";
$logs = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) : [];
$user_logs = $logs[$username] ?? [];

// $within_30_sec = array_filter($user_logs, fn($t) => $now - strtotime($t) < 30);
// $within_1_hour = array_filter($user_logs, fn($t) => $now - strtotime($t) < 3600);
// $within_today = array_filter($user_logs, fn($t) => date("Y-m-d", strtotime($t)) === date("Y-m-d", $now));

// if (count($within_30_sec) > 0) {
//     echo json_encode(["success" => false, "message" => "Please wait 30 seconds before requesting again."]);
//     exit;
// }
// if (count($within_1_hour) >= 4) {
//     echo json_encode(["success" => false, "message" => "Hourly limit (4) exceeded."]);
//     exit;
// }
// if (count($within_today) >= 10) {
//     echo json_encode(["success" => false, "message" => "Daily limit (10) exceeded."]);
//     exit;
// }

$otp = strval(rand(100000, 999999));

$otp_file = "otp_log.json";
$otp_data = file_exists($otp_file) ? json_decode(file_get_contents($otp_file), true) : [];
$otp_data[$username] = [
    "otp" => $otp,
    "time" => date("Y-m-d H:i:s")
];
file_put_contents($otp_file, json_encode($otp_data, JSON_PRETTY_PRINT));

$apiKey = "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"; // החלף ב־API KEY שלך

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'sender' => [
        'name' => 'Whisper OTP',
        'email' => 'no-reply@example.com'
    ],
    'to' => [[ 'email' => $username ]],
    'subject' => 'קוד התחברות',
    'htmlContent' => "<html><body><p>הקוד שלך הוא: <strong>$otp</strong></p></body></html>"
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "api-key: $apiKey",
    "Content-Type: application/json",
    "accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$user_logs[] = date("Y-m-d H:i:s", $now);
$logs[$username] = $user_logs;
file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(["success" => true, "message" => "OTP sent"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send email"]);
}
