(function() {
    var html = document.documentElement;
    var themeColor = html.getAttribute('data-theme-color');
    var themeColorRgb = html.getAttribute('data-theme-color-rgb');
    if (themeColor) {
        html.style.setProperty('--tblr-primary', themeColor);
    }
    if (themeColorRgb) {
        html.style.setProperty('--tblr-primary-rgb', themeColorRgb);
    }
})();
