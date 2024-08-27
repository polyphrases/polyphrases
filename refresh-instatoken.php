<?php
include __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
include __DIR__ . '/instatoken.php';

// Refresh the Instagram access token
$url = "https://graph.facebook.com/v20.0/oauth/access_token?grant_type=fb_exchange_token&client_id=" . $_ENV['FB_CLIENT_ID'] . "&client_secret=" . $_ENV['FB_CLIENT_SECRET'] . "&fb_exchange_token=" . INSTAGRAM_ACCESS_TOKEN;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $response = json_decode($response, true);
    print_r($response);
    $newToken = $response['access_token'];

    // Update instatoken.php
    $file = fopen('./instatoken.php', 'w');
    fwrite($file, "<?php\n");
    fwrite($file, "const INSTAGRAM_ACCESS_TOKEN = '$newToken';\n");
    fclose($file);
}

echo 'OK!' .  $newToken;

curl_close($ch);
