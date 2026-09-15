// Read editor config from data attribute and set as global variable
(function() {
    var configEl = document.getElementById('editor-config');
    if (configEl && configEl.dataset.config) {
        window.resumeEditorConfig = JSON.parse(configEl.dataset.config);
    }
})();
