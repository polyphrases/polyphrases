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

// Verify webhook signature
$signature = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP'];

$publicKey = $_ENV['SENDGRID_WEBHOOK_VERIFICATION_KEY'];
$payload = $timestamp . file_get_contents('php://input');

// Verify signature using public key
if (!hash_equals(base64_encode(hash_hmac('sha256', $payload, base64_decode($publicKey), true)), $signature)) {
    http_response_code(401);
    exit('Invalid webhook signature');
}

// Process the webhook events
$events = json_decode(file_get_contents('php://input'), true);

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

// Send a 200 OK response
http_response_code(200);
echo "Webhook processed successfully.";
