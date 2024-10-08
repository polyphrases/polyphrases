<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Establish a database connection
try {
    $pdo = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4", $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$lang = $_GET['lang'] ?? null;  // New: Check for 'lang' parameter
$date = $_GET['date'] ?? null;

// Check if $date is set
if (isset($date)) {
    // Get phrase by $date
    $stmt = $pdo->prepare("SELECT * FROM phrases WHERE date = :date");
    $stmt->execute(['date' => $date]);
} else {
    // Get phrase where audioed = 0
    $stmt = $pdo->prepare("SELECT * FROM phrases WHERE audioed = 0 ORDER BY id LIMIT 1");
    $stmt->execute();
}
$phrase = $stmt->fetch(PDO::FETCH_ASSOC);

if ($phrase) {
    // Fetch available voices
    $voices = fetchVoices();

    if (!empty($voices['voices'])) {
        $lang_db_field_map = array(
            'en' => 'phrase',
            'es' => 'spanish',
            'fr' => 'french',
            'de' => 'german',
            'it' => 'italian',
            'pt' => 'portuguese',
            'no' => 'norwegian',
        );

        // New: If a specific language is requested, generate voice only for that language
        if ($lang && isset($lang_db_field_map[$lang]) && isset($phrase[$lang_db_field_map[$lang]])) {
            $randomVoice = $voices['voices'][array_rand($voices['voices'])]['voice_id'];
            generateAudio($randomVoice, $phrase[$lang_db_field_map[$lang]] . '.', $lang, $phrase['date']);
        } else {
            // Original behavior: generate voices for all languages if no specific language is requested
            foreach ($lang_db_field_map as $lang => $db_field) {
                if ($phrase[$db_field]) {
                    $randomVoice = $voices['voices'][array_rand($voices['voices'])]['voice_id'];
                    generateAudio($randomVoice, $phrase[$db_field] . '.', $lang, $phrase['date']);
                }
            }
        }
    } else {
        echo "No voices found or failed to fetch voices.\n";
    }

    // Update the phrase to mark it as audioed
    $stmt = $pdo->prepare("UPDATE phrases SET audioed = 1 WHERE id = :id");
    $stmt->execute(['id' => $phrase['id']]);
} else {
    echo "No phrases found to audio.\n";
}

// Helper function to perform a GET request to fetch available voices
function fetchVoices()
{
    $ch = curl_init('https://api.elevenlabs.io/v1/voices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "xi-api-key:" . $_ENV['ELEVENLABS_API_KEY'],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Helper function to perform a POST request to generate audio
function generateAudio($voiceId, $text, $lang, $date)
{
    $ch = curl_init("https://api.elevenlabs.io/v1/text-to-speech/$voiceId/stream");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "xi-api-key:" . $_ENV['ELEVENLABS_API_KEY'],
        "Content-Type: application/json",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model_id' => 'eleven_turbo_v2_5',
        'text' => $text,
        'language_code' => $lang
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $filePath = __DIR__ . '/public/voices/' . $date . '-' . $lang . '.mp3';
        file_put_contents($filePath, $response);
    } else {
        echo "Failed to generate audio for language $lang using voice ID $voiceId\n";
    }
}