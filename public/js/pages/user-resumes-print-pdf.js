function setPrintToolbarStatus(message, isError = false) {
    const statusEl = document.getElementById('print-toolbar-status');
    if (!statusEl) {
        return;
    }
    statusEl.textContent = message || '';
    statusEl.style.color = isError ? '#fca5a5' : '#cbd5e1';
}

function sanitizeFilename(value) {
    return String(value || 'resume_export')
        .replace(/[\\/:*?"<>|]+/g, '_')
        .replace(/\s+/g, '_')
        .slice(0, 80);
}

function resumePrintRenderer(config = {}) {
    const modules = Array.isArray(config.modules) ? config.modules : [];

    return {
        modules: modules.map(function(mod, index) {
            const next = mod && typeof mod === 'object' ? { ...mod } : { type: 'summary', data: {} };
            next.data = next.data && typeof next.data === 'object' ? next.data : {};
            next._key = next._key || String(next.id || '') || ('print_' + index);
            return next;
        }),
        template: String(config.template || 'classic'),
        theme: String(config.theme || 'blue'),
        leftColumnRatio: String(config.leftColumnRatio || 50),

        moduleDisplayDefaultsByType(type) {
            const base = {
                showBadge: false,
                badgeText: '',
                showDivider: true,
                showSubtitle: true,
                showDate: true,
                showLocation: true,
                itemMarker: 'dot',
            };
            const map = {
                personal: { ...base, showDivider: false, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'none' },
                objective: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'none' },
                summary: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'none' },
                skill: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'dot' },
                certificate: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'dot' },
            };
            return map[String(type || '')] || base;
        },

        moduleDisplayConfig(mod) {
            const defaults = this.moduleDisplayDefaultsByType(mod?.type);
            const current = mod?.data?._display && typeof mod.data._display === 'object'
                ? mod.data._display
                : {};
            return {
                ...defaults,
                ...current,
                badgeText: String(current.badgeText || ''),
                itemMarker: ['dot', 'dash', 'none'].includes(String(current.itemMarker || defaults.itemMarker))
                    ? String(current.itemMarker || defaults.itemMarker)
                    : defaults.itemMarker,
                layoutColumn: ['auto', 'left', 'right', 'full'].includes(String(current.layoutColumn || 'auto'))
                    ? String(current.layoutColumn || 'auto')
                    : 'auto',
                headingFontSize: current.headingFontSize || '',
                headingColor: current.headingColor || '',
                accentColor: current.accentColor || '',
                sectionSpacing: current.sectionSpacing || '',
                bodyFontColor: current.bodyFontColor || '',
                bodyFontSize: current.bodyFontSize || '',
                bodyLineHeight: current.bodyLineHeight || '',
            };
        },

        moduleDefaultBadgeText(mod) {
            const type = String(mod?.type || '');
            const map = {
                objective: '核心',
                education: '教育',
                experience: '经历',
                project: '项目',
                skill: '技能',
                certificate: '证书',
                summary: '亮点',
            };
            return map[type] || (this.moduleTypeLabel(type) || '模块');
        },

        moduleTypeLabel(type) {
            const map = {
                personal: '个人信息',
                objective: '求职意向',
                education: '教育经历',
                experience: '工作/实习经历',
                project: '项目经验',
                skill: '技能证书',
                certificate: '获奖证书',
                summary: '自我评价',
            };
            return map[String(type || '')] || String(type || '模块');
        },

        defaultModuleLayoutForTemplate(type, templateOverride = '') {
            const template = String(templateOverride || this.template || 'classic');
            const typeKey = String(type || '');
            const defaults = {
                classic: { personal: 'full', objective: 'full', summary: 'full', skill: 'right', certificate: 'right', education: 'left', experience: 'left', project: 'right' },
                minimal: { personal: 'full', objective: 'full', summary: 'full', skill: 'right', certificate: 'right', education: 'left', experience: 'left', project: 'right' },
                creative: { personal: 'full', objective: 'full', skill: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', certificate: 'right' },
                modern: { personal: 'left', skill: 'left', summary: 'left', objective: 'right', education: 'right', experience: 'right', project: 'right', certificate: 'right' },
                elegant: { objective: 'left', experience: 'left', project: 'left', education: 'right', skill: 'right', certificate: 'right', summary: 'right', personal: 'full' },
                timeline: { personal: 'full', objective: 'full', summary: 'full', experience: 'left', project: 'left', education: 'left', skill: 'right', certificate: 'right' },
                professional: { personal: 'left', skill: 'left', summary: 'left', certificate: 'left', objective: 'right', education: 'right', experience: 'right', project: 'right' },
                academic: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                internet: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                executive: { personal: 'full', objective: 'left', experience: 'left', project: 'left', education: 'right', skill: 'right', certificate: 'right', summary: 'right' },
                startup: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                student: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                medical: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                teacher: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                lawyer: { personal: 'left', skill: 'left', objective: 'left', summary: 'left', education: 'right', experience: 'right', project: 'right', certificate: 'right' },
                finance: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                consulting: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                designer: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                government: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
                freelancer: { personal: 'full', objective: 'full', summary: 'full', education: 'left', experience: 'left', project: 'right', skill: 'right', certificate: 'right' },
            };
            return (defaults[template] && defaults[template][typeKey]) || 'auto';
        },

        moduleLayoutValue(mod, templateOverride = '') {
            const explicit = String(mod?.data?._display?.layoutColumn || '').trim();
            if (explicit && explicit !== 'auto') {
                return explicit;
            }
            return this.defaultModuleLayoutForTemplate(mod?.type, templateOverride);
        },

        moduleGridColumnStyle(mod, options = {}) {
            const layout = this.moduleLayoutValue(mod, options.template || '');
            const fallbackFullTypes = Array.isArray(options.defaultFullTypes) ? options.defaultFullTypes : [];
            if (layout === 'full' || (layout === 'auto' && fallbackFullTypes.includes(String(mod?.type || '')))) return 'grid-column: 1 / -1;';
            if (layout === 'left') return 'grid-column: 1;';
            if (layout === 'right') return 'grid-column: 2;';
            return '';
        },

        clampedLeftColumnRatio() {
            const raw = Number(this.leftColumnRatio || 50);
            if (Number.isNaN(raw)) return 50;
            return Math.max(35, Math.min(65, raw));
        },

        rightColumnRatio() {
            return 100 - this.clampedLeftColumnRatio();
        },

        dualColumnGridStyle(gap = '18px') {
            return `display: grid; grid-template-columns: minmax(0, ${this.clampedLeftColumnRatio()}%) minmax(0, ${this.rightColumnRatio()}%); gap: ${gap}; align-items: start;`;
        },

        splitColumnStyle(column) {
            const left = this.clampedLeftColumnRatio();
            const right = this.rightColumnRatio();
            if (column === 'left') return `flex: 0 0 ${left}%; max-width: ${left}%;`;
            if (column === 'right') return `flex: 0 0 ${right}%; max-width: ${right}%;`;
            return '';
        },

        moduleBelongsToColumn(mod, column, templateOverride = '') {
            const layout = this.moduleLayoutValue(mod, templateOverride);
            if (column === 'left') return layout === 'left';
            if (column === 'right') return layout === 'right' || layout === 'full';
            if (column === 'full') return layout === 'full';
            return false;
        },

        moduleStyleOverride(mod) {
            const d = mod?.data?._display;
            if (!d) return '';
            let s = '';
            if (d.sectionSpacing) s += `margin-bottom: ${d.sectionSpacing}px;`;
            if (d.headingFontSize) s += `--heading-fs: ${d.headingFontSize}em;`;
            if (d.headingColor) s += `--heading-c: ${d.headingColor};`;
            if (d.accentColor) s += `--accent-c: ${d.accentColor};`;
            if (d.bodyFontColor) s += `--body-c: ${d.bodyFontColor};`;
            if (d.bodyFontSize) s += `--body-fs: ${d.bodyFontSize}px;`;
            if (d.bodyLineHeight) s += `--body-lh: ${d.bodyLineHeight};`;
            return s;
        },

        inlineUpdate() {},
        inlineUpdateItem() {},
        onItemEnter() {},
        removeInlineItem() {},
        addInlineItem() {},
    };
}

