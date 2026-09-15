function smartFillFromText(text) {
    const lines = text.split('\n').map(l => l.trim()).filter(l => l !== '');
    let currentSection = null;
    const sections = {};

    const sectionPatterns = {
        personal:    /^(个人信息|基本资料|个人概况|联系方式|Personal\s*Info|Profile|Contact|About\s*Me)/i,
        objective:   /^(求职意向|职业目标|期望岗位|应聘岗位|Career\s*Objective|Job\s*Objective|Objective|Target\s*Position)/i,
        education:   /^(教育背景|教育经历|学历|学习经历|毕业院校|Education|Academic\s*Background|Qualifications)/i,
        experience:  /^(工作(经验|经历)|职业经历|实习(经验|经历)|职场经历|Work\s*Experience|Professional\s*Experience|Employment|Career\s*History|Internship)/i,
        project:     /^(项目(经验|经历)|Project\s*Experience|Projects|Project\s*History)/i,
        skill:       /^(技能特长|专业技能|核心技能|职业技能|技术栈|技能|证书|Skills|Technical\s*Skills|Core\s*Skills|Professional\s*Skills|Expertise)/i,
        certificate: /^(证书|荣誉|获奖|资格证书|获奖情况|Certifications|Certificates|Awards|Honors|Honours)/i,
        summary:     /^(自我评价|个人评价|自我总结|Summary|Self\s*Assessment|Self\s*Evaluation)/i,
    };

    for (const line of lines) {
        let matched = false;
        for (const [sec, pattern] of Object.entries(sectionPatterns)) {
            if (pattern.test(line)) {
                currentSection = sec;
                if (!sections[currentSection]) sections[currentSection] = [];
                matched = true;
                break;
            }
        }
        if (!matched && currentSection) {
            sections[currentSection].push(line);
        }
    }

    // 如果没有匹配到任何章节，整篇当作 personal
    if (Object.keys(sections).length === 0) {
        sections.personal = lines;
    }

    // 填充基本信息（标题、目标岗位、目标公司）
    fillBasicInfo(lines, sections, sectionPatterns);

    // 填充个人信息
    fillPersonalInfo(sections);

    // 填充各列表模块
    const listMap = [
        { key: 'education', sec: 'education', title: '教育经历' },
        { key: 'experience', sec: 'experience', title: '实习经历' },
        { key: 'project', sec: 'project', title: '项目经验' },
        { key: 'skill', sec: 'skill', title: '技能证书' },
        { key: 'certificate', sec: 'certificate', title: '获奖情况' },
    ];

    for (const map of listMap) {
        const secLines = sections[map.sec] || sections[map.key] || [];
        if (!secLines.length) continue;

        const items = parseSectionLines(secLines, map.key);
        formData[map.key] = items;
        restoreListItems(map.key, items);
    }

    // 填充自我评价
    if (sections.summary) {
        const content = sections.summary.join('\n');
        formData.personal.content = (formData.personal.content ? formData.personal.content + '\n\n' : '') + content;
        const el = document.getElementById('input-personal-content');
        if (el) el.value = formData.personal.content;
    }
}

