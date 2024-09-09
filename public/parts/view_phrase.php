<?php
if (!isset($view_phrase)) {
    exit;
}

// Define available languages and their respective 2-letter codes
$languages = [
    'spanish' => 'es',
    'german' => 'de',
    'italian' => 'it',
    'french' => 'fr',
    'portuguese' => 'pt',
    'norwegian' => 'no'
];

// Base path for audio files
$audio_base_path = '/voices/' . $view_phrase['date'] . '-';
?>

<div class="phrase-viewer">
    <h1><?php echo $view_phrase['phrase']; ?></h1>
    <figure class="image">
        <img src="/images/<?php echo $view_phrase['date']; ?>.jpg" alt="<?php echo $view_phrase['phrase']; ?>"/>
    </figure>
    <hr>

    <?php foreach ($languages as $lang_name => $lang_code): ?>
        <h2>
            <span><?php echo ucfirst($lang_name); ?></span>
            <?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . $audio_base_path . $lang_code . '.mp3')) { ?>
                <button class="play-button" data-lang="<?php echo $lang_code; ?>"
                        data-date="<?php echo $view_phrase['date']; ?>" aria-label="Play audio">
                    ▶️
                </button>
            <?php } ?>
        </h2>
        <p><?php echo $view_phrase[$lang_name]; ?></p>
    <?php endforeach; ?>

    <hr>
    <p class="flex-justify-center">
        <?php if($_SESSION['visit_comes_from'] === 'email'){ ?>
            <a class="button" href="https://wa.me/?text=<?php echo urlencode("Look at this, it's a cool service to practice your *language skills* for free, in a suppa funny way!\n\n" . $_ENV['SITE_URL'] . '/' . $view_phrase['date'] . '?from=whatsapp'); ?>">Share it 😃</a>
        <?php } else { ?>
            <a class="button" href="/subscribe">Receive a Daily Phrase via email! ✉️</a>
        <?php } ?>
    </p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const audioElements = {};
        let currentAudio = null;
        let currentButton = null;

        document.querySelectorAll('.play-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const lang = this.getAttribute('data-lang');
                const date = this.getAttribute('data-date');
                const audioFilePath = `/voices/${date}-${lang}.mp3`;

                // Stop currently playing audio if any
                if (currentAudio && !currentAudio.paused) {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                    if (currentButton) {
                        currentButton.textContent = '▶️';
                    }
                }

                // If clicking the same button again, stop playback
                if (currentAudio && this === currentButton) {
                    currentAudio = null;
                    currentButton = null;
                    return;
                }

                // Play the selected audio
                if (!audioElements[audioFilePath]) {
                    audioElements[audioFilePath] = new Audio(audioFilePath);
                }

                currentAudio = audioElements[audioFilePath];
                currentAudio.play();
                currentButton = this;
                this.textContent = '⏸️'; // Change to pause button

                // Handle audio ended event to reset the button
                currentAudio.onended = function() {
                    currentButton.textContent = '▶️';
                    currentAudio = null;
                    currentButton = null;
                };
            });
        });

        // Stop playing when the escape key is pressed
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                if (currentButton) {
                    currentButton.textContent = '▶️'; // Change back to play button
                }
                currentAudio = null;
                currentButton = null;
            }
        });
    });
</script>
