(() => {
    var TOTAL_STEPS = 3;
    var currentStep = 1;

    var wizardSteps = document.querySelectorAll('.wizard-step');
    var wizardPanels = [
        document.getElementById('wizardPanel1'),
        document.getElementById('wizardPanel2'),
        document.getElementById('wizardPanel3')
    ];
    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var btnSubmit = document.getElementById('btnSubmit');
    var btnCancel = document.getElementById('btnCancel');

    var goToStep = function(step) {
        if (step < 1 || step > TOTAL_STEPS) return;

        if (step > currentStep) {
            for (var s = currentStep; s < step; s++) {
                if (!validateStep(s)) return;
            }
        }

        currentStep = step;

        wizardPanels.forEach(function(panel, idx) {
            if (panel) {
                panel.classList.toggle('active', (idx + 1) === currentStep);
            }
        });

        wizardSteps.forEach(function(stepEl, idx) {
            var stepNum = idx + 1;
            stepEl.classList.remove('active', 'completed');
            if (stepNum === currentStep) {
                stepEl.classList.add('active');
            } else if (stepNum < currentStep) {
                stepEl.classList.add('completed');
            }
        });

        if (btnPrev) btnPrev.classList.toggle('d-none', currentStep === 1);
        if (btnNext) btnNext.classList.toggle('d-none', currentStep === TOTAL_STEPS);
        if (btnSubmit) btnSubmit.classList.toggle('d-none', currentStep !== TOTAL_STEPS);
        if (btnCancel) btnCancel.classList.toggle('d-none', currentStep === TOTAL_STEPS);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    var validateStep = function(step) {
        var panel = wizardPanels[step - 1];
        if (!panel) return true;

        var requiredFields = panel.querySelectorAll('[required]');
        var valid = true;

        requiredFields.forEach(function(field) {
            field.classList.remove('is-invalid');

            if (field.type === 'radio' || field.type === 'checkbox') {
                var name = field.getAttribute('name');
                if (name && panel.querySelector('input[name="' + name + '"]:checked')) {
                    return;
                }
                field.closest('.form-selectgroup-item')?.classList.add('is-invalid');
                valid = false;
                return;
            }

            if (!field.value || !field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            }
        });

        return valid;
    };

    if (btnNext) {
    if (btnNext) {
        btnNext.addEventListener('click', function() {
            goToStep(currentStep + 1);
        });
    }
    if (btnPrev) {
        btnPrev.addEventListener('click', function() {
            goToStep(currentStep - 1);
        });
    }
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function() {
            goToStep(currentStep - 1);
        });
    }

    if (wizardSteps.length > 0) {
        wizardSteps.forEach(function(stepEl) {
            stepEl.addEventListener('click', function() {
            var step = parseInt(stepEl.dataset.step, 10);
            if (step && step < currentStep) {
                goToStep(step);
            }
            });
        });
    }

    goToStep(1);

    var qSlider = document.getElementById('questionCountRange');
    var qBadge = document.getElementById('questionCountBadge');
    var qTimeHint = document.getElementById('questionTimeHint');
    if (qSlider && qBadge) {
        var updateTimeHint = function() {
            var count = parseInt(qSlider.value, 10) || 5;
            qBadge.textContent = count + ' 题';
            if (qTimeHint) {
                var minutes = Math.max(1, Math.round(count * 2.5));
                qTimeHint.textContent = '预计耗时约 ' + minutes + ' 分钟';
            }
        };
        qSlider.addEventListener('input', updateTimeHint);
        updateTimeHint();
    }

    const form = document.getElementById('interviewCreateForm');
    const resumeSelect = document.getElementById('resume_id');
    const positionInput = document.getElementById('position');
    const targetJobTip = document.getElementById('resume-target-job-tip');
    const anchorsTip = document.getElementById('resume-anchors-tip');
    const profileHint = document.getElementById('candidate-profile-hint');
    const profileRadios = Array.from(document.querySelectorAll('input[name="candidate_profile"]'));

    if (!form || !resumeSelect || !positionInput) {
        return;
    }

    var highlightsCache = {};

    var renderResumeHints = function() {
        var selected = resumeSelect.options[resumeSelect.selectedIndex];
        if (!selected || !selected.value) {
            if (targetJobTip) targetJobTip.textContent = '请先选择简历';
            if (anchorsTip) anchorsTip.textContent = '系统会基于简历亮点生成定制问题。';
            return;
        }

        const resumeId = selected.value;
        const targetJob = (selected.dataset.targetJob || '').trim();
        if (targetJobTip) {
            targetJobTip.textContent = targetJob !== ''
                ? '目标：' + targetJob
                : '简历未填写目标岗位';
        }

        if (positionInput.value.trim() === '' && targetJob !== '') {
            positionInput.value = targetJob;
        }

        if (highlightsCache[resumeId]) {
            applyHighlights(highlightsCache[resumeId]);
            return;
        }

        if (anchorsTip) anchorsTip.textContent = '正在加载简历亮点...';

        fetch('/user/interviews/resume-highlights', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ resume_id: parseInt(resumeId, 10) })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            highlightsCache[resumeId] = data.highlights || [];
            applyHighlights(data.highlights || []);
        })
        .catch(function() {
            if (anchorsTip) anchorsTip.textContent = '加载简历亮点失败，将基于简历正文自动抽取。';
        });
    };

    const applyHighlights = (highlights) => {
        const anchors = Array.isArray(highlights)
            ? highlights.filter(item => typeof item === 'string' && item.trim() !== '').slice(0, 3)
            : [];
        if (anchorsTip) anchorsTip.textContent = anchors.length > 0
            ? `简历锚点：${anchors.join('；')}`
            : '未识别到亮点锚点，将基于简历正文和模块内容自动抽取。';
    };

    resumeSelect.addEventListener('change', renderResumeHints);
    renderResumeHints();

    // 快捷预设
    var presetBtns = document.querySelectorAll('.preset-btn');
    presetBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var data = this.dataset;
            if (data.position && positionInput) positionInput.value = data.position;
            if (data.company) {
                var ci = document.querySelector('input[name="company"]');
                if (ci) ci.value = data.company;
            }
            if (data.type) {
                var typeRadio = document.querySelector('input[name="type"][value="' + data.type + '"]');
                if (typeRadio) typeRadio.checked = true;
            }
            if (data.difficulty) {
                var diffRadio = document.querySelector('input[name="difficulty"][value="' + data.difficulty + '"]');
                if (diffRadio) diffRadio.checked = true;
            }
            if (data.profile) {
                var profileRadio = document.querySelector('input[name="candidate_profile"][value="' + data.profile + '"]');
                if (profileRadio) profileRadio.checked = true;
                renderProfileHint();
            }
            if (data.lang) {
                var langRadio = document.querySelector('input[name="language"][value="' + data.lang + '"]');
                if (langRadio) langRadio.checked = true;
            }
        });
    });

    var previewCard = document.getElementById('resumePreviewCard');
    if (previewCard && resumeSelect) {
        var updatePreview = function() {
            var opt = resumeSelect.options[resumeSelect.selectedIndex];
            if (!opt || !opt.value) {
                previewCard.classList.add('d-none');
                return;
            }
            previewCard.classList.remove('d-none');
            document.getElementById('resumePreviewTitle').textContent = opt.textContent.trim();
            document.getElementById('resumePreviewJob').textContent = (opt.dataset.targetJob || '').trim() || '未填写';
            document.getElementById('resumePreviewModules').textContent = opt.dataset.modules || '0';
            document.getElementById('resumePreviewTime').textContent = opt.dataset.updatedAt || '—';
            var ready = document.getElementById('resumePreviewReady');
            ready.classList.toggle('d-none', parseInt(opt.dataset.modules || '0') < 2);
            var atsScore = opt.dataset.atsScore ? parseInt(opt.dataset.atsScore) : null;
            var atsWrap = document.getElementById('resumePreviewAtsWrap');
            var atsEl = document.getElementById('resumePreviewAts');
            var atsWarn = document.getElementById('resumePreviewAtsWarn');
            if (atsScore && atsWrap && atsEl) {
                atsWrap.classList.remove('d-none');
                atsEl.textContent = atsScore + '分';
            } else if (atsWrap) {
                atsWrap.classList.add('d-none');
            }
            if (atsWarn) {
                atsWarn.classList.toggle('d-none', !atsScore || atsScore >= 70);
            }
        };
        resumeSelect.addEventListener('change', updatePreview);
        updatePreview();
    }

    const profileHintMap = {
        fresh_graduate: '当前模式会优先生成基础友好题目：重视学习能力、课程/项目表达和成长潜力。',
        no_experience: '当前模式会优先生成转岗友好题目：重视迁移能力、学习计划和可验证的小成果。',
        junior: '当前模式会平衡基础与进阶题：重视项目落地、问题拆解与协作推进。',
        experienced: '当前模式会提高追问深度：重视复杂场景、技术权衡和业务结果。',
    };
    const renderProfileHint = () => {
        if (!profileHint || profileRadios.length === 0) {
            return;
        }
        const checked = profileRadios.find((item) => item.checked);
        profileHint.textContent = profileHintMap[checked?.value || 'fresh_graduate'] || profileHintMap.fresh_graduate;
    };
    profileRadios.forEach((item) => item.addEventListener('change', renderProfileHint));
    renderProfileHint();

    var profileRecommendations = window.__interviewProfileRecommendations || {};
    var recommendBadges = document.querySelectorAll('.type-recommend-badge');

    var updateTypeRecommendations = function() {
        var checked = profileRadios.find(function(r) { return r.checked; });
        var profile = checked ? checked.value : '';
        var recommended = profileRecommendations[profile] || [];

        recommendBadges.forEach(function(badge) {
            var type = badge.dataset.type;
            if (recommended.indexOf(type) >= 0) {
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        });
    };

    profileRadios.forEach(function(r) { r.addEventListener('change', updateTypeRecommendations); });
    updateTypeRecommendations();

    var submitBtn = btnSubmit;
    var submitting = false;

    const setSubmittingState = (active) => {
        submitting = active;
        if (submitBtn) {
            submitBtn.disabled = active;
            submitBtn.innerHTML = active
                ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>创建中...'
                : '<i class="ti ti-player-play me-2"></i>开始面试';
        }
        var overlay = document.getElementById('interviewLoadingOverlay');
        if (overlay) {
            overlay.style.display = active ? 'flex' : 'none';
        }
    };

    const showMessage = (message, level = 'warning') => {
        if (!message) {
            return;
        }
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, level);
            return;
        }
        window.alert(message);
    };

    const showFieldErrors = (validationErrors) => {
        if (!validationErrors || typeof validationErrors !== 'object') {
            return false;
        }

        const firstField = Object.keys(validationErrors)[0];
        const firstMessages = firstField ? validationErrors[firstField] : null;
        const firstMessage = Array.isArray(firstMessages) ? firstMessages[0] : '';

        [resumeSelect, positionInput].forEach((el) => el?.classList.remove('is-invalid'));
        if (firstField === 'resume_id') {
            resumeSelect.classList.add('is-invalid');
        }
        if (firstField === 'position') {
            positionInput.classList.add('is-invalid');
        }
        if (firstMessage) {
            showMessage(firstMessage, 'warning');
            return true;
        }
        return false;
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        for (var s = 1; s <= TOTAL_STEPS; s++) {
            if (!validateStep(s)) {
                goToStep(s);
                return;
            }
        }

        setSubmittingState(true);

        document.addEventListener('quota:modal-open', function onQuotaModal() {
            setSubmittingState(false);
            document.removeEventListener('quota:modal-open', onQuotaModal);
        }, { once: true });

        document.addEventListener('quota:credit-selected', function onCreditSelected() {
            setSubmittingState(true);
            document.removeEventListener('quota:credit-selected', onCreditSelected);
        }, { once: true });

        const submitPromise = window.quotaInterceptor && typeof window.quotaInterceptor.submitForm === 'function'
            ? window.quotaInterceptor.submitForm(form)
            : Promise.reject(new Error('缺少配额拦截器'));

        submitPromise.then((result) => {
            if (result && result.redirected) {
                return;
            }

            if (result && result.ok) {
                if (result.url) {
                    window.location.href = result.url;
                }
                return;
            }

            const handledValidation = showFieldErrors(result?.validationErrors || null);
            if (!handledValidation) {
                showMessage(result?.message || '创建面试失败，请稍后重试', 'error');
            }
            setSubmittingState(false);
        }).catch((error) => {
            if (error && error.quotaHandled) {
                setSubmittingState(false);
                return;
            }

            showMessage(error?.message || '创建面试失败，请稍后重试', 'error');
            setSubmittingState(false);
        });
    });

    var jdTextarea = document.querySelector('textarea[name="job_description"]');
    var companyInput = document.querySelector('input[name="company"]');
    var jdImportToggle = document.getElementById('jdImportToggle');
    var jdImportMenu = document.getElementById('jdImportMenu');
    var jdSourcesLoaded = false;

    if (jdImportToggle && jdImportMenu) {
        jdImportToggle.addEventListener('show.bs.dropdown', function() {
            if (jdSourcesLoaded) return;
            fetch('/user/interviews/jd-sources', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var html = '';
                var bookmarks = data.bookmarks || [];
                var histories = data.histories || [];
                if (bookmarks.length === 0 && histories.length === 0) {
                    html = '<span class="dropdown-item small text-secondary py-2">暂无 JD 数据</span>';
                } else {
                    if (bookmarks.length > 0) {
                        html += '<span class="dropdown-header">从岗位收藏</span>';
                        bookmarks.forEach(function(bm) {
                            html += '<button type="button" class="dropdown-item small py-2 jd-import-btn" data-source="bookmark" data-id="' + bm.id + '">'
                                + '<div class="fw-medium">' + bm.title + '</div>'
                                + (bm.company ? '<div class="text-secondary" style="font-size:0.75rem;">' + bm.company + '</div>' : '')
                                + '<div class="text-secondary text-truncate" style="max-width:320px;font-size:0.7rem;">' + bm.summary + '</div>'
                                + '</button>';
                        });
                    }
                    if (histories.length > 0) {
                        html += '<span class="dropdown-header">从历史分析</span>';
                        histories.forEach(function(h) {
                            html += '<button type="button" class="dropdown-item small py-2 jd-import-btn" data-source="history" data-id="' + h.id + '">'
                                + '<div class="d-flex justify-content-between align-items-center">'
                                + '<span>' + h.title + '</span>'
                                + (h.match_score ? '<span class="badge bg-green-lt text-green ms-1">' + h.match_score + '分</span>' : '')
                                + '</div>'
                                + '<div class="text-secondary text-truncate" style="max-width:320px;font-size:0.7rem;">' + h.summary + '</div>'
                                + '</button>';
                        });
                    }
                }
                jdImportMenu.innerHTML = html;
                jdSourcesLoaded = true;
            })
            .catch(function() {
                jdImportMenu.innerHTML = '<span class="dropdown-item small text-danger py-2">加载失败，请重试</span>';
            });
        });
    }

    if (jdTextarea) {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.jd-import-btn');
            if (!btn) return;
            var source = btn.dataset.source;
            var id = btn.dataset.id;
            if (!source || !id) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>导入中...';

            fetch('/user/interviews/jd-content', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ source: source, id: parseInt(id, 10) })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.job_description) {
                    jdTextarea.value = data.job_description;
                    jdTextarea.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (data.company && companyInput && !companyInput.value.trim()) {
                    companyInput.value = data.company;
                }
                var dd = btn.closest('.dropdown-menu');
                if (dd) dd.classList.remove('show');
            })
            .catch(function() {
                if (typeof window.appNotify === 'function') {
                    window.appNotify('导入失败，请重试', 'error');
                }
            })
            .finally(function() {
                btn.disabled = false;
            });
        });
    }

    var kwInput = document.getElementById('techKeywordsInput');
    var kwSelected = document.getElementById('techTagsSelected');
    var kwPlaceholder = document.getElementById('kwPlaceholder');
    var kwCount = document.getElementById('kwCount');
    var kwBody = document.getElementById('techTagsBody');
    var kwTypeTabs = document.querySelectorAll('.kw-type-tab');
    var kwCache = {};
    var currentKwType = 'technical';
    var selectedKws = kwInput ? (kwInput.value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean) : [];

    var loadKeywords = function(type) {
        if (kwCache[type]) {
            renderKwButtons(kwCache[type]);
            return;
        }
        kwBody.innerHTML = '<span class="text-secondary small"><span class="spinner-border spinner-border-sm me-1"></span>加载中...</span>';
        fetch('/user/interviews/keywords/' + encodeURIComponent(type), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            kwCache[type] = data;
            renderKwButtons(data);
        })
        .catch(function() {
            kwBody.innerHTML = '<span class="text-danger small">加载失败，请重试</span>';
        });
    };

    var renderKwButtons = function(keywords) {
        if (!kwBody) return;
        var searchVal = (document.getElementById('kwSearchInput')?.value || '').trim().toLowerCase();
        var filtered = keywords;
        if (searchVal) {
            filtered = keywords.filter(function(kw) {
                return kw.toLowerCase().indexOf(searchVal) >= 0;
            });
        }
        if (filtered.length === 0) {
            kwBody.innerHTML = '<span class="text-secondary small">' + (searchVal ? '未找到匹配的关键词' : '暂无关键词') + '</span>';
            return;
        }
        kwBody.innerHTML = filtered.map(function(kw) {
            var active = selectedKws.indexOf(kw) >= 0;
            return '<button type="button" class="btn btn-xs py-0 px-2 ' + (active ? 'btn-primary' : 'btn-outline-secondary') + ' tech-kw-btn" data-kw="' + kw + '" style="font-size:0.72rem;">' + kw + '</button>';
        }).join('');
    };

    var kwSearchInput = document.getElementById('kwSearchInput');
    if (kwSearchInput) {
        kwSearchInput.addEventListener('input', function() {
            var currentKeywords = kwCache[currentKwType] || [];
            if (currentKeywords.length > 0) {
                renderKwButtons(currentKeywords);
            }
        });
    }

    if (kwTypeTabs.length > 0) {
        kwTypeTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                kwTypeTabs.forEach(function(t) {
                    t.classList.remove('btn-primary');
                    t.classList.add('btn-outline-secondary');
                });
                tab.classList.remove('btn-outline-secondary');
                tab.classList.add('btn-primary');
                currentKwType = tab.dataset.type;
                loadKeywords(currentKwType);
            });
        });
    }

    if (kwInput && kwSelected) {
        var renderKw = function() {
            var badges = selectedKws.map(function(kw) {
                return '<span class="badge bg-primary-lt text-primary me-1 mb-1 cursor-pointer kw-badge" data-kw="' + kw + '" style="font-size:0.72rem;">'
                    + kw + ' <i class="ti ti-x" style="font-size:0.6rem;"></i></span>';
            }).join('');
            kwSelected.innerHTML = badges || '<span class="text-secondary small" id="kwPlaceholder">点击右侧标签添加</span>';
            kwInput.value = selectedKws.join(',');
            kwCount.textContent = selectedKws.length;

            document.querySelectorAll('.tech-kw-btn').forEach(function(b) {
                var active = selectedKws.indexOf(b.dataset.kw) >= 0;
                b.classList.toggle('btn-primary', active);
                b.classList.toggle('btn-outline-secondary', !active);
            });
        };

        kwSelected.addEventListener('click', function(e) {
            var badge = e.target.closest('.kw-badge');
            if (!badge) return;
            var kw = badge.dataset.kw;
            var idx = selectedKws.indexOf(kw);
            if (idx >= 0) selectedKws.splice(idx, 1);
            renderKw();
        });

        kwBody.addEventListener('click', function(e) {
            var btn = e.target.closest('.tech-kw-btn');
            if (!btn) return;
            var kw = btn.dataset.kw;
            var idx = selectedKws.indexOf(kw);
            if (idx >= 0) {
                selectedKws.splice(idx, 1);
            } else if (selectedKws.length < 10) {
                selectedKws.push(kw);
            }
            renderKw();
        });

        if (selectedKws.length > 0) renderKw();
    }
})();
