<?php
require __DIR__ . '/Polyphraser.php';

use App\Polyphraser;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dbConfig = [
    'host' => $_ENV['DB_HOST'],
    'dbname' => $_ENV['DB_NAME'],
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASS']
];

$generator = new Polyphraser(
    $_ENV['OPENAI_API_KEY'],
    $dbConfig,
    25, // Number of examples
    $_ENV['ADMIN_EMAIL'],
    $_ENV['CURRENT_ENV']
);

$generator->generateAndInsertPhrase();
