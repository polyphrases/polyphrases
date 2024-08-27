<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/functions.php';
include __DIR__ . '/instatoken.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Establish a database connection
try {
    $pdo = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4", $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the next phrase to upload
$sql = "SELECT * FROM phrases WHERE uploaded = 0 ORDER BY id ASC LIMIT 1";
$result = $pdo->query($sql);

if ($result->rowCount() > 0) {
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $id = $row['id'];
    $date = $row['date'];
    $phrase = $row['phrase'];
    $spanish = $row['spanish'];
    $german = $row['german'];
    $italian = $row['italian'];
    $french = $row['french'];
    $portuguese = $row['portuguese'];

    $imagePath = 'https://polyphrases.com/images/' . $date . '.jpg';
    $caption = "$phrase\n\n" .
        "Español: $spanish\n\n" .
        "Italiano: $italian\n\n" .
        "Français: $french\n\n" .
        "Deutsch: $german\n\n" .
        "Português: $portuguese\n\n" .
        "#polygloths #languages";

    // Upload to Instagram
    $response = uploadToInstagram($imagePath, $caption, INSTAGRAM_ACCESS_TOKEN, $_ENV['INSTAGRAM_USER_ID']);

    if (isset($response['id'])) {
        // Update uploaded = 1
        $pdo->query("UPDATE phrases SET uploaded = 1 WHERE id = $id");
    }
}

function uploadToInstagram($imagePath, $caption, $accessToken, $userId)
{
    // Step 1: Upload the image
    $url = "https://graph.facebook.com/v20.0/$userId/media";
    $imageData = [
        'image_url' => $imagePath,
        'caption' => $caption,
        'access_token' => $accessToken,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($imageData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if (!isset($responseData['id'])) {
        echo 'NO CREATION ID YET';
        exit;
    }

    $creationId = $responseData['id'];

    // Step 2: Publish the image
    $publishUrl = "https://graph.facebook.com/v20.0/$userId/media_publish";
    $publishData = [
        'creation_id' => $creationId,
        'access_token' => $accessToken,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $publishUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($publishData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
