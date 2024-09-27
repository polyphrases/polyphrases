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
$logFile = __DIR__ . "/webhook-log-" . uniqid() . ".txt";
$logData = [];

try {
    // Get the raw payload from the webhook
    $rawPayload = file_get_contents('php://input');

    // Log the raw payload
    $logData['rawPayload'] = $rawPayload;

    // Decode the JSON payload to process the event
    $events = json_decode($rawPayload, true);

    // Check if the payload was properly decoded
    if ($events === null) {
        // Log the decoding failure
        $logData['result'] = 'Failed to decode JSON payload.';
        http_response_code(400); // Bad Request
        echo "Invalid JSON.";
    } else {
        // Process the events and update the database
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
                        $logData['event'][] = "Delivered event processed for $email.";
                        break;

                    case 'open':
                        // Increment opens counter
                        $stmt = $pdo->prepare("UPDATE subscribers SET opens = opens + 1 WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $logData['event'][] = "Open event processed for $email.";
                        break;

                    case 'click':
                        // Increment clicks counter
                        $stmt = $pdo->prepare("UPDATE subscribers SET clicks = clicks + 1 WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $logData['event'][] = "Click event processed for $email.";
                        break;

                    case 'unsubscribe':
                        // Set verified to 8
                        $stmt = $pdo->prepare("UPDATE subscribers SET verified = 8 WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $logData['event'][] = "Unsubscribe event processed for $email.";
                        break;

                    case 'spamreport':
                        // Set verified to 9
                        $stmt = $pdo->prepare("UPDATE subscribers SET verified = 9 WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $logData['event'][] = "Spamreport event processed for $email.";
                        break;

                    case 'dropped':
                        // Set verified to 7
                        $stmt = $pdo->prepare("UPDATE subscribers SET verified = 7 WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $logData['event'][] = "Dropped event processed for $email.";
                        break;

                    case 'bounce':
                        // Set verified to 5
                        $stmt = $pdo->prepare("UPDATE subscribers SET verified = 5 WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $logData['event'][] = "Bounce event processed for $email.";
                        break;
                }
            } else {
                $logData['event'][] = "Subscriber with email $email not found.";
            }
        }

        // Log the success
        $logData['result'] = 'Webhook received and processed successfully.';
        http_response_code(200); // OK
        echo "Webhook received.";
    }
} catch (Exception $e) {
    // Log any errors
    $logData['error'] = $e->getMessage();
    http_response_code(500); // Internal Server Error
    echo "An error occurred.";
} finally {
    // Write the log data to the log file
    // file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}