function fillBasicInfo(allLines, sections, sectionPatterns) {
    // 1. 标题：取前5行中最像标题的短文本（排除已识别的章节名和明显非标题内容）
    const sectionTitles = new Set([
        '个人信息','基本资料','个人概况','联系方式','求职意向','职业目标','期望岗位','应聘岗位',
        '教育背景','教育经历','学历','学习经历','毕业院校','工作','职业经历','实习','职场经历',
        '项目','技能特长','专业技能','核心技能','职业技能','技术栈','技能','证书','荣誉','获奖',
        '资格证书','获奖情况','自我评价','个人评价','自我总结',
        'Personal Info','Profile','Contact','About Me','Career Objective','Job Objective',
        'Objective','Target Position','Education','Academic Background','Qualifications',
        'Work Experience','Professional Experience','Employment','Career History','Internship',
        'Project Experience','Projects','Project History','Skills','Technical Skills',
        'Core Skills','Professional Skills','Expertise','Certifications','Certificates',
        'Awards','Honors','Honours','Summary','Self Assessment','Self Evaluation',
    ]);
    for (let i = 0; i < Math.min(5, allLines.length); i++) {
        const line = allLines[i];
        // 排除：章节标题、过长、含4位数字（年份）、纯英文过长（可能是句子）、含@或http
        if (line.length >= 2 && line.length <= 25 && !/\d{4}/.test(line) && !sectionTitles.has(line)
            && !line.includes('@') && !/https?:\/\//.test(line)
            && !/^[A-Za-z\s]{15,}$/.test(line)) {
            formData.title = line;
            const el = document.getElementById('input-title');
            if (el) el.value = line;
            break;
        }
    }

    // 2. 目标岗位：多策略提取
    const objLines = sections.objective || [];
    const searchLines = objLines.length ? objLines : allLines;

    // 策略A：关键词+冒号格式
    const jobKeywords = /(?:期望|目标|应聘|求职|岗位|职位|Job\s*Title|Position|Role)\s*[:：]\s*(.+)/i;
    for (const line of searchLines) {
        const m = line.match(jobKeywords);
        if (m && m[1]) {
            formData.target_job = m[1].trim();
            const el = document.getElementById('input-target-job');
            if (el) el.value = formData.target_job;
            break;
        }
    }

    // 策略B：objective 章节第一行非标题短文本
    if (!formData.target_job && objLines.length > 0) {
        for (const line of objLines) {
            if (line.length >= 2 && line.length <= 30 && !sectionPatterns.objective.test(line)) {
                formData.target_job = line;
                const el = document.getElementById('input-target-job');
                if (el) el.value = line;
                break;
            }
        }
    }

    // 策略C：从 personal 章节找 "求职意向/应聘岗位" 相关内容
    if (!formData.target_job && sections.personal) {
        for (const line of sections.personal) {
            const m = line.match(jobKeywords);
            if (m && m[1]) {
                formData.target_job = m[1].trim();
                const el = document.getElementById('input-target-job');
                if (el) el.value = formData.target_job;
                break;
            }
        }
    }

    // 3. 目标公司：多策略提取
    const companyKeywords = /(?:期望|目标|应聘|公司|企业|Company|Employer)\s*[:：]\s*(.+)/i;
    for (const line of searchLines) {
        const m = line.match(companyKeywords);
        if (m && m[1]) {
            formData.target_company = m[1].trim();
            const el = document.getElementById('input-target-company');
            if (el) el.value = formData.target_company;
            break;
        }
    }
    if (!formData.target_company && sections.personal) {
        for (const line of sections.personal) {
            const m = line.match(companyKeywords);
            if (m && m[1]) {
                formData.target_company = m[1].trim();
                const el = document.getElementById('input-target-company');
                if (el) el.value = formData.target_company;
                break;
            }
        }
    }
}

