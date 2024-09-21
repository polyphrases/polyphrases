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

// Get the raw payload and headers
$rawPayload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP'];

// Public key from environment (make sure it's in PEM format for OpenSSL)
$publicKey = $_ENV['SENDGRID_WEBHOOK_VERIFICATION_KEY'];

// Step 1: Decode the Base64-encoded signature
$decodedSignature = base64_decode($signature);

// Step 2: Hash the concatenation of timestamp + payload using SHA-256
$hashedPayload = hash('sha256', $timestamp . $rawPayload, true);

// Step 3: Load the public key and verify the signature using OpenSSL
$publicKeyResource = openssl_pkey_get_public($publicKey);
if ($publicKeyResource === false) {
    die('Invalid public key.');
}

// Step 4: Use OpenSSL to verify the ECDSA signature
$verification = openssl_verify($hashedPayload, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);

if ($verification === 1) {
    // Signature is valid
    echo "Webhook signature is valid.";

    // Process the webhook events
    $events = json_decode($rawPayload, true);
    foreach ($events as $event) {
        // Your event processing code here...
    }

    // Respond with 200 OK
    http_response_code(200);
} elseif ($verification === 0) {
    // Signature is invalid
    http_response_code(401);
    echo "Invalid webhook signature.";
} else {
    // Some error occurred during the verification process
    http_response_code(500);
    echo "Error during signature verification.";
}

// Clean up the public key resource
openssl_free_key($publicKeyResource);
