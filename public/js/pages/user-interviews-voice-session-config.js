// Read voice session config from data attribute and set as global variable
(function() {
    var configEl = document.getElementById('voice-session-config');
    if (configEl && configEl.dataset.config) {
        window.voiceSessionConfig = JSON.parse(configEl.dataset.config);
    }
})();