window.resumePrintRenderer = resumePrintRenderer;

function getSelectedPaperSize() {
    const select = document.getElementById('paper-size-select');
    if (!select) {
        return { width: 210, height: 297, unit: 'mm' };
    }
    const selected = select.options[select.selectedIndex];
    return {
        width: parseFloat(selected.dataset.width) || 210,
        height: parseFloat(selected.dataset.height) || 297,
        unit: selected.dataset.unit || 'mm',
    };
}

function applyPaperSizeToDocument(doc, paperSize) {
    const root = doc?.documentElement;
    if (!root) {
        return;
    }
    root.style.setProperty('--paper-width', paperSize.width + paperSize.unit);
    root.style.setProperty('--paper-height', paperSize.height + paperSize.unit);
}

function applyPaperSizeToCss(paperSize) {
    applyPaperSizeToDocument(document, paperSize);
}

function unitToPx(value, unit) {
    const n = Number(value || 0);
    if (unit === 'mm') return n * (96 / 25.4);
    if (unit === 'cm') return n * (96 / 2.54);
    if (unit === 'in') return n * 96;
    if (unit === 'pt') return n * (96 / 72);
    if (unit === 'px') return n;
    return n;
}

function getInnerContentHeightPx(paperSize, paddingMm = 15) {
    const paperHeightPx = unitToPx(paperSize.height, paperSize.unit);
    const paddingPx = unitToPx(paddingMm, 'mm');
    return Math.max(10, paperHeightPx - paddingPx * 2);
}

