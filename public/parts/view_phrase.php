<?php
if (!isset($view_phrase)) {
    exit;
}

// Define available languages and their respective 2-letter codes
$languages = [
    'english'     => 'en',
    'spanish'     => 'es',
    'german'      => 'de',
    'italian'     => 'it',
    'french'      => 'fr',
    'portuguese'  => 'pt',
    'norwegian'   => 'no'
];

// Base path for audio files
$audio_base_path = '/voices/' . $view_phrase['date'] . '-';

// Prepare language data to avoid duplication
$language_data = [];

foreach ($languages as $lang_name => $lang_code) {
    $db_field_name = $lang_name === 'english' ? 'phrase' : $lang_name;
    $audio_file = $_SERVER['DOCUMENT_ROOT'] . $audio_base_path . $lang_code . '.mp3';
    $audio_exists = file_exists($audio_file);
    $phrase = $view_phrase[$db_field_name];

    $language_data[] = [
        'lang_name'     => $lang_name,
        'lang_code'     => $lang_code,
        'db_field_name' => $db_field_name,
        'audio_exists'  => $audio_exists,
        'phrase'        => $phrase
    ];
}
?>

<article class="phrase-viewer">
    <header>
        <a href="/" title="Poly Phrases"><h1>Poly Phrases</h1></a>
        <h2><?php echo get_cool_slogan(); ?></h2>
    </header>
    <figure class="image">
        <img src="/images/<?php echo $view_phrase['date']; ?>.jpg" alt="<?php echo htmlspecialchars($view_phrase['phrase']); ?>"/>
    </figure>
    <?php
    if (isset($_SESSION['current_subscriber']) && isset($subscriber)) {
        echo "<p class='subscriber-stats'><span><strong>Consecutive days:</strong> " . $subscriber['streak'] . "  </span><span><strong>Your Points:</strong> <span id='current-total-points'>" . $subscriber['points'] . "</span></span></p>";
    }
    ?>
    <hr>

    <?php foreach ($language_data as $lang): ?>
        <h3>
            <span><?php echo ucfirst($lang['lang_name']); ?></span>
            <span>
            <?php if ($lang['audio_exists']): ?>
                <button class="play-button" data-lang="<?php echo $lang['lang_code']; ?>" data-date="<?php echo $view_phrase['date']; ?>" aria-label="Play audio">
                    ▶️
                </button>
            <?php endif; ?>
                <?php if ($lang['lang_name'] !== 'english'): ?>
                    <?php
                    // Generate a unique ID for this exercise
                    $exercise_id = 'exercise_' . $lang['lang_code'];
                    ?>
                    <button class="reveal-button" data-exercise-id="<?php echo $exercise_id; ?>" aria-label="Reveal phrase">
                    👁️
                </button>
                <?php endif; ?>
            </span>
        </h3>
        <?php if ($lang['lang_name'] === 'english'): ?>
            <p><?php echo htmlspecialchars($lang['phrase']); ?></p>
        <?php else: ?>
            <?php
            // Get the phrase in this language
            $phrase = $lang['phrase'];

            // Split the phrase into tokens (words and punctuation)
            $tokens = preg_split('/(\P{L}+)/u', $phrase, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            // Collect indices of tokens that are words with at least 5 letters
            $eligible_indices = [];
            for ($i = 0; $i < count($tokens); $i++) {
                $token = $tokens[$i];
                // Check if token is a word (letters only)
                if (preg_match('/^\p{L}+$/u', $token) && mb_strlen($token) >= 5) {
                    $eligible_indices[] = $i;
                }
            }

            // Initialize selected indices array
            $selected_indices = [];

            // Maximum words to remove
            $max_words_to_remove = min(5, count($eligible_indices));

            // Shuffle eligible indices
            shuffle($eligible_indices);

            // Select up to 5 non-consecutive indices
            foreach ($eligible_indices as $index) {
                $is_adjacent = false;
                foreach ($selected_indices as $sel_index) {
                    if (abs($index - $sel_index) == 1) {
                        $is_adjacent = true;
                        break;
                    }
                }
                if (!$is_adjacent) {
                    $selected_indices[] = $index;
                    if (count($selected_indices) >= $max_words_to_remove) {
                        break;
                    }
                }
            }

            // Collect the missing words and replace them with placeholders
            $missing_words = [];
            foreach ($selected_indices as $index) {
                $missing_words[] = $tokens[$index];
                // Replace token with an array containing placeholder info
                $tokens[$index] = [
                    'type' => 'placeholder',
                    'original_word' => $tokens[$index],
                ];
            }

            // Shuffle the missing words
            shuffle($missing_words);
            ?>
            <div class="exercise" id="<?php echo $exercise_id; ?>" data-correct-phrase="<?php echo htmlspecialchars($phrase, ENT_QUOTES, 'UTF-8'); ?>" data-lang-code="<?php echo $lang['lang_code']; ?>" data-phrase-id="<?php echo $view_phrase['id']; ?>">
                <p>
                    <!-- Display the phrase with placeholders -->
                    <?php foreach ($tokens as $token): ?>
                        <?php if (is_array($token) && $token['type'] === 'placeholder'): ?>
                            <span class="placeholder" data-original-word="<?php echo htmlspecialchars($token['original_word'], ENT_QUOTES, 'UTF-8'); ?>">_______</span>
                        <?php else: ?>
                            <span class="constructed-word"><?php echo htmlspecialchars($token); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </p>
                <!-- Display the missing words as clickable items -->
                <div class="word-bank">
                    <?php foreach ($missing_words as $word): ?>
                        <span class="word-bank-word"><?php echo htmlspecialchars($word); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <hr>
    <p class="flex-justify-center">
        <?php if ($_SESSION['visit_comes_from'] === 'email'): ?>
            <a class="button" href="https://wa.me/?text=<?php echo urlencode("Look at this, it's a cool service to practice your *language skills* for free, in a super fun way!\n\n" . $_ENV['SITE_URL'] . '/' . $view_phrase['date'] . '?from=whatsapp'); ?>">Share it 😃</a>
        <?php else: ?>
            <a class="button" href="/subscribe">Get Daily Phrases ✉️</a>
        <?php endif; ?>
    </p>
</article>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Audio playback logic
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
                currentAudio.onended = function () {
                    currentButton.textContent = '▶️';
                    currentAudio = null;
                    currentButton = null;
                };
            });
        });

        // Exercise functionality
        const exercises = document.querySelectorAll('.exercise');

        exercises.forEach(function (exercise) {
            const wordBank = exercise.querySelector('.word-bank');
            const correctPhrase = exercise.dataset.correctPhrase.trim();
            const langCode = exercise.dataset.langCode;
            const phraseId = exercise.dataset.phraseId;
            const revealButton = document.querySelector(`.reveal-button[data-exercise-id="${exercise.id}"]`);

            // Get all word elements in the exercise
            const wordElements = exercise.querySelectorAll('span.constructed-word, span.placeholder');
            const placeholders = Array.from(exercise.querySelectorAll('.placeholder'));

            // Keep track of filled placeholders
            let filledPlaceholders = Array(placeholders.length).fill(null);

            // Function to add word to next available placeholder
            function addWordToPlaceholder(wordElement) {
                const word = wordElement.textContent;
                wordElement.remove();

                // Find the next unfilled placeholder
                let index = filledPlaceholders.findIndex(item => item === null);
                if (index !== -1) {
                    let placeholder = placeholders[index];
                    placeholder.textContent = word;
                    placeholder.classList.add('filled-placeholder');
                    filledPlaceholders[index] = word;

                    // Add event listener to allow removal
                    placeholder.addEventListener('click', function placeholderClickHandler() {
                        removeWordFromPlaceholder(placeholder, index);
                        placeholder.removeEventListener('click', placeholderClickHandler);
                    });

                    checkIfComplete();
                }
            }

            // Function to remove word from placeholder
            function removeWordFromPlaceholder(placeholder, index) {
                const word = placeholder.textContent;
                placeholder.textContent = '_______';
                placeholder.classList.remove('filled-placeholder');
                filledPlaceholders[index] = null;

                // Add word back to word bank
                const wordBankWordElement = document.createElement('span');
                wordBankWordElement.classList.add('word-bank-word');
                wordBankWordElement.textContent = word;
                wordBank.appendChild(wordBankWordElement);
                wordBankWordElement.addEventListener('click', function () {
                    addWordToPlaceholder(wordBankWordElement);
                });

                resetExerciseClass();

                // Show the reveal button if it was hidden
                if (revealButton && revealButton.style.display === 'none') {
                    revealButton.style.display = '';
                }

                // Remove 'solution' class when a word is removed
                exercise.classList.remove('solution');
            }

            // Function to check if exercise is complete
            function checkIfComplete() {
                if (wordBank.querySelectorAll('.word-bank-word').length === 0) {
                    // All placeholders filled
                    const constructedPhrase = Array.from(wordElements).map(el => el.textContent).join('').trim().replace(/\s+/g, '').toLowerCase();
                    const normalizedCorrectPhrase = correctPhrase.replace(/\s+/g, '').toLowerCase();

                    if (constructedPhrase === normalizedCorrectPhrase) {
                        // Correct
                        exercise.classList.remove('wrong');
                        if (!exercise.classList.contains('solution')) {
                            exercise.classList.add('good');

                            // Hide the reveal button
                            if (revealButton) {
                                revealButton.style.display = 'none';
                            }

                            // Check if user is logged in
                            const pointsElement = document.getElementById('current-total-points');
                            if (pointsElement) {
                                // User is logged in
                                // Send AJAX request to increment points
                                fetch('/ajax-suppasecure-old-school-backend.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        action: 'add_points',
                                        phrase_id: phraseId,
                                        lang_code: langCode
                                    })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Update the points displayed on the site
                                            pointsElement.textContent = data.new_points_total;
                                        }
                                    });
                            }
                        }
                    } else {
                        // Incorrect
                        exercise.classList.remove('good');
                        if (!exercise.classList.contains('solution')) {
                            exercise.classList.add('wrong');
                        }
                    }
                }
            }

            function resetExerciseClass() {
                exercise.classList.remove('good', 'wrong');

                // Show the reveal button if it was hidden
                if (revealButton && revealButton.style.display === 'none') {
                    revealButton.style.display = '';
                }
            }

            // Add event listeners to word bank words
            wordBank.querySelectorAll('.word-bank-word').forEach(function (wordElement) {
                wordElement.addEventListener('click', function () {
                    addWordToPlaceholder(wordElement);
                });
            });

            // Handle the reveal button for this exercise
            if (revealButton) {
                revealButton.addEventListener('click', function () {
                    // Replace placeholders with the correct words
                    placeholders.forEach(function (placeholder, idx) {
                        const originalWord = placeholder.getAttribute('data-original-word');
                        placeholder.textContent = originalWord;
                        placeholder.classList.add('filled-placeholder');
                        filledPlaceholders[idx] = originalWord;
                    });

                    // Empty word bank
                    if (wordBank) {
                        wordBank.innerHTML = '';
                    }

                    // Mark the exercise as 'solution'
                    exercise.classList.remove('good', 'wrong');
                    exercise.classList.add('solution');

                    // Hide the reveal button
                    revealButton.style.display = 'none';

                    // Allow the user to remove fillers
                    placeholders.forEach(function (placeholder, index) {
                        placeholder.addEventListener('click', function placeholderClickHandler() {
                            removeWordFromPlaceholder(placeholder, index);
                            placeholder.removeEventListener('click', placeholderClickHandler);
                        });
                    });
                });
            }
        });
    });
</script>
