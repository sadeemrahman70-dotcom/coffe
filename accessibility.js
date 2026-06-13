// --- accessibility.js ---

// 1. Voice Settings (English)
function speakText(text) {
    window.speechSynthesis.cancel(); 
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'en-US'; // English Pronunciation
    utterance.rate = 1.0;
    window.speechSynthesis.speak(utterance);
}

// 2. Function to read text on mouse hover
const handleMouseOver = (e) => {
    const target = e.target.closest('button, a, h1, h2, h3, td, th, p, label, span, input, img');
    if (target) {
        let textToSpeak = target.innerText || target.getAttribute('aria-label') || target.placeholder || target.value;
        if (target.tagName === 'IMG') textToSpeak = target.alt || "Image";
        
        if (textToSpeak && textToSpeak.trim() !== "") {
            speakText(textToSpeak);
        }
    }
};

// 3. Enable Reader
function enableVoiceReader(notify) {
    localStorage.setItem('voiceReaderStatus', 'enabled');
    document.addEventListener('mouseover', handleMouseOver);
    updateButtonUI(true);
    if (notify) speakText("Voice reader enabled");
}

// 4. Disable Reader
function disableVoiceReader() {
    localStorage.setItem('voiceReaderStatus', 'disabled');
    document.removeEventListener('mouseover', handleMouseOver);
    window.speechSynthesis.cancel();
    updateButtonUI(false);
    speakText("Voice reader disabled");
}

// 5. Toggle Switch
function toggleVoiceReader() {
    const isEnabled = localStorage.getItem('voiceReaderStatus') === 'enabled';
    if (isEnabled) {
        disableVoiceReader();
    } else {
        enableVoiceReader(true);
    }
}

// 6. Update Button Appearance (English)
function updateButtonUI(isEnabled) {
    const btn = document.getElementById('global-accessibility-btn');
    if (btn) {
        if (isEnabled) {
            btn.innerHTML = "🔇 Stop Reader";
            btn.style.backgroundColor = "#1E6B3A"; // Green when active
        } else {
            btn.innerHTML = "📢 Start Reader";
            btn.style.backgroundColor = "#4A2C1D"; // Brown when inactive
        }
    }
}

// 7. Auto-initialize and Create Button on every page
(function init() {
    const run = () => {
        // Create the button if it doesn't exist
        if (!document.getElementById('global-accessibility-btn')) {
            const btn = document.createElement('button');
            btn.id = 'global-accessibility-btn';
            btn.onclick = toggleVoiceReader;
            // Styling the button to stay at the bottom right
            Object.assign(btn.style, {
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                zIndex: '9999',
                padding: '12px 20px',
                color: 'white',
                border: 'none',
                borderRadius: '50px',
                cursor: 'pointer',
                boxShadow: '0 4px 15px rgba(0,0,0,0.3)',
                fontWeight: 'bold',
                fontFamily: 'Arial, sans-serif'
            });
            document.body.appendChild(btn);
        }

        // Check if it was enabled in the previous page
        const isVoiceEnabled = localStorage.getItem('voiceReaderStatus') === 'enabled';
        if (isVoiceEnabled) {
            enableVoiceReader(false); // Enable without repeating "enabled" message
        } else {
            updateButtonUI(false);
        }
    };

    // Ensure the script runs after the body is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();