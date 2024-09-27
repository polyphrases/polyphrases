<?php
if (!isset($view_phrase)) {
    exit;
}

// Define available languages and their respective 2-letter codes
$languages = [
    'english' => 'en',
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

<article class="phrase-viewer">
    <header>
        <a href="/" title="Poly Phrases"><h1>Poly Phrases</h1></a>
        <?php
        $bunch_of_cool_subtitles = array(
            'Practice your polyglot skills!',
            'Learn a new phrase every day!',
            'Improve your language skills!',
            'Become a polyglot!',
            'Master multiple languages!',
            'Speak like a native!',
            'Boost your language skills!',
            'Learn a new language!',
            'Become a language expert!',
            'Speak like a pro!',
            'Master a new language!',
            'Learn a new language every day!',
            'Expand your linguistic horizons!',
            'Words are your playground!',
            'Language learning, made fun!',
            'Fluent in fun!',
            'Speak to the world!',
            'Unleash your inner linguist!',
            'Unlock a new language today!',
            'Language is power, seize it!',
            'Make the world your conversation!',
            'Let\'s talk globally!',
            'Speak with confidence, in any tongue!',
            'From beginner to bilingual!',
            'Your daily dose of dialects!',
            'Talk the talk, in every language!',
            'Translate your dreams into reality!',
            'Step up your language game!',
            'Words that wander!',
            'Express yourself in new languages!',
            'Master the art of communication!',
            'Every language is a new adventure!',
            'Say hello to fluency!',
            'Find your voice in any language!',
            'Speak, learn, repeat!',
            'Linguistic love at first sight!',
            'Language fluency: No passport needed!',
            'Get fluent, fast!',
            'Speak the unspoken!',
            'Conquer conversations worldwide!',
            'Your language journey starts here!',
            'Fluency is a click away!',
            'Expand your mind, word by word!',
            'Global conversations start here!',
            'Crack the code of languages!',
            'From curious to confident!',
            'Talk like a local, anywhere!',
            'Unravel the mystery of languages!',
            'Speak fluently, live fully!',
            'Turn words into wonders!',
            'Connect across cultures!',
            'Travel through languages!',
            'Languages: Your next adventure!',
            'Dive into diversity!',
            'Let’s speak global!',
            'Be a citizen of the world!',
            'Start your language adventure!',
            'Speak new worlds into existence!',
            'Turn learning into a lifestyle!',
            'A new phrase, a new friend!',
            'Say it with flair!',
            'Speak beyond borders!',
            'Discover the rhythm of languages!',
            'The world speaks, so can you!',
            'Find your linguistic flow!',
            'Live, love, language!',
            'Build bridges with words!',
            'Speak the universal language of connection!',
            'Fuel your curiosity with languages!',
            'The world is your language classroom!',
            'Transform yourself through language!',
            'Converse like a native!',
            'Bring your language dreams to life!',
            'Speak to the world, one phrase at a time!',
            'Every word counts!',
            'Turn your tongue into a passport!',
            'Unleash your voice in every tongue!',
            'Embrace the power of words!',
            'Speak your way around the globe!',
            'Dare to dialogue in every dialect!',
            'Language mastery starts here!',
            'Get ready to gab in any language!',
            'Open your mind, one word at a time!',
            'Learn languages like a legend!',
            'Practice makes polyglots!',
            'Talk your way to the top!',
            'Travel with your tongue!',
            'A new language, a new perspective!',
            'Speak beyond limits!',
            'Say it like a native!',
            'Speak with style!',
            'Make your words wander!',
            'Chase words, not just dreams!',
            'Be a conversation starter in any language!',
            'The world is speaking, are you?',
            'The language of love and learning!',
            'Cross borders with your voice!',
            'From hello to eloquent!',
            'Language lessons for the adventurous!',
            'Dare to speak differently!',
            'Linguistic legends are made here!',
            'A world of words awaits!',
            'Unlock your multilingual potential!',
            'Your language journey is just a word away!',
            'Get tongue-twistingly good!',
            'Speak boldly, speak broadly!',
            'Where every phrase opens a new door!',
            'Learn languages, leave limits behind!',
            'Speak, smile, succeed!',
            'Find your flow in any language!',
            'Learn, laugh, linguist!',
            'Conquer the language jungle!',
            'Speak the world\'s language of fun!',
            'Where learning never gets lost in translation!',
            'Fluent fun for everyone!',
            'From zero to lingual hero!',
            'Words worth the wander!',
            'Let’s talk the talk!',
            'Banter like a local!',
            'Get vocal with your vocab!',
            'The ultimate language unlock!',
            'Learn phrases, make faces!',
            'Talk smarter, not harder!',
            'Get fluent, stay brilliant!',
            'Languages are your superpower!',
            'Be the polyglot you were born to be!',
            'Learn words that wow!',
            'From timid to talkative!',
            'Fluent in fabulous!',
            'Speak and spark connections!',
            'Where words take flight!',
            'Let’s make language magic!',
            'Be the voice of many!',
            'Phrase your way to fluency!',
            'New phrases, new faces!',
            'Speak with sparkle!',
            'Laugh in any language!',
            'Find your global groove!',
            'Talk to the world!',
            'Learn today, speak tomorrow!',
            'Step up your speaking game!',
            'Multilingual is the new black!',
            'Get addicted to accents!',
            'Language is limitless!',
            'Speak with no boundaries!',
            'It’s never too late to learn!',
            'Savor the sound of every syllable!',
            'Live the language, love the culture!',
            'Every word is a step closer!',
            'Express, don\'t stress!',
            'Break the language barrier!',
            'Your journey to fluency starts now!',
            'Let’s converse universally!',
            'The language of fun!',
            'From gibberish to genius!',
            'Speak boldly, live broadly!',
            'Your passport to global fluency!',
            'One click, many conversations!',
            'Talk the world!',
            'Where words know no walls!',
            'Find your linguistic spark!',
            'Be fluent in fantastic!',
            'Learn, connect, inspire!',
            'Every phrase is an adventure!',
            'From casual chats to cultural connections!',
            'Speak with soul!',
            'The world is your language oyster!',
            'A new language, a new you!',
            'Elevate your eloquence!',
            'From whispers to wisdom!',
            'A world of words in your pocket!',
            'Language is the spice of life!',
            'Fluent in fun, fluent in you!',
            'Speak easy, speak often!',
            'Let’s chat the world!',
            'Find your foreign flair!',
            'Be a linguistic trendsetter!',
            'Learn loud, learn proud!',
            'Speak the language of the future!',
            'Every word is a window!',
            'Uncover new dialects daily!',
            'Become a vocabulary virtuoso!',
            'Let’s make your words wander!',
            'Talk like you\'ve traveled the world!',
            'Let’s get gabby!',
            'Speak fearlessly, learn endlessly!',
            'From phrases to phrases!',
            'Make every word count!',
            'A new phrase for every place!',
            'Speak to explore!',
            'The journey to fluency starts here!',
            'Your next conversation is just a click away!',
            'Talk with the world, not just to it!',
            'Unlock a universe of expressions!',
            'A new word every day keeps boredom away!',
            'Dial up your dialect game!',
            'Global fluency, at your fingertips!',
            'Say more, say it better!',
            'Expand your expression arsenal!',
            'Join the language revolution!',
            'Words are your passport!',
            'Break the sound barrier!',
            'Speak in every shade!',
            'Unlock your linguistic legacy!',
            'From grammar to glamour!',
            'Be a language superstar!',
            'Talk across timelines!',
            'Speak your truth in every tongue!',
            'Languages live in your voice!',
            'Get your tongue around the world!',
            'A new phrase to dazzle!',
            'Step into the language light!',
            'Say it with style!',
            'Where words open worlds!',
            'Fluent and fearless!',
            'Speak with spark!',
            'Speak, dream, achieve!',
            'Dialects for days!',
            'Speak fresh, speak free!',
            'Words that wander with you!',
            'Voice your ventures!',
            'From babble to brilliance!',
            'Conquer continents with conversation!',
            'Language adventures await!',
            'Be the architect of your accents!',
            'A global gabfest!',
            'Speak up, the world is listening!',
            'Become a talking point!',
            'New words, new worlds!',
            'Speak and let live!',
            'Learn a language, live a culture!',
            'Be bilingual, be brilliant!',
            'Travel with every word!',
            'The art of being understood!',
            'From phrases to friends!',
            'Be a conversation catalyst!',
            'Linguistic learning for all!',
            'Embrace the global gab!',
            'Speak like you mean it!',
            'A language for every mood!',
            'Find the fun in fluency!',
            'Talk travels further!',
            'Get linguistically lit!',
            'Speak your way to new horizons!',
            'Where every word is a step!',
            'Say it, slay it!',
            'Fluent forever!',
            'From lost in translation to finding words!',
            'Speak, laugh, repeat!',
            'Make your words roar!',
            'Talk till you travel!',
            'Find your fluency fast!',
            'Be a language ninja!',
            'Phrase by phrase, find your place!',
            'Unlock your linguistic legend!',
            'Dive deep into dialects!',
            'Make every syllable count!',
            'Master every phrase, make every friend!',
            'Speak your heart out!',
            'Talk the world awake!',
            'Learn language, live life!',
            'Let’s get wordy!',
            'Fluency feels fabulous!',
            'Express without stress!',
            'Take your tongue for a spin!',
            'Speak with wonder!',
            'Your fluency, your way!',
            'Craft conversations confidently!',
            'Find your verbal vibe!',
            'From tongue-tied to terrific!',
            'Break out of your language box!',
            'Speak to succeed!',
            'Voice your versatility!',
            'Language is your playground!',
            'Find friends in every phrase!',
            'Say it boldly, say it often!',
            'Talk the world into your life!',
            'Discover the world through words!',
            'A new phrase, a new face!',
            'Speak the spice of life!',
            'Where words find a way!',
            'Build bridges, one word at a time!',
            'Your global voice starts here!',
            'Speak the world wide web!',
            'Master the language maze!',
            'Dialects are delightful!',
            'Lingo with a little zing!',
            'Speak the talk of the town!',
            'From phrases to praises!',
            'Learn the language of your dreams!',
            'Global words, local flavor!',
            'Feel the language love!',
            'Speak up, live more!',
            'Be a language leader!',
            'Phrase by phrase, feel the world!',
            'Speak smart, speak start!',
            'Globally gabby, locally lovely!',
            'Speak and shine!',
            'Word warriors unite!',
            'Get wordy with us!',
            'From hello to hero!',
            'Talk in tongues!',
            'Words that wander with wonder!',
            'Learn with laughter!',
            'The fluency fun zone!',
            'Say it with soul!',
            'Speak sweetly, swiftly!',
            'Linguistic learning that lingers!',
            'Phrase it to amaze it!',
            'Find your accent action!',
            'Speak bright, speak right!',
            'Let’s be language buddies!',
            'Every word a new world!',
            'The language of limitless learning!',
            'Say it, own it!',
            'Word by word, win the world!',
            'From phrases to phases!',
            'Talk the language of love!',
            'Your voice, your voyage!',
            'Master languages, master life!',
            'The talk of the town, in any tongue!',
            'Explore expressions!',
            'Speak cool, speak global!',
            'Linguistic luxury for all!',
            'Get gabby, get global!',
            'The world in words!',
            'Start small, speak big!',
            'Live fluently!',
            'From newbie to native!',
            'Speak spectacular!',
            'Conversations without borders!',
            'A new phrase, a new place!',
            'Your language journey begins now!',
            'Speak to inspire!',
            'Talk it up!',
            'Language legends are born here!',
            'Go global with your grammar!',
            'Chat the world’s way!',
            'Find your global gab!',
            'Speak and sparkle!',
            'Let your words wander!',
            'Master the globe with words!',
            'Let’s speak brilliantly!',
            'A phrase a day keeps dullness away!',
            'Talk with tenacity!',
            'Say it smart!',
            'Make words work for you!',
            'Find your linguistic light!',
            'Explore, express, excel!',
            'Language learning, your way!',
            'Speak and succeed!',
            'Find your fluent flow!',
            'Globally fluent, locally savvy!',
            'Unlock languages, unlock life!',
            'Talk and transform!',
            'Find the fun in every phrase!',
            'Speak boldly, learn globally!',
            'Words that wander, worlds that wonder!',
        );
        ?>
        <h2><?php
            echo $bunch_of_cool_subtitles[array_rand($bunch_of_cool_subtitles)];
            ?></h2>
    </header>
    <figure class="image">
        <img src="/images/<?php echo $view_phrase['date']; ?>.jpg" alt="<?php echo $view_phrase['phrase']; ?>"/>
    </figure>
    <hr>
    <?php foreach ($languages as $lang_name => $lang_code): ?>
        <h3>
            <span><?php echo ucfirst($lang_name); ?></span>
            <?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . $audio_base_path . $lang_code . '.mp3')) { ?>
                <button class="play-button" data-lang="<?php echo $lang_code; ?>"
                        data-date="<?php echo $view_phrase['date']; ?>" aria-label="Play audio">
                    ▶️
                </button>
            <?php } ?>
        </h3>
        <?php $db_field_name = $lang_name === 'english' ? 'phrase' : $lang_name; ?>
        <p><?php echo $view_phrase[$db_field_name]; ?></p>
    <?php endforeach; ?>
    <hr>
    <p class="flex-justify-center">
        <?php if ($_SESSION['visit_comes_from'] === 'email') { ?>
            <a class="button"
               href="https://wa.me/?text=<?php echo urlencode("Look at this, it's a cool service to practice your *language skills* for free, in a suppa funny way!\n\n" . $_ENV['SITE_URL'] . '/' . $view_phrase['date'] . '?from=whatsapp'); ?>">Share
                it 😃</a>
        <?php } else { ?>
            <a class="button" href="/subscribe">Get Daily Phrases ✉️</a>
        <?php } ?>
    </p>
</article>

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
                currentAudio.onended = function () {
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
