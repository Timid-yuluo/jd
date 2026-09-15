// Read interview session config from data attribute and set as global variable
(function() {
    var configEl = document.getElementById('interview-session-config');
    if (configEl && configEl.dataset.config) {
        window.interviewSessionConfig = JSON.parse(configEl.dataset.config);
    }
})();
