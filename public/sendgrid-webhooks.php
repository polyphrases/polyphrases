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

// Log the raw data, signature, and timestamp for debugging
$logFile = __DIR__ . "/webhook-debug-" . uniqid() . ".txt";
$logData = [
    'signature' => $signature,
    'timestamp' => $timestamp,
    'payload' => $rawPayload
];
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));

// Verify webhook signature using the public key
$publicKey = $_ENV['SENDGRID_WEBHOOK_VERIFICATION_KEY'];
$payload = $timestamp . $rawPayload;

// Calculate the hash and compare it with the signature
$calculatedSignature = base64_encode(hash_hmac('sha256', $payload, base64_decode($publicKey), true));

// Compare the signature to ensure it's valid
if (!hash_equals($calculatedSignature, $signature)) {
    // Log the failed verification
    file_put_contents(__DIR__ . "/webhook-failed-verification-" . uniqid() . ".txt", json_encode($logData, JSON_PRETTY_PRINT));

    // Respond with 401 Unauthorized if the signature is invalid
    http_response_code(401);
    exit('Invalid webhook signature');
}

// Process the webhook events after successful verification
$events = json_decode($rawPayload, true);

foreach ($events as $event) {
    $email = $event['email'];
    $eventType = $event['event'];

    // Check if the subscriber exists
    $stmt = $pdo->prepare("SELECT * FROM subscribers WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subscriber) {
        $id = $subscriber['id'];

        switch ($eventType) {
            case 'delivered':
                // Increment delivered counter
                $stmt = $pdo->prepare("UPDATE subscribers SET delivered = delivered + 1 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;

            case 'open':
                // Increment opens counter
                $stmt = $pdo->prepare("UPDATE subscribers SET opens = opens + 1 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;

            case 'click':
                // Increment clicks counter
                $stmt = $pdo->prepare("UPDATE subscribers SET clicks = clicks + 1 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;

            case 'unsubscribe':
                // Set verified to 8
                $stmt = $pdo->prepare("UPDATE subscribers SET verified = 8 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;

            case 'spamreport':
                // Set verified to 9
                $stmt = $pdo->prepare("UPDATE subscribers SET verified = 9 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;

            case 'dropped':
                // Set verified to 7
                $stmt = $pdo->prepare("UPDATE subscribers SET verified = 7 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;

            case 'bounce':
                // Set verified to 5
                $stmt = $pdo->prepare("UPDATE subscribers SET verified = 5 WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;
        }
    }
}

// Send a 200 OK response after successful processing
http_response_code(200);
echo "Webhook processed successfully.";
