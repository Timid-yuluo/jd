        moduleHintTitle(mod) {
            const titleMap = this.config.moduleHintTitles || {
                personal: '优先补齐基础身份信息',
                objective: '让岗位方向更明确',
                education: '突出学校、时间和核心亮点',
                experience: '强化职责与成果表达',
                project: '讲清项目背景与技术价值',
                skill: '用关键词覆盖核心能力',
                certificate: '补充证书与荣誉背书',
                summary: '用几句话概括你的竞争力',
            };

            return titleMap[mod?.type] || '继续完善当前模块';
        },

        moduleMissingFields(mod) {
            const itemsCount = this.filledItemsCount(mod?.data?.items);
            const contentLength = this.textLength(mod?.data?.content);

            switch (mod?.type) {
                case 'personal':
                    return [
                        this.hasMeaningfulText(mod, 'name') ? null : '姓名',
                        this.hasMeaningfulText(mod, 'phone') ? null : '电话',
                        this.hasMeaningfulText(mod, 'email') ? null : '邮箱',
                        this.hasMeaningfulText(mod, 'location') ? null : '所在地',
                    ].filter(Boolean);
                case 'objective':
                    return [
                        this.hasMeaningfulText(mod, 'target_job') ? null : '目标岗位',
                        this.hasMeaningfulText(mod, 'content') ? null : '求职意向说明',
                    ].filter(Boolean);
                case 'education':
                    return [
                        this.hasMeaningfulText(mod, 'subtitle') ? null : '学校 / 专业',
                        this.hasMeaningfulText(mod, 'date') ? null : '时间范围',
                        this.hasMeaningfulText(mod, 'location') ? null : '城市',
                        (contentLength >= 12 || itemsCount >= 1) ? null : '课程 / 奖项 / 研究方向',
                    ].filter(Boolean);
                case 'experience':
                    return [
                        this.hasMeaningfulText(mod, 'subtitle') ? null : '公司 / 职位',
                        this.hasMeaningfulText(mod, 'date') ? null : '时间范围',
                        this.hasMeaningfulText(mod, 'location') ? null : '工作地点',
                        itemsCount >= 2 ? null : '至少 2 条职责或成果',
                    ].filter(Boolean);
                case 'project':
                    return [
                        this.hasMeaningfulText(mod, 'subtitle') ? null : '项目名称 / 角色',
                        this.hasMeaningfulText(mod, 'date') ? null : '项目周期',
                        this.hasMeaningfulText(mod, 'location') ? null : '项目场景',
                        itemsCount >= 2 ? null : '至少 2 条项目亮点',
                    ].filter(Boolean);
                case 'skill':
                    return [
                        itemsCount >= 1 ? null : '补 1 个核心技能',
                        itemsCount >= 3 ? null : '补到 3 个以上技能关键词',
                    ].filter(Boolean);
                case 'certificate':
                    return [
                        itemsCount >= 1 ? null : '至少 1 条证书或荣誉',
                    ].filter(Boolean);
                case 'summary':
                    return [
                        contentLength >= 30 ? null : '补充更完整的自我评价',
                    ].filter(Boolean);
                default:
                    return [];
            }
        },

        moduleMissingFieldKeys(mod) {
            const itemsCount = this.filledItemsCount(mod?.data?.items);
            const contentLength = this.textLength(mod?.data?.content);

            switch (mod?.type) {
                case 'personal':
                    return ['name', 'phone', 'email', 'location']
                        .filter((field) => !this.hasMeaningfulText(mod, field));
                case 'objective':
                    return ['target_job', 'content']
                        .filter((field) => !this.hasMeaningfulText(mod, field));
                case 'education':
                    return [
                        !this.hasMeaningfulText(mod, 'subtitle') ? 'subtitle' : null,
                        !this.hasMeaningfulText(mod, 'date') ? 'date' : null,
                        !this.hasMeaningfulText(mod, 'location') ? 'location' : null,
                        (contentLength < 12 && itemsCount < 1) ? 'content' : null,
                        itemsCount < 1 ? 'items' : null,
                    ].filter(Boolean);
                case 'experience':
                case 'project':
                    return [
                        !this.hasMeaningfulText(mod, 'subtitle') ? 'subtitle' : null,
                        !this.hasMeaningfulText(mod, 'date') ? 'date' : null,
                        !this.hasMeaningfulText(mod, 'location') ? 'location' : null,
                        (contentLength < 12 && itemsCount < 1) ? 'content' : null,
                        itemsCount < 2 ? 'items' : null,
                    ].filter(Boolean);
                case 'skill':
                case 'certificate':
                    return itemsCount < 1 ? ['items'] : [];
                case 'summary':
                    return contentLength < 30 ? ['content'] : [];
                default:
                    return [];
            }
        },

        moduleHasMissingFields(mod) {
            return this.moduleMissingFieldKeys(mod).length > 0;
        },

        moduleHintText(mod) {
            const missingFields = this.moduleMissingFields(mod);
            if (missingFields.length === 0) {
                return '这一块已经比较完整，可以继续微调措辞，或结合目标岗位做 AI 优化。';
            }

            return `建议优先补充：${missingFields.slice(0, 3).join('、')}${missingFields.length > 3 ? ' 等' : ''}。`;
        },

        @include('user.resumes.editor-partials.editor-script-domains.module-field-labels')

        shouldSuggestExample(mod) {
            if (!mod) {
                return false;
            }

            return this.moduleCompletionPercent(mod) < 75;
        },

        @include('user.resumes.editor-partials.editor-script-domains.module-examples')
