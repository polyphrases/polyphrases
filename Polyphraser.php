<?php

namespace App;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/functions.php';

use Orhanerday\OpenAi\OpenAi;

class Polyphraser
{
    private $pdo;
    private $openAi;
    private $numExamples;
    private $adminEmail;
    private $environment;
    private $debugLog = [];

    public function __construct($openAiApiKey, $dbConfig, $numExamples = 25, $adminEmail = '', $environment = 'development')
    {
        $this->openAi = new OpenAi($openAiApiKey);
        $this->numExamples = $numExamples;
        $this->adminEmail = $adminEmail;
        $this->environment = $environment;
        $this->connectDatabase($dbConfig);
    }

    private function connectDatabase($dbConfig)
    {
        try {
            $this->pdo = new \PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
                $dbConfig['user'],
                $dbConfig['pass']
            );
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function generateAndInsertPhrase()
    {
        $phraseDate = $this->getNextPhraseDate();
        $examples = $this->fetchRandomExamples();
        $originalPhrase = rtrim($this->generateCreativePhrase($examples), '.');

        if ($originalPhrase) {
            $translations = $this->translateAll($originalPhrase);
            $this->insertPhraseIntoDatabase($phraseDate, $originalPhrase, $translations);
        }
    }

    private function getNextPhraseDate()
    {
        $stmt = $this->pdo->prepare("SELECT MAX(date) as last_date FROM phrases");
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result && $result['last_date'] ? date('Y-m-d', strtotime($result['last_date'] . ' +1 day')) : date('Y-m-d', strtotime('+1 day'));
    }

    private function fetchRandomExamples()
    {
        $stmt = $this->pdo->prepare("SELECT phrase FROM phrases WHERE date < CURDATE() ORDER BY RAND() LIMIT :numExamples");
        $stmt->bindValue(':numExamples', $this->numExamples, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function generateCreativePhrase($examples)
    {
        $examplesText = implode("\n- ", $examples);
        $temporalTenses = ['present', 'past', 'future'];
        $tense = $temporalTenses[array_rand($temporalTenses)];

        $response = $this->openAi->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    "role" => "system",
                    "content" => "You are a creative assistant who generates humorous, absurd, and imaginative phrases for language practice. Use unique combinations of everyday objects, activities, and professions to make the phrases fun and surprising, yet linguistically coherent. Here are some examples of the type of phrases I'm looking for:\n- " . $examplesText
                ],
                [
                    "role" => "user",
                    "content" => "Based on those examples, give me another phrase in " . $tense . " tense."
                ],
            ],
            'temperature' => 0.9,
            'max_tokens' => 100,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.7,
        ]);

        $data = json_decode($response);
        $phrase = $data->choices[0]->message->content ?? null;
        $this->logDebug("Generated Phrase: $phrase");
        return $phrase;
    }

    public function translate($phrase, $toLang, $fromLang = 'english')
    {
        $response = $this->openAi->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    "role" => "system",
                    "content" => "You are a skilled translator. Translate the following phrase from " . ucfirst($fromLang) . " to " . ucfirst($toLang) . ", maintaining the creativity and humor of the original phrase."
                ],
                [
                    "role" => "user",
                    "content" => $phrase
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 100,
        ]);

        $data = json_decode($response);
        $translation = rtrim($data->choices[0]->message->content, '.') ?? '';
        $this->logDebug("Translation from $fromLang to $toLang: $translation");
        return $translation;
    }

    private function translateAll($phrase)
    {
        $languages = ['spanish', 'german', 'italian', 'french', 'portuguese', 'norwegian'];
        $translations = [];

        foreach ($languages as $lang) {
            $translations[$lang] = $this->translate($phrase, $lang);
        }

        return $translations;
    }

    private function insertPhraseIntoDatabase($date, $phrase, $translations)
    {
        try {
            $sql = "INSERT INTO `phrases` (`date`, `phrase`, `spanish`, `german`, `italian`, `french`, `portuguese`, `norwegian`) 
                    VALUES (:date, :phrase, :spanish, :german, :italian, :french, :portuguese, :norwegian)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':phrase', $phrase);
            $stmt->bindParam(':spanish', $translations['spanish']);
            $stmt->bindParam(':german', $translations['german']);
            $stmt->bindParam(':italian', $translations['italian']);
            $stmt->bindParam(':french', $translations['french']);
            $stmt->bindParam(':portuguese', $translations['portuguese']);
            $stmt->bindParam(':norwegian', $translations['norwegian']);

            $stmt->execute();

            $generatedPhraseId = $this->pdo->lastInsertId();
            $this->logDebug("Generated Phrase ID: $generatedPhraseId");
            $this->notifyAdmin();

        } catch (\PDOException $e) {
            $this->handleError("Error generating daily phrase", $e->getMessage());
        }
    }

    private function logDebug($message)
    {
        $this->debugLog[] = $message;
    }

    private function notifyAdmin()
    {
        $emailContent = "<pre>" . implode("\n", $this->debugLog) . "</pre>";

        if ($this->environment === 'production' && !empty($this->adminEmail)) {
            send_email($this->adminEmail, 'A daily phrase was generated', $emailContent);
        } else {
            echo $emailContent;
        }
    }

    private function handleError($subject, $message)
    {
        if ($this->environment === 'production' && !empty($this->adminEmail)) {
            send_email($this->adminEmail, $subject, $message);
        } else {
            echo '<pre>' . $message . '</pre>';
        }
    }

    public function generateImage($phrase)
    {
        $response = $this->openAi->image([
            "model" => "dall-e-3",
            "style" => "vivid",
            "quality" => "hd",
            "prompt" => 'Creative, vivid, funny, fantastic: ' . $phrase,
            "n" => 1,
            "size" => "1024x1024",
            "response_format" => "url",
        ]);

        $data = json_decode($response, true);

        if (isset($data['data'][0]['url'])) {
            $imageUrl = $data['data'][0]['url'];
            $this->logDebug("Generated Image URL: $imageUrl");
            return $imageUrl;
        }

        return null;
    }

    public function saveImage($url, $date)
    {
        if ($url) {
            $image = file_get_contents($url);
            file_put_contents(__DIR__ . '/public/images/' . $date . '.jpg', $image);
        }
    }

    public function updateImageStatus($id)
    {
        $stmt = $this->pdo->prepare("UPDATE phrases SET imaged = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function fetchPhraseForImage($date = null)
    {
        if ($date) {
            $stmt = $this->pdo->prepare("SELECT * FROM phrases WHERE date = :date");
            $stmt->execute(['date' => $date]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM phrases WHERE imaged = 0 ORDER BY id ASC LIMIT 1");
            $stmt->execute();
        }

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