function fillPersonalInfo(sections) {
    const pLines = sections.personal || [];
    if (!pLines.length) return;

    // 姓名：前5行中找最像姓名的
    // 排除：纯数字、含@、含http、纯符号、章节标题、过长（>20）
    const sectionLike = /^(个人信息|基本资料|个人概况|联系方式|Profile|Contact|About\s*Me)/i;
    for (let i = 0; i < Math.min(5, pLines.length); i++) {
        const line = pLines[i];
        if (line.length >= 2 && line.length <= 20 && !/\d/.test(line) && !line.includes('@') && !/https?:\/\//.test(line) && !sectionLike.test(line) && /[\u4e00-\u9fa5A-Za-z]/.test(line)) {
            formData.personal.name = line;
            const el = document.getElementById('input-name');
            if (el) el.value = line;
            break;
        }
    }

    for (const line of pLines) {
        // 电话（支持 +86 前缀、空格分隔）
        if (!formData.personal.phone) {
            const m = line.match(/(?:\+?86[-\s]?)?\b(1[3-9]\d{9}|\d{3,4}[-\s]?\d{7,8})\b/);
            if (m) {
                formData.personal.phone = m[1].replace(/\s/g, '');
                const el = document.getElementById('input-phone');
                if (el) el.value = formData.personal.phone;
            }
        }
        // 邮箱（更宽松的正则，支持 + 别名）
        if (!formData.personal.email) {
            const m = line.match(/([\w.+\-]+@[\w.\-]+\.\w{2,})/);
            if (m) {
                formData.personal.email = m[1];
                const el = document.getElementById('input-email');
                if (el) el.value = m[1];
            }
        }
        // 所在地
        if (!formData.personal.location) {
            const locPatterns = [
                /(?:所在|居住|城市|地点|Location|City|Address)\s*[:：]\s*(.+)/i,
                /^(北京|上海|广州|深圳|杭州|成都|武汉|西安|南京|重庆|天津|苏州|长沙|郑州|东莞|青岛|沈阳|宁波|昆明|无锡|佛山|合肥|大连|福州|厦门|哈尔滨|济南|长春|南宁|贵阳|兰州|海口|石家庄|太原|呼和浩特|乌鲁木齐|拉萨|银川|西宁|南昌|常州|南通|徐州|温州|嘉兴|绍兴|金华|台州|扬州|镇江|盐城|泰州|淮安|连云港|宿迁)\b/,
            ];
            for (const pat of locPatterns) {
                const m = line.match(pat);
                if (m) {
                    formData.personal.location = m[1] ? m[1].trim() : m[0].trim();
                    const el = document.getElementById('input-location');
                    if (el) el.value = formData.personal.location;
                    break;
                }
            }
        }
        // 个人网站 / GitHub / LinkedIn
        if (!formData.personal.website) {
            const m = line.match(/(https?:\/\/[\w.\-]+\.[a-z]{2,}(?:\/[\w.\-]*)*)/i);
            if (m) {
                formData.personal.website = m[1];
            }
        }
        if (!formData.personal.github) {
            const m = line.match(/(?:github)\s*[:：]?\s*(https?:\/\/github\.com\/[^\s]+|[A-Za-z0-9_-]+)/i);
            if (m) {
                formData.personal.github = m[1];
            }
        }
        if (!formData.personal.wechat) {
            const m = line.match(/(?:微信|wechat|weixin)\s*[:：]?\s*([A-Za-z0-9_-]{4,})/i);
            if (m) {
                formData.personal.wechat = m[1];
            }
        }
        if (!formData.personal.gender) {
            const m = line.match(/(?:性别|gender)\s*[:：]?\s*(男|女|male|female)/i);
            if (m) {
                formData.personal.gender = m[1];
            }
        }
        if (!formData.personal.birthday) {
            const m = line.match(/(?:生日|出生(?:日期)?|birthday|birth)\s*[:：]?\s*((?:19|20)\d{2}[.\-\/年]\d{1,2}(?:[.\-\/月]\d{1,2})?日?)/i);
            if (m) {
                formData.personal.birthday = m[1];
            }
        }
    }

    const customFields = [...(formData.personal.custom_fields || [])];
    const reservedKeys = new Set(['姓名','电话','手机','邮箱','地址','所在地','现居地','城市','性别','生日','出生','出生日期','微信','wechat','weixin','github','作品集','website','portfolio']);
    for (const line of pLines) {
        const m = line.match(/^([^:：]{1,20})[:：]\s*(.+)$/);
        if (!m) continue;
        const label = m[1].trim();
        const value = m[2].trim();
        if (!label || !value || reservedKeys.has(label) || reservedKeys.has(label.toLowerCase())) continue;
        customFields.push({ label, value });
    }
    formData.personal.custom_fields = normalizePersonalCustomFields(customFields);

    const inputMap = {
        name: 'input-name',
        phone: 'input-phone',
        email: 'input-email',
        location: 'input-location',
        gender: 'input-gender',
        birthday: 'input-birthday',
        wechat: 'input-wechat',
        github: 'input-github',
        website: 'input-website',
    };
    for (const [key, id] of Object.entries(inputMap)) {
        const el = document.getElementById(id);
        if (el) el.value = formData.personal[key] || '';
    }
    renderPersonalCustomFields(formData.personal.custom_fields || []);

    // 个人简介：取前10行中排除已提取字段后的内容
    const used = new Set([
        formData.personal.name,
        formData.personal.phone,
        formData.personal.email,
        formData.personal.location,
        formData.personal.gender,
        formData.personal.birthday,
        formData.personal.wechat,
        formData.personal.github,
        formData.personal.website,
        ...(formData.personal.custom_fields || []).flatMap(field => [field.label, field.value]),
    ].filter(Boolean));
    const contentLines = pLines.filter(l => {
        const normalized = l.replace(/\s/g, '');
        return !used.has(l) && !used.has(normalized) && !used.has(l.replace(/\s+/g, ' ').trim());
    }).slice(0, 10);
    if (contentLines.length) {
        formData.personal.content = contentLines.join('\n');
        const el = document.getElementById('input-personal-content');
        if (el) el.value = formData.personal.content;
    }
}

