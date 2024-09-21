<?php
require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Establish a database connection
try {
    $pdo = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4", $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the raw input from the webhook
$rawPayload = file_get_contents('php://input');

// Extract the signature and timestamp from the headers
$signature = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP'];

// Get the verification key from environment variables
$publicKey = $_ENV['SENDGRID_WEBHOOK_VERIFICATION_KEY'];
$payload = $timestamp . $rawPayload;

// Calculate the expected signature (HMAC and base64 encoding)
$calculatedBinarySignature = hash_hmac('sha256', $payload, base64_decode($publicKey), true);
$calculatedSignature = base64_encode($calculatedBinarySignature);

// Log additional debugging info
$logFile = __DIR__ . "/webhook-debug-" . uniqid() . ".txt";
$logData = [
    'received_signature' => $signature,
    'timestamp' => $timestamp,
    'payload' => $rawPayload,
    'public_key' => $publicKey, // Log the verification key
    'calculated_signature' => $calculatedSignature, // Log the calculated signature
    'binary_hmac' => bin2hex($calculatedBinarySignature) // Log the raw binary HMAC
];
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));

// Compare the calculated signature to the received signature
if (!hash_equals($calculatedSignature, $signature)) {
    // Log the failed verification data
    $failedVerificationLog = __DIR__ . "/webhook-failed-verification-" . uniqid() . ".txt";
    file_put_contents($failedVerificationLog, json_encode($logData, JSON_PRETTY_PRINT));

    // Respond with 401 Unauthorized if the signature is invalid
    http_response_code(401);
    exit('Invalid webhook signature');
}

// Process the webhook events after successful verification
$events = json_decode($rawPayload, true);
