<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function () {
function createResumeEditor(config = {}) {
    // === Non-reactive state (closure variables) ===
    // These are NOT tracked by Alpine's Proxy reactivity system,
    // reducing overhead for properties that never appear in template bindings.
    const _urls = {
        saveUrl: config.saveUrl ?? '',
        showUrl: config.showUrl ?? '',
        optimizeStreamUrl: config.optimizeStreamUrl ?? '',
        optimizeStreamPrewarmUrl: config.optimizeStreamPrewarmUrl ?? '',
        optimizeStreamPrewarmEnabled: config.optimizeStreamPrewarmEnabled === true,
        optimizeStreamPrewarmTtlSeconds: Number(config.optimizeStreamPrewarmTtlSeconds ?? 900),
        optimizePrimaryChannel: String(config.optimizePrimaryChannel ?? 'session'),
        deepProgressivePreviewEnabled: config.deepProgressivePreviewEnabled === true,
        deepProgressivePreviewTickMs: Number(config.deepProgressivePreviewTickMs ?? 1200),
        optimizeSessionEnabled: config.optimizeSessionEnabled ?? true,
        optimizeSessionCreateUrl: config.optimizeSessionCreateUrl ?? '',
        optimizeSessionHistoryUrl: config.optimizeSessionHistoryUrl ?? '',
        optimizeStrategyGainUrl: config.optimizeStrategyGainUrl ?? '',
        applyOptimizedUrl: config.applyOptimizedUrl ?? '',
        atsScoreUrl: config.atsScoreUrl ?? '',
        importDocumentUrl: config.importDocumentUrl ?? '',
        uploadAvatarUrl: config.uploadAvatarUrl ?? '',
        keywordExtractUrl: config.keywordExtractUrl ?? '',
        optimizeSectionUrl: config.optimizeSectionUrl ?? '',
        exportTaskCreateUrl: config.exportTaskCreateUrl ?? '',
        exportTaskStatusUrlTemplate: config.exportTaskStatusUrlTemplate ?? '',
    };

    const _state = {
        draftTimer: null,
        serverSnapshotSignature: '',
        lastPersistedDraftSignature: null,
        aiActionLastAt: {},
        pendingImportFile: null,
        activeOptimizeSessionId: null,
        moduleCompletionSnapshot: null,
        undoStack: [],
        redoStack: [],
        previewActionPrimed: false,
        previewActionIndex: null,
        insightsTimer: null,
        lastAtsSignature: null,
        lastOptimizeSignature: null,
        optimizePollAbort: null,
        deepProgressiveTimer: null,
    };

    return {
        modules: [],
        template: config.template ?? 'classic',
        theme: config.theme ?? 'blue',
        activeIndex: 0,
        detailOpen: false,
        detailIndex: null,
        previewActionMuted: false,
        previewActionVisible: false,
        previewActionStyle: '',
        rawContentForDiff: config.rawContentForDiff ?? '',
        optimizedContentForDiff: config.optimizedContentForDiff ?? '',
        diffOnlyChanges: false,
        diffDeltaText: '',
        selectedDiffSection: 'ALL',
        diffSections: [],
        diffRiskTips: [],
        diffHighlightedKeywords: { hit: [], miss: [] },
        diffHtmlOriginal: '',
        diffHtmlOptimized: '',
        streamTargetJob: config.streamTargetJob ?? '',
        streamTargetCompany: config.streamTargetCompany ?? '',
        streamTargetJobTitle: config.streamTargetJobTitle ?? '',
        streamTargetJobDescription: config.streamTargetJobDescription ?? '',
        optimizeGoals: Array.isArray(config.optimizeGoals) ? config.optimizeGoals : [],
        optimizeMode: config.optimizeMode ?? 'balanced',
        jdExtractedKeywords: [],
        selectedJdKeywords: Array.isArray(config.selectedJdKeywords) ? config.selectedJdKeywords : [],
        streamStatus: 'idle',
        streamText: '',
        streamHighlights: [],
        lastAiErrorMessage: '',
        lastAiAttemptedDrivers: [],
        optimizeChangesSummary: '',
        optimizeGapActions: [],
        optimizeDiffModules: [],
        keywordHighlightEnabled: false,
        optimizeMetricsCompare: null,
        optimizePollProgress: null,
        currentAiDriver: '',
        fallbackFromDriver: '',
        optimizeVersionHistory: [],
        selectedOptimizeVersionA: null,
        selectedOptimizeVersionB: null,
        regressionValidationSummary: null,
        keywordCoverageCurrent: null,
        keywordCoverageOptimized: null,
        moduleOptimizeSuggestions: [],
        moduleGroupedSuggestions: [],
        moduleScoreBreakdown: [],
        currentModuleOverallScore: Number(config.currentModuleOverallScore ?? 0),
        moduleScoreHistory: Array.isArray(config.moduleScoreHistory) ? config.moduleScoreHistory : [],
        moduleSnapshots: {},
        moduleSnapshotCompareSelection: {},
        moduleSnapshotCompareOptions: {},
        rawAutoFixPreview: {
            visible: false,
            index: null,
            before: '',
            after: '',
        },
        moduleStrategies: {},
        recommendedModuleOrder: [],
        suggestionConfirmPayload: null,
        suggestionConflictTips: [],
        keywordConflictTips: [],
        applySelectedModuleTypes: [],
        promptStrategyTemplate: config.promptStrategyTemplate ?? 'general',
        _strategyCategoryFilter: 'all',
        advancedModelEnabled: config.advancedModelEnabled === true,
        priorityQueueEnabled: config.priorityQueueEnabled === true,
        planUpgradeUrl: config.planUpgradeUrl ?? '/user/pricing',
        atsScoring: false,
        importing: false,
        importPreviewModules: [],
        importPreviewRawText: '',
        importPreviewTargetJob: '',
        importPreviewModuleStats: {},
        importPreviewFileName: '',
        importPreviewMeta: {},
        dirty: false,
        isSubmitting: false,
        aiActionLocks: {
            streamOptimize: false,
            atsScore: false,
            applyOptimized: false,
        },
        collapsedModules: new Set(),
        previewDockOpen: false,
        previewToolTab: 'style',
        showShortcuts: false,
        fontFamily: config.fontFamily ?? "'PingFang SC','Microsoft YaHei','Hiragino Sans GB','WenQuanYi Micro Hei',sans-serif",
        fontSize: config.fontSize ?? '13.5',
        lineHeight: config.lineHeight ?? '1.7',
        headingFontSize: config.headingFontSize ?? '1.11',
        headingColor: config.headingColor ?? '',
        accentColor: config.accentColor ?? '',
        bodyFontColor: config.bodyFontColor ?? '',
        sectionSpacing: config.sectionSpacing ?? '18',
        layoutProfile: config.layoutProfile ?? 'custom',
        titleStyleVariant: config.titleStyleVariant ?? 'template',
        leftColumnRatio: config.leftColumnRatio ?? '50',
        layoutDragIndex: null,
        detailSettingsOpen: true,
        resumeAtsScore: config.resumeAtsScore ?? null,
        previewScale: 100,
        isPreviewFullscreen: false,
        a4PageHeightPx: 0,
        a4OverflowPx: 0,
        a4AutoFitEnabled: true,
        a4AutoFitScale: 1,
        a4AutoFitMinScale: 0.78,
        a4AutoFitSkippedForLongContent: false,
        resumeId: config.resumeId ?? null,
        draftAvailable: false,
        draftPayload: null,
        lastDraftSavedAt: null,
        draftSaveState: 'idle',
        showOnboarding: false,
        onboardingPreviewTouched: false,
        optimizeSessionHistory: [],
        optimizeSessionHistoryLoading: false,
        optimizeStrategyGainRows: [],
        freshGraduateGuideDontShowAgain: false,
        freshGraduateJustAddedType: '',
        freshGraduateJustAddedAt: 0,
        freshGraduateFocusFallbackHintAt: 0,
        aiPanelReturnToWorkbench: false,

        config: config,

        @include('user.resumes.editor-partials.editor-script-domains.init-helpers')

        init() {
            this.initModules();
            this.ensureAuthenticityGoal();
            this.enforcePlanFeatureConstraints(true);
            this.$nextTick(() => this.refreshJdKeywordsFromDescription(false));
            this.$nextTick(() => this.scheduleInsightsRefresh(0));
            this.$nextTick(() => this.maybeSuggestFreshGraduatePreset());
            _state.serverSnapshotSignature = this.editorStateSignature();
            this.checkRestorableDraft();
            this.maybeShowOnboarding();
            this.captureModuleCompletionSnapshot();
            this.initSortable();
            this.$nextTick(() => this.loadOptimizeStrategyGain());
            if (this.config.optimizedJustDone && this.optimizedContentForDiff !== '') {
                this.$nextTick(() => this.openOptimizedDiff());
            }
            this.initEventListeners();
            this.initKeyboardShortcuts();
            this.initWatchers();
            this.initAutoSave();
            this.$nextTick(() => this.syncPreviewModuleAction());
        },

        scheduleInsightsRefresh(delay = 300) {
            if (_state.insightsTimer) clearTimeout(_state.insightsTimer);
            _state.insightsTimer = setTimeout(() => this.refreshOptimizeInsights(), delay);
        },


        @include('user.resumes.editor-partials.editor-script-domains.draft')
        @include('user.resumes.editor-partials.editor-script-domains.editor-helpers')

        @include('user.resumes.editor-partials.editor-script-domains.preview-sync')

        @include('user.resumes.editor-partials.editor-script-domains.import')
        @include('user.resumes.editor-partials.editor-script-domains.module-management')
        @include('user.resumes.editor-partials.editor-script-domains.ai-tools')
        @include('user.resumes.editor-partials.editor-script-domains.form-utilities')

        @include('user.resumes.editor-partials.editor-script-domains.diff-viewer')

        @include('user.resumes.editor-partials.editor-script-domains.export')
        @include('user.resumes.editor-partials.editor-script-domains.inline-edit')
        @include('user.resumes.editor-partials.editor-script-domains.onboarding')
    };
}

window.resumeEditor = function resumeEditor() {
    return createResumeEditor(window.resumeEditorConfig || {});
};

@include('user.resumes.editor-partials.editor-script-domains.onboarding-globals')
})();
</script>