function parseSectionLines(lines, section) {
    const items = [];
    let currentItem = { subtitle: '', date: '', location: '', content: '' };
    let contentBuffer = [];

    // 日期匹配：支持 2020.09-2024.06、Sep 2020 - Jun 2024、2020.09 - Present 等
    const datePattern = /(\d{4}[\.\-/]\d{1,2}\s*[\-~至]\s*(?:\d{4}[\.\-/]\d{1,2}|至今|现在|Present|Now)|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\.\s]+\d{4}\s*[-~]\s*(?:\d{4}|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\.\s]+\d{4}|Present|Now))/i;
    // 纯日期行
    const standaloneDatePattern = /^\s*(\d{4}[\.\-/]\d{1,2}\s*[\-~至]\s*(?:\d{4}[\.\-/]\d{1,2}|至今|现在|Present|Now))\s*$/i;
    // 学位关键词
    const degreePattern = /(本科|硕士|博士|研究生|专科|大专|Bachelor|Master|Ph\.?D|MBA|MPA|Engineer|Doctor)/i;
    // 学校关键词
    const schoolPattern = /(大学|学院|学校|University|College|Institute|Academy|School)/i;

    function flushItem() {
        if (currentItem.subtitle || contentBuffer.length) {
            currentItem.content = contentBuffer.join('\n');
            items.push({ ...currentItem });
        }
        currentItem = { subtitle: '', date: '', location: '', content: '' };
        contentBuffer = [];
    }

    for (let idx = 0; idx < lines.length; idx++) {
        const line = lines[idx];
        const hasDate = datePattern.test(line);
        const isStandaloneDate = standaloneDatePattern.test(line);
        const hasDegree = degreePattern.test(line);
        const hasSchool = schoolPattern.test(line);

        // 判断是否为条目头部
        const isHeaderLike = line.length <= 80 && (
            hasDate ||
            line.includes('|') ||
            line.includes('/') ||
            line.includes('—') ||
            line.includes('–') ||
            line.includes(' - ') ||
            isStandaloneDate
        );

        // 教育经历：学校名或学位词或日期可触发新条目
        const isEducationHeader = section === 'education' && (hasSchool || hasDegree || isStandaloneDate);

        // 新条目开始条件
        const shouldStartNew = isHeaderLike || isEducationHeader;

        if (shouldStartNew && (currentItem.subtitle || currentItem.date || contentBuffer.length)) {
            flushItem();
        }

        if (shouldStartNew && !currentItem.subtitle && !currentItem.date) {
            if (isStandaloneDate) {
                // 纯日期行：先存日期，subtitle 留给下一行
                currentItem.date = line.trim();
            } else if (section === 'education') {
                // 教育经历标题行解析：尝试提取 学校 | 专业/学位 | 日期
                const parts = line.split(/\s*[|\/—–-]\s*/).map(s => s.trim()).filter(s => s);
                if (parts.length >= 1) {
                    // 判断哪个部分是学校名
                    if (schoolPattern.test(parts[0]) || (parts[0].length >= 4 && !datePattern.test(parts[0]) && !degreePattern.test(parts[0]))) {
                        currentItem.subtitle = parts[0];
                    } else if (degreePattern.test(parts[0])) {
                        currentItem.location = parts[0]; // 学位放 location
                    } else {
                        currentItem.subtitle = parts[0];
                    }
                }
                if (parts.length >= 2) {
                    if (datePattern.test(parts[1])) {
                        currentItem.date = parts[1];
                    } else if (degreePattern.test(parts[1]) || schoolPattern.test(parts[1])) {
                        if (!currentItem.subtitle && schoolPattern.test(parts[1])) currentItem.subtitle = parts[1];
                        else if (!currentItem.location) currentItem.location = parts[1];
                    } else {
                        if (!currentItem.location) currentItem.location = parts[1];
                    }
                }
                if (parts.length >= 3) {
                    if (datePattern.test(parts[2])) currentItem.date = parts[2];
                    else if (!currentItem.location) currentItem.location = parts[2];
                }
                // 如果整行就是日期
                if (!currentItem.subtitle && hasDate) {
                    currentItem.date = line;
                }
            } else {
                // 非教育经历：通用解析
                const parts = line.split(/\s*[|\/—–-]\s*/).map(s => s.trim()).filter(s => s);
                if (parts.length >= 1) currentItem.subtitle = parts[0];
                if (parts.length >= 2) {
                    if (datePattern.test(parts[1])) currentItem.date = parts[1];
                    else currentItem.location = parts[1];
                }
                if (parts.length >= 3) {
                    if (datePattern.test(parts[2])) currentItem.date = parts[2];
                    else if (!currentItem.location) currentItem.location = parts[2];
                }
                if (!currentItem.subtitle && hasDate) {
                    currentItem.date = line;
                }
            }
        } else if (currentItem.date && !currentItem.subtitle && section === 'education' && hasSchool) {
            // 日期行之后紧跟学校名
            currentItem.subtitle = line;
        } else if (currentItem.subtitle && !currentItem.location && section === 'education' && hasDegree) {
            // 学校名之后紧跟学位
            currentItem.location = line;
        } else {
            contentBuffer.push(line);
        }
    }
    flushItem();

    // 后处理：教育经历中补全缺失字段
    if (section === 'education') {
        for (const item of items) {
            // subtitle 为空但有 content，尝试提取学校名
            if (!item.subtitle && item.content) {
                const contentLines = item.content.split('\n');
                for (let i = 0; i < contentLines.length; i++) {
                    const cl = contentLines[i];
                    if (schoolPattern.test(cl)) {
                        item.subtitle = cl.trim();
                        contentLines.splice(i, 1);
                        item.content = contentLines.join('\n');
                        break;
                    }
                }
            }
            // location 为空但有 content，尝试提取学位
            if (!item.location && item.content) {
                const contentLines = item.content.split('\n');
                for (let i = 0; i < contentLines.length; i++) {
                    const cl = contentLines[i];
                    if (degreePattern.test(cl)) {
                        item.location = cl.trim();
                        contentLines.splice(i, 1);
                        item.content = contentLines.join('\n');
                        break;
                    }
                }
            }
            // date 为空但有 content，尝试提取日期
            if (!item.date && item.content) {
                const contentLines = item.content.split('\n');
                for (let i = 0; i < contentLines.length; i++) {
                    const cl = contentLines[i];
                    if (datePattern.test(cl)) {
                        item.date = cl.trim();
                        contentLines.splice(i, 1);
                        item.content = contentLines.join('\n');
                        break;
                    }
                }
            }
        }
    }

    // 如果什么都没解析出来，整段作为 content
    if (items.length === 0 && lines.length > 0) {
        items.push({ subtitle: '', date: '', location: '', content: lines.join('\n') });
    }

    return items;
}

function setupDragDrop() {
    const zone = document.getElementById('import-zone');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        zone.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    zone.addEventListener('dragenter', () => zone.classList.add('dragover'));
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', (e) => {
        zone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('resume-file').files = files;
            importResume(document.getElementById('resume-file'));
        }
    });
}
