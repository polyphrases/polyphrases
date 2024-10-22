<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define a color variable for the email
$colors = array(
    '#0099e5',
    '#ff4c4c',
    '#00a98f',
    '#be0027',
    '#371777',
    '#008374',
    '#037ef3',
    '#f85a40',
    '#0cb9c1',
    '#f48924',
    '#da1884',
    '#a51890'
);
$emailColor = $colors[array_rand($colors)];

// Establish a database connection
try {
    $pdo = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch today's date
$today = date('Y-m-d');

// Fetch today's phrase
$stmt = $pdo->prepare("SELECT * FROM phrases WHERE date = :today LIMIT 1");
$stmt->execute([':today' => $today]);
$phrase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$phrase) {
    exit;
}

// Fetch up to 20 verified subscribers whose last_sent is less than today
$stmt = $pdo->prepare("SELECT * FROM subscribers WHERE verified = 1 AND last_sent < :today LIMIT 20");
$stmt->execute([':today' => $today]);
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no subscribers are found, stop the script
if (!$subscribers) {
    exit;
}

$hr_separator = '<hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">';

foreach ($subscribers as $subscriber) {
    $email = $subscriber['email'];

    // Validate email address
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Delete this subscriber
        $delete_stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = :id");
        $delete_stmt->execute([':id' => $subscriber['id']]);
        continue;
    }

    // Calculate engagement ratios
    $delivered = $subscriber['delivered'];
    $opens = $subscriber['opens'];
    $clicks = $subscriber['clicks'];

    // Decide whether to send email
    $send_email = false;

    if ($delivered == 0) {
        $click_ratio = 0;
        $open_ratio = 0;
    } else {
        $click_ratio = ($clicks / $delivered) * 100;
        $open_ratio = ($opens / $delivered) * 100;
    }

    // Do we really wanna send to this person?
    if ($delivered < 3 || $click_ratio > 49 || $open_ratio > 65) {
        $send_email = true;
    } else {
        // Randomly send if open ratio under 65 but above 35
        if ($open_ratio > 35) {
            if (mt_rand(1, 100) <= 70) {
                $send_email = true;
            }
        // Don't send to unengaged peepole, set them as stale
        } else {
            $stmt = $pdo->prepare("UPDATE subscribers SET verified = 6 WHERE id = :id");
            $stmt->execute(['id' => $subscriber['id']]);
        }
    }

    echo "\nChecking subscriber: " . $subscriber['id'] . " with open ratio: " . $open_ratio . "% (" . $opens . " of " . $delivered . ") and click ratio: " . $click_ratio . "% (" . $clicks . " of " . $delivered . ") --> Send?: " . ($send_email ? 'Yes' : 'No');

    // Generate the unsubscribe link
    $subscriber_token = generateToken($subscriber['id'], $email);
    $unsubscribe_link = $_ENV['SITE_URL'] . '/?id=' . urlencode($subscriber['id']) . '&token=' . urlencode($subscriber_token) . '&action=unsubscribe';

    $message = "<h1 style='color: $emailColor;'>Today's Phrase</h1>
    <p style='font-size:16px;padding:15px;background-color:$emailColor;color:#FFF;border-radius:8px;'>"
        . htmlspecialchars($phrase['phrase']) . "</p>" . $hr_separator;

    // Add translations based on subscriber's preferences
    if ($subscriber['spanish']) {
        $message .= "<p><strong>Spanish:</strong> " . htmlspecialchars($phrase['spanish']) . "</p>";
    }
    if ($subscriber['german']) {
        $message .= "<p><strong>German:</strong> " . htmlspecialchars($phrase['german']) . "</p>";
    }
    if ($subscriber['italian']) {
        $message .= "<p><strong>Italian:</strong> " . htmlspecialchars($phrase['italian']) . "</p>";
    }
    if ($subscriber['french']) {
        $message .= "<p><strong>French:</strong> " . htmlspecialchars($phrase['french']) . "</p>";
    }
    if ($subscriber['portuguese']) {
        $message .= "<p><strong>Portuguese:</strong> " . htmlspecialchars($phrase['portuguese']) . "</p>";
    }
    if ($subscriber['norwegian']) {
        $message .= "<p><strong>Norwegian:</strong> " . htmlspecialchars($phrase['norwegian']) . "</p>";
    }

    $message .= $hr_separator;

    // Show current consecutive days and points
    $consecutive_days = $subscriber['consecutive_days'];
    $points = $subscriber['points'];

    $point_str = $points == 1 ? "point" : "points";
    $day_str = $consecutive_days == 1 ? "day" : "days";

    if ($consecutive_days == 0) {
        $message .= "<p>🚀 You haven't started practicing yet! Click the button below to kickstart your language learning adventure! 😊</p>";
    } elseif ($consecutive_days == 1 && $points == 1) {
        $message .= "<p>🎉 Great start! You practiced for 1 day and earned 1 point! Let's keep the momentum going! 💪</p>";
    } elseif ($consecutive_days == 1) {
        $message .= "<p>👍 Great job! You practiced for 1 day and earned $points $point_str! Keep it up! 🌟</p>";
    } elseif ($consecutive_days == 2) {
        $message .= "<p>🔥 Yeah! You've practiced for 2 consecutive days and have earned $points $point_str! Keep going! 🏆</p>";
    } else {
        $message .= "<p>🏅 Fantastic! You've been practicing for $consecutive_days consecutive $day_str and have earned $points $point_str! Your dedication is paying off! Keep the streak alive! 🌠</p>";
    }

    $message .= '<p style="text-align:center;padding:20px;">
        <a href="' . $_ENV['SITE_URL'] . '/' . $phrase['date'] . '?from=email&id=' . urlencode($subscriber['id']) . '&token=' . urlencode($subscriber_token) . '" style="
            display:inline-block;
            background-color:#fff;
            text-decoration:none;
            padding:10px 16px;
            border-radius:5px;
            border:3px solid ' . $emailColor . ';
            font-size:16px;
            font-family:Helvetica,sans-serif;
            font-weight:bold;
            color:' . $emailColor . ';
            line-height:16px;">Open the practice mode! ✏️</a></p>';

    $message .= $hr_separator . "<p><i>Don't just ignore this. Take your time to learn the new vocabulary, a small step a day makes wonders!</i></p>";

    // Add image if exists
    $image_path = __DIR__ . '/public/images/' . $phrase['date'] . '.jpg';
    if (file_exists($image_path)) {
        $message .= "<img src='" . $_ENV['SITE_URL'] . '/images/' . $phrase['date'] . '.jpg' . "' alt='Descriptive image for this phrase' style='width:500px;max-width:100%;height:auto;border-radius:8px;'>";
    }

    $message .= $hr_separator . '
    <p>Poly Phrases | Day: <i>' . $today . '</i></p>
    <p style="margin-top:30px;font-size:11px;color:#555;">
        30 N Gould St Ste N, Sheridan, WY 82801 - 
        <a href="' . $unsubscribe_link . '" title="Unsubscribe from Poly Phrases">Unsubscribe</a>
    </p>';

    // Send the email (Use your own mail function or mail library)
    $subject = $phrase['phrase'];
    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    if ($send_email) {
        try {
            send_email($email, $encoded_subject, $message);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    // Update the subscriber's last_sent date to today even if decided to not send (avoid recalculating ratios today)
    $update_stmt = $pdo->prepare("UPDATE subscribers SET last_sent = :today WHERE id = :id");
    $update_stmt->execute([':today' => $today, ':id' => $subscriber['id']]);
}
