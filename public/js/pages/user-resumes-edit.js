let pdfJsLoader;

function getPdfJs() {
    if (window.pdfjsLib) {
        return Promise.resolve(window.pdfjsLib);
    }

    if (!pdfJsLoader) {
        pdfJsLoader = import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.5.136/build/pdf.min.mjs')
            .then((module) => {
                const pdfjsLib = module.default ?? module;
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.5.136/build/pdf.worker.min.mjs';
                window.pdfjsLib = pdfjsLib;
                return pdfjsLib;
            });
    }

    return pdfJsLoader;
}

function updateResumeMetrics() {
    const titleInput = document.querySelector('input[name="title"]');
    const targetJobInput = document.querySelector('input[name="target_job"]');
    const contentInput = document.getElementById('content_raw');
    const content = contentInput.value.trim();
    const lineCount = content === '' ? 0 : content.split(/\n/).length;

    document.getElementById('content-count').textContent = content.length;
    document.getElementById('line-count').textContent = lineCount;

    document.getElementById('title-status').textContent = titleInput.value.trim() === '' ? '待检查标题' : '标题已填写';
    document.getElementById('title-status').className = `badge ${titleInput.value.trim() === '' ? 'bg-secondary-lt' : 'bg-success-lt'}`;

    document.getElementById('target-status').textContent = targetJobInput.value.trim() === '' ? '待检查目标岗位' : '目标岗位已填写';
    document.getElementById('target-status').className = `badge ${targetJobInput.value.trim() === '' ? 'bg-secondary-lt' : 'bg-success-lt'}`;

    let contentStatus = '正文较少';
    let contentClass = 'bg-secondary-lt';

    if (content.length >= 600) {
        contentStatus = '正文较完整';
        contentClass = 'bg-success-lt';
    } else if (content.length >= 300) {
        contentStatus = '正文可优化';
        contentClass = 'bg-warning-lt';
    }

    document.getElementById('content-status').textContent = contentStatus;
    document.getElementById('content-status').className = `badge ${contentClass}`;
}

async function importResume(input) {
    const file = input.files[0];
    if (!file) return;

    const importBtn = document.getElementById('resume-import-btn');
    const importStatus = document.getElementById('import-status');

    try {
        importBtn.disabled = true;
        importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>解析中...';
        importStatus.style.display = 'block';
        importStatus.textContent = '正在解析文件，请稍候...';

        const fileName = (file.name || '').toLowerCase();
        if (fileName.endsWith('.pdf') || file.type === 'application/pdf') {
            const pdfjsLib = await getPdfJs();
            const fileBuffer = await file.arrayBuffer();
            const loadingTask = pdfjsLib.getDocument({ data: fileBuffer });
            const pdf = await loadingTask.promise;
            const pageTexts = [];

            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                const page = await pdf.getPage(pageNumber);
                const textContent = await page.getTextContent();
                const pageText = textContent.items
                    .map((item) => item.str || '')
                    .join(' ')
                    .replace(/\s{2,}/g, ' ')
                    .trim();
                if (pageText !== '') {
                    pageTexts.push(pageText);
                }
            }

            const extractedText = pageTexts.join('\n\n').trim();
            if (extractedText === '') {
                if (typeof window.appNotify === 'function') window.appNotify('PDF 解析完成，但未提取到文本内容。请确认该 PDF 不是扫描图片版。', 'warning');
                importStatus.textContent = '未提取到文本内容，可尝试复制粘贴或使用可选中文本 PDF。';
                return;
            }

            document.getElementById('content_raw').value = extractedText;
        } else {
            const text = await file.text();
            const extractedText = text.trim();
            if (extractedText === '') {
                if (typeof window.appNotify === 'function') window.appNotify('导入文件为空，请检查后重试。', 'warning');
                importStatus.textContent = '导入失败：文件内容为空。';
                return;
            }

            document.getElementById('content_raw').value = text;
        }

        updateResumeMetrics();
        importStatus.textContent = '导入成功，已自动填充到简历正文。';
    } catch (error) {
        if (typeof window.appNotify === 'function') window.appNotify('导入失败，请检查文件是否有效。', 'error');
        importStatus.textContent = '导入失败，请检查文件格式或网络后重试。';
    } finally {
        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="ti ti-upload me-1"></i>重新导入 (.txt, .md, .pdf)';
        input.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('input[name="title"]').addEventListener('input', updateResumeMetrics);
    document.querySelector('input[name="target_job"]').addEventListener('input', updateResumeMetrics);
    document.getElementById('content_raw').addEventListener('input', updateResumeMetrics);
    updateResumeMetrics();

    // 事件委托
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        switch (btn.dataset.action) {
            case 'import-resume':
                document.getElementById('resume-file').click();
                break;
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('[data-action="import-file"]')) {
            importResume(e.target);
        }
    });
});
