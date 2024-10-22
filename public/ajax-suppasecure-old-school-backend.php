<?php
require '../vendor/autoload.php';
require '../includes/functions.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Establish a database connection
try {
    $pdo = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4", $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session
session_start();
if (!isset($_SESSION['current_subscriber'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_SESSION['points_tracker'])) {
    $_SESSION['points_tracker'] = [];
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];

if ($action === 'add_points') {
    // Get phrase_id and lang_code
    if (!isset($input['phrase_id']) || !isset($input['lang_code'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
        exit;
    }

    $phrase_id = intval($input['phrase_id']);
    $lang_code = $input['lang_code'];
    $subscriber_id = $_SESSION['current_subscriber'];

    // Create a unique key for this phrase and language
    $phrase_lang_key = $phrase_id . '_' . $lang_code;

    // Check if points have already been added for this phrase and language
    if (in_array($phrase_lang_key, $_SESSION['points_tracker'])) {
        // Points already added
        echo json_encode(['success' => false, 'message' => 'Points already awarded', 'new_points_total' => null]);
        exit;
    }

    // Add 5 points to the subscriber's points
    $update_stmt = $pdo->prepare("UPDATE subscribers SET points = points + 5 WHERE id = :id");
    $update_stmt->execute([':id' => $subscriber_id]);

    // Fetch the new total points
    $select_stmt = $pdo->prepare("SELECT points FROM subscribers WHERE id = :id");
    $select_stmt->execute([':id' => $subscriber_id]);
    $subscriber = $select_stmt->fetch(PDO::FETCH_ASSOC);

    $new_points_total = $subscriber['points'];

    // Add this phrase_lang_key to the points_tracker session array
    $_SESSION['points_tracker'][] = $phrase_lang_key;

    echo json_encode(['success' => true, 'message' => 'Points added', 'new_points_total' => $new_points_total]);
    exit;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
