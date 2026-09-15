/**
 * 技能自评页交互
 *
 * 依赖：window.skillPageConfig { storeUrl, updateUrlTemplate }
 */
(function () {
    'use strict';

    var config = window.skillPageConfig;
    if (!config) return;

    var form = document.getElementById('skillForm');
    if (!form) return;

    var skillIdEl = document.getElementById('skillId');
    var formMethodEl = document.getElementById('formMethod');
    var skillNameEl = document.getElementById('skillName');
    var skillCategoryEl = document.getElementById('skillCategory');
    var skillProficiencyEl = document.getElementById('skillProficiency');
    var skillYearsEl = document.getElementById('skillYears');
    var skillLastUsedEl = document.getElementById('skillLastUsed');
    var skillEvidenceEl = document.getElementById('skillEvidence');
    var submitBtn = document.getElementById('submitBtn');
    var resetBtn = document.getElementById('resetBtn');

    window.editSkill = function (id, name, category, proficiency, years, lastUsed, evidence) {
        skillIdEl.value = id;
        skillNameEl.value = name;
        skillCategoryEl.value = category;
        skillProficiencyEl.value = proficiency;
        skillYearsEl.value = years || '';
        skillLastUsedEl.value = lastUsed || '';
        skillEvidenceEl.value = evidence || '';
        formMethodEl.value = 'PUT';
        form.action = config.updateUrlTemplate.replace(':id', id);
        submitBtn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>保存';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    resetBtn.addEventListener('click', function () {
        form.reset();
        skillIdEl.value = '';
        formMethodEl.value = 'POST';
        form.action = config.storeUrl;
        submitBtn.innerHTML = '<i class="ti ti-plus me-1"></i>添加';
    });
})();
