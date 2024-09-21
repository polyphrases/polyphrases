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

// Initialize logging
$logFile = __DIR__ . "/webhook-debug-" . uniqid() . ".txt";
$logData = [];

try {
    // Get the raw payload and headers
    $rawPayload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE'] ?? null;
    $timestamp = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP'] ?? null;

    // Log raw payload, signature, and timestamp
    $logData['rawPayload'] = $rawPayload;
    $logData['signature'] = $signature;
    $logData['timestamp'] = $timestamp;

    // Public key from environment (Base64 format)
    $base64PublicKey = $_ENV['SENDGRID_WEBHOOK_VERIFICATION_KEY'];

    // Convert the Base64-encoded public key into PEM format
    $pemFormattedKey = "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split($base64PublicKey, 64, "\n") .
        "-----END PUBLIC KEY-----\n";

    $logData['pemFormattedKey'] = $pemFormattedKey;

    // Step 1: Decode the Base64-encoded signature
    $decodedSignature = base64_decode($signature);
    $logData['decodedSignature'] = bin2hex($decodedSignature); // Log binary signature

    // Step 2: Hash the concatenation of timestamp + payload using SHA-256
    $hashedPayload = hash('sha256', $timestamp . $rawPayload, true);
    $logData['hashedPayload'] = bin2hex($hashedPayload); // Log binary hashed payload

    // Step 3: Load the PEM-formatted public key and verify the signature using OpenSSL
    $publicKeyResource = openssl_pkey_get_public($pemFormattedKey);
    if ($publicKeyResource === false) {
        $logData['error'] = 'Invalid PEM public key';
        throw new Exception('Invalid PEM public key.');
    }

    // Step 4: Use OpenSSL to verify the ECDSA signature
    $verification = openssl_verify($hashedPayload, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);
    $logData['verification_result'] = $verification;

    if ($verification === 1) {
        // Signature is valid
        $logData['verification_status'] = 'Signature is valid.';
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
        $logData['verification_status'] = 'Invalid signature.';
        http_response_code(401);
        echo "Invalid webhook signature.";
    } else {
        // Some error occurred during the verification process
        $logData['verification_status'] = 'Error during signature verification.';
        http_response_code(500);
        echo "Error during signature verification.";
    }

    // Clean up the public key resource
    openssl_free_key($publicKeyResource);
} catch (Exception $e) {
    $logData['exception'] = $e->getMessage();
    http_response_code(500);
    echo "An error occurred.";
} finally {
    // Write the log data to the debug file
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}
