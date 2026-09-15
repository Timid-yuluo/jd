/**
 * 职业测评答题页交互
 *
 * 依赖：window.assessmentConfig { type, fetchUrl, submitUrl, csrfToken, totalQuestions }
 */
(function () {
    'use strict';

    var config = window.assessmentConfig;
    if (!config) return;

    var answers = {};
    var currentPage = 1;
    var totalPages = 1;
    var currentQuestions = [];

    var container = document.getElementById('questionsContainer');
    var progressBar = document.getElementById('progressBar');
    var currentPageEl = document.getElementById('currentPage');
    var totalPagesEl = document.getElementById('totalPages');
    var answeredCountEl = document.getElementById('answeredCount');
    var prevBtn = document.getElementById('prevBtn');
    var nextBtn = document.getElementById('nextBtn');
    var submitBtn = document.getElementById('submitBtn');

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function loadPage(page) {
        var url = config.fetchUrl + '?page=' + encodeURIComponent(page);
        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                currentQuestions = data.data || [];
                currentPage = data.current_page;
                totalPages = data.last_page;
                renderQuestions();
                updateUI();
            })
            .catch(function () {
                container.innerHTML = '<div class="alert alert-danger">题目加载失败，请刷新页面重试</div>';
            });
    }

    function renderQuestions() {
        var html = '';
        currentQuestions.forEach(function (q) {
            var qid = q.id;
            var answered = answers[qid];
            var optionsHtml = '';
            var options = q.options || [];
            options.forEach(function (opt, optIdx) {
                var value = opt.value !== undefined ? opt.value : (optIdx + 1);
                var isSelected = String(answered) === String(value);
                var labelText = escapeHtml(opt.text || opt);
                optionsHtml += '<label class="form-check option-item ' + (isSelected ? 'selected' : '') + '" data-qid="' + qid + '" data-value="' + value + '">' +
                    '<input type="radio" name="q_' + qid + '" value="' + value + '" ' + (isSelected ? 'checked' : '') + ' class="form-check-input">' +
                    '<span class="form-check-label">' + labelText + '</span>' +
                    '</label>';
            });
            html += '<div class="question-item mb-4">' +
                '<h5 class="mb-3"><span class="badge bg-primary-lt me-2">Q' + qid + '</span>' + escapeHtml(q.text) + '</h5>' +
                '<div class="options-list">' + optionsHtml + '</div>' +
                '</div>';
        });
        container.innerHTML = html;

        var optionEls = container.querySelectorAll('.option-item');
        optionEls.forEach(function (el) {
            el.addEventListener('click', function () {
                var qid = this.dataset.qid;
                var value = this.dataset.value;
                answers[qid] = value;
                var siblings = document.querySelectorAll('.option-item[data-qid="' + qid + '"]');
                siblings.forEach(function (s) { s.classList.remove('selected'); });
                this.classList.add('selected');
                var radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                updateAnsweredCount();
            });
        });
    }

    function updateUI() {
        currentPageEl.textContent = currentPage;
        totalPagesEl.textContent = totalPages;
        var progress = (currentPage / totalPages) * 100;
        progressBar.style.width = progress + '%';
        prevBtn.disabled = currentPage <= 1;
        var isLast = currentPage >= totalPages;
        nextBtn.style.display = isLast ? 'none' : '';
        submitBtn.style.display = isLast ? '' : 'none';
        updateAnsweredCount();
    }

    function updateAnsweredCount() {
        answeredCountEl.textContent = Object.keys(answers).length;
    }

    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) loadPage(currentPage - 1);
    });

    nextBtn.addEventListener('click', function () {
        if (currentPage < totalPages) loadPage(currentPage + 1);
    });

    submitBtn.addEventListener('click', function () {
        var answered = Object.keys(answers).length;
        if (answered < config.totalQuestions) {
            if (!confirm('还有 ' + (config.totalQuestions - answered) + ' 题未作答，确定要提交吗？')) return;
        }
        var answersArr = Object.keys(answers).map(function (qid) {
            return { question_id: parseInt(qid, 10), option: answers[qid] };
        });
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = config.submitUrl;
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = config.csrfToken;
        form.appendChild(csrf);
        var answersInput = document.createElement('input');
        answersInput.type = 'hidden';
        answersInput.name = 'answers';
        answersInput.value = JSON.stringify(answersArr);
        form.appendChild(answersInput);
        document.body.appendChild(form);
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>提交中...';
        form.submit();
    });

    loadPage(1);
})();