function fitSinglePageContent(page, paperSize, pageIndex, totalPages) {
    if (!page) {
        return { mode: 'single', scale: 1 };
    }

    const contentHeightPx = getInnerContentHeightPx(paperSize, 15);
    const minSinglePageScale = 0.78;

    const inner = page.querySelector('.print-page-inner');
    const renderCtx = page.querySelector('.resume-render-context');

    if (!renderCtx) {
        page.dataset.pageMode = 'single';
        return { mode: 'single', scale: 1 };
    }

    renderCtx.style.transform = '';
    renderCtx.style.width = '100%';
    renderCtx.style.height = 'auto';
    renderCtx.style.maxHeight = 'none';
    renderCtx.style.overflow = 'visible';
    renderCtx.style.transformOrigin = 'top left';

    const naturalHeight = Math.ceil(renderCtx.scrollHeight || 0);
    const requiredScale = naturalHeight > 1 ? (contentHeightPx / naturalHeight) : 1;

    if (requiredScale < minSinglePageScale) {
        page.dataset.pageMode = 'multi';
        page.style.overflow = 'visible';
        page.style.height = 'auto';
        page.style.minHeight = 'var(--paper-height, 297mm)';
        page.style.width = 'var(--paper-width, 210mm)';
        page.style.pageBreakAfter = 'auto';
        page.style.breakAfter = 'auto';

        if (inner) {
            inner.style.overflow = 'visible';
            inner.style.height = 'auto';
            inner.style.maxHeight = 'none';
            inner.style.padding = '15mm';
            inner.style.boxSizing = 'border-box';
        }

        renderCtx.dataset.a4FitScale = '1';
        return { mode: 'multi', scale: 1 };
    }

    page.dataset.pageMode = 'single';
    page.style.overflow = 'hidden';
    page.style.height = 'var(--paper-height, 297mm)';
    page.style.minHeight = 'var(--paper-height, 297mm)';
    page.style.width = 'var(--paper-width, 210mm)';
    page.style.pageBreakAfter = pageIndex === totalPages - 1 ? 'auto' : 'always';
    page.style.breakAfter = pageIndex === totalPages - 1 ? 'auto' : 'page';

    if (inner) {
        inner.style.overflow = 'hidden';
        inner.style.height = 'calc(var(--paper-height, 297mm) - 30mm)';
        inner.style.maxHeight = 'calc(var(--paper-height, 297mm) - 30mm)';
        inner.style.padding = '15mm';
        inner.style.boxSizing = 'border-box';
    }

    if (naturalHeight > contentHeightPx + 1) {
        const scale = Math.max(minSinglePageScale, Math.min(1, requiredScale));
        renderCtx.style.transform = `scale(${scale})`;
        renderCtx.style.width = `${100 / scale}%`;
        renderCtx.dataset.a4FitScale = String(scale);
        return { mode: 'single', scale };
    }

    renderCtx.dataset.a4FitScale = '1';
    return { mode: 'single', scale: 1 };
}

function applySinglePageFitToRoot(rootNode, paperSize, ownerDocument = document) {
    if (!rootNode) {
        return { hasMultiPageMode: false };
    }

    applyPaperSizeToDocument(ownerDocument, paperSize);
    const pages = rootNode.querySelectorAll('.print-page');
    let hasMultiPageMode = false;

    pages.forEach(function(page, idx) {
        const result = fitSinglePageContent(page, paperSize, idx, pages.length);
        if (result?.mode === 'multi') {
            hasMultiPageMode = true;
        }
    });

    return { hasMultiPageMode };
}

function getTiIconName(classList) {
    if (!classList || !classList.length) {
        return '';
    }
    const matched = Array.from(classList).find(function(cls) {
        return cls.startsWith('ti-') && cls !== 'ti';
    });
    return matched ? matched.replace(/^ti-/, '') : '';
}

