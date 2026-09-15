window.resolveResumeOnboardingScope = function resolveResumeOnboardingScope() {
    const config = window.resumeEditorConfig || {};
    const configId = String(config.resumeId ?? '').trim();
    if (configId !== '' && configId !== 'null' && configId !== 'undefined') {
        return configId;
    }

    const match = String(window.location?.pathname || '').match(/\/resumes\/(\d+)\/editor/);
    if (Array.isArray(match) && match[1]) {
        return match[1];
    }

    return 'default';
};

window.resumeEditorOnboardingCookieKey = function resumeEditorOnboardingCookieKey() {
    return `resume_editor_onboarding_${window.resolveResumeOnboardingScope()}`;
};

window.resumeEditorSetOnboardingHidden = function resumeEditorSetOnboardingHidden() {
    const storageKey = `resume-editor-onboarding:${window.resolveResumeOnboardingScope()}`;
    try {
        window.localStorage.setItem(storageKey, 'hidden');
    } catch (error) {}
    try {
        const cookieKey = window.resumeEditorOnboardingCookieKey();
        document.cookie = `${cookieKey}=hidden; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax`;
    } catch (error) {}
};

window.resumeEditorOnboardingIsHidden = function resumeEditorOnboardingIsHidden() {
    const storageKey = `resume-editor-onboarding:${window.resolveResumeOnboardingScope()}`;
    let storageHidden = false;
    try {
        storageHidden = window.localStorage.getItem(storageKey) === 'hidden';
    } catch (error) {}

    let cookieHidden = false;
    try {
        const cookieKey = window.resumeEditorOnboardingCookieKey();
        const needle = `${cookieKey}=`;
        cookieHidden = String(document.cookie || '')
            .split(';')
            .map((chunk) => chunk.trim())
            .some((chunk) => chunk.startsWith(needle) && decodeURIComponent(chunk.slice(needle.length)) === 'hidden');
    } catch (error) {}

    return storageHidden || cookieHidden;
};

// Fallback when Alpine fails to initialize (e.g. CDN load issue).
window.resumeEditorDismissOnboarding = function resumeEditorDismissOnboarding(remember = false) {
    const overlay = document.getElementById('editor-onboarding-overlay');

    if (remember) {
        window.resumeEditorSetOnboardingHidden();
    }

    if (overlay) {
        overlay.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('editor-onboarding-overlay');
    const startBtn = document.getElementById('editor-onboarding-start');
    const dismissBtn = document.getElementById('editor-onboarding-dismiss');

    if (!overlay || !startBtn || !dismissBtn) {
        return;
    }

    // Hard guard: honor hidden state regardless of Alpine runtime branch.
    if (window.resumeEditorOnboardingIsHidden()) {
        overlay.style.display = 'none';
    }

    startBtn.addEventListener('click', () => window.resumeEditorDismissOnboarding(false));
    dismissBtn.addEventListener('click', () => window.resumeEditorDismissOnboarding(true));
});