function fallbackTiSymbol(iconName) {
    const map = {
        phone: '☎',
        mail: '✉',
        'map-pin': '📍',
        calendar: '🗓',
        briefcase: '💼',
        school: '🎓',
        building: '🏢',
        code: '⌨',
        certificate: '🏆',
        tools: '🛠',
        'user-check': '✅',
        plus: '+',
        x: '×',
    };
    return map[String(iconName || '')] || '•';
}

function readTiPseudoGlyph(iconEl, ownerDocument = document) {
    try {
        const view = ownerDocument?.defaultView || window;
        const content = view.getComputedStyle(iconEl, '::before')?.content || '';
        if (!content || content === 'none' || content === 'normal' || content === '""' || content === "''") {
            return '';
        }
        return content.replace(/^['"]|['"]$/g, '');
    } catch (error) {
        return '';
    }
}

function stabilizeTablerIcons(rootNode, ownerDocument = document) {
    if (!rootNode) {
        return;
    }

    const icons = rootNode.querySelectorAll('.ti');
    icons.forEach(function(iconEl) {
        if (iconEl.dataset.iconFixed === '1') {
            return;
        }

        const iconName = getTiIconName(iconEl.classList);
        let glyph = readTiPseudoGlyph(iconEl, ownerDocument);
        if (!glyph) {
            glyph = fallbackTiSymbol(iconName);
        }

        Array.from(iconEl.classList).forEach(function(cls) {
            if (cls.startsWith('ti-') && cls !== 'ti') {
                iconEl.classList.remove(cls);
            }
        });

        iconEl.textContent = glyph;
        iconEl.style.fontFamily = 'inherit';
        iconEl.style.fontStyle = 'normal';
        iconEl.style.fontWeight = 'inherit';
        iconEl.style.display = 'inline-block';
        iconEl.style.lineHeight = '1';
        iconEl.style.verticalAlign = 'middle';
        iconEl.dataset.iconFixed = '1';
    });
}

function initPaperSizeSelector() {
    const select = document.getElementById('paper-size-select');
    if (!select) {
        return;
    }
    const defaultPaper = getSelectedPaperSize();
    applyPaperSizeToCss(defaultPaper);
    select.addEventListener('change', function() {
        const paperSize = getSelectedPaperSize();
        applyPaperSizeToCss(paperSize);
        const exportRoot = document.getElementById('print-export-root');
        applySinglePageFitToRoot(exportRoot, paperSize, document);
    });
}

function buildPdfExportNode(sourceNode) {
    const clonedNode = sourceNode.cloneNode(true);
    clonedNode.querySelectorAll('[data-pdf-export-ignore="true"]').forEach(function(node) {
        node.remove();
    });

    const paperSize = getSelectedPaperSize();
    const sandbox = document.createElement('div');
    sandbox.style.position = 'fixed';
    sandbox.style.left = '-100000px';
    sandbox.style.top = '0';
    sandbox.style.width = paperSize.width + paperSize.unit;
    sandbox.style.pointerEvents = 'none';
    sandbox.style.opacity = '0';
    sandbox.setAttribute('aria-hidden', 'true');
    sandbox.appendChild(clonedNode);
    document.body.appendChild(sandbox);

    return {
        node: clonedNode,
        cleanup: function() {
            sandbox.remove();
        },
    };
}

function waitForImageElement(img) {
    return new Promise(function(resolve) {
        if (!img) {
            resolve();
            return;
        }
        if (img.complete) {
            resolve();
            return;
        }
        const done = function() {
            img.removeEventListener('load', done);
            img.removeEventListener('error', done);
            resolve();
        };
        img.addEventListener('load', done, { once: true });
        img.addEventListener('error', done, { once: true });
    });
}

function waitForAlpineRender() {
    return Promise.resolve();
}

async function waitForRenderAssets() {
    await waitForAlpineRender();

    const exportRoot = document.getElementById('print-export-root');
    const images = exportRoot ? Array.from(exportRoot.querySelectorAll('img')) : [];
    await Promise.all(images.map(waitForImageElement));

    if (document.fonts && document.fonts.ready) {
        try {
            await document.fonts.ready;
        } catch (error) {
            // ignore
        }
    }
}

async function openPrintWindow() {
    await waitForRenderAssets();
    const exportRoot = document.getElementById('print-export-root');
    const paperSize = getSelectedPaperSize();
    stabilizeTablerIcons(exportRoot, document);
    applySinglePageFitToRoot(exportRoot, paperSize, document);
    window.print();
}

async function registerPdfDownloadQuota() {
    const registerUrl = document.body?.dataset?.registerDownloadUrl;
    if (!registerUrl) {
        return { success: true };
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch(registerUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        credentials: 'same-origin',
    });

    let payload = null;
    try {
        payload = await response.json();
    } catch (error) {
        payload = null;
    }

    if (!response.ok || payload?.success !== true) {
        const message = payload?.message || '下载过于频繁，请稍后再试。';
        throw new Error(message);
    }

    return payload;
}

async function downloadPdfDirectly() {
    const exportRoot = document.getElementById('print-export-root');
    if (!exportRoot) {
        setPrintToolbarStatus('导出区域不存在，请刷新后重试。', true);
        return;
    }

    if (!window.html2pdf) {
        setPrintToolbarStatus('当前浏览器未加载 PDF 下载组件，请稍后重试。', true);
        return;
    }

    let exportPayload = null;

    try {
        setPrintToolbarStatus('正在校验下载频率...');
        await registerPdfDownloadQuota();
        setPrintToolbarStatus('正在加载图片与排版...');
        await waitForRenderAssets();
        setPrintToolbarStatus('正在生成 PDF，请稍候...');
        var docTitle = document.querySelector('[data-document-title]')?.dataset.documentTitle || 'resume_export';
        exportPayload = buildPdfExportNode(exportRoot);
        var paperSize = getSelectedPaperSize();

        var jsPdfFormat = 'a4';
        if (paperSize.unit === 'mm') {
            if (Math.abs(paperSize.width - 210) < 1 && Math.abs(paperSize.height - 297) < 1) {
                jsPdfFormat = 'a4';
            } else if (Math.abs(paperSize.width - 297) < 1 && Math.abs(paperSize.height - 420) < 1) {
                jsPdfFormat = 'a3';
            } else if (Math.abs(paperSize.width - 215.9) < 1 && Math.abs(paperSize.height - 279.4) < 1) {
                jsPdfFormat = 'letter';
            } else if (Math.abs(paperSize.width - 215.9) < 1 && Math.abs(paperSize.height - 355.6) < 1) {
                jsPdfFormat = 'legal';
            } else {
                jsPdfFormat = [paperSize.width, paperSize.height];
            }
        } else {
            jsPdfFormat = [paperSize.width, paperSize.height];
        }

        const options = {
            margin: 0,
            filename: sanitizeFilename(docTitle) + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false,
                ignoreElements: function(element) {
                    return element?.dataset?.pdfExportIgnore === 'true';
                },
                onclone: function(clonedDoc) {
                    const clonedRoot = clonedDoc.getElementById('print-export-root') || clonedDoc.body;
                    stabilizeTablerIcons(clonedRoot, clonedDoc);
                    applySinglePageFitToRoot(clonedRoot, paperSize, clonedDoc);

                    // Prevent elements from breaking across pages
                    clonedDoc.querySelectorAll('.resume-template-classic > div, .resume-template-minimal > div, .resume-template-timeline > div, .resume-template-creative > div, .resume-template-elegant > div, .resume-template-modern, .resume-template-modern > div, .print-page ul').forEach(function(el) {
                        el.style.breakInside = 'avoid';
                        el.style.pageBreakInside = 'avoid';
                    });
                },
            },
            jsPDF: {
                unit: paperSize.unit,
                format: jsPdfFormat,
                orientation: 'portrait',
                compress: true,
            },
            pagebreak: {
                mode: ['css', 'legacy'],
                before: [],
                after: [],
                avoid: [],
            },
        };

        await window.html2pdf().set(options).from(exportPayload.node).save();
        setPrintToolbarStatus('PDF 已开始下载。');
    } catch (error) {
        setPrintToolbarStatus('直接下载失败，请改用"打开打印窗口"。', true);
    } finally {
        exportPayload?.cleanup();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initPaperSizeSelector();

    const exportRoot = document.getElementById('print-export-root');
    const paperSize = getSelectedPaperSize();
    applySinglePageFitToRoot(exportRoot, paperSize, document);
});

document.addEventListener('click', function(e) {
    var downloadBtn = e.target.closest('[data-action="download-pdf"]');
    if (downloadBtn) downloadPdfDirectly();

    var printBtn = e.target.closest('[data-print]');
    if (printBtn) openPrintWindow();
});

if (document.querySelector('[data-auto-print]')) {
    window.addEventListener('load', function() {
        window.setTimeout(function() {
            openPrintWindow();
        }, 450);
    });
}