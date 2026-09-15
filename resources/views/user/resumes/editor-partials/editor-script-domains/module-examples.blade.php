        exampleModuleData(type) {
            const examples = this.config.moduleExamples || {
                personal: {
                    name: '张三',
                    phone: '13800000000',
                    email: 'zhangsan@example.com',
                    location: '深圳',
                },
                objective: {
                    target_job: 'Laravel 后端开发工程师',
                    content: '聚焦企业级后台系统、招聘平台或中后台建设，优先深圳 / 广州，期望在高并发与工程化方向持续深耕。',
                },
                education: {
                    title: '教育经历',
                    subtitle: '华南理工大学 / 软件工程',
                    date: '2018.09 - 2022.06',
                    location: '广州',
                    content: '系统学习软件工程、数据库系统、分布式架构等核心课程。',
                    items: ['连续两年获得校级奖学金', '毕业设计聚焦招聘系统推荐算法与数据分析'],
                },
                experience: {
                    title: '实习经历',
                    subtitle: '某科技有限公司 / PHP 开发工程师',
                    date: '2023.03 - 至今',
                    location: '深圳',
                    content: '负责招聘业务后台、简历模块和 AI 能力接入，参与核心链路优化与迭代。',
                    items: ['主导简历管理模块重构，接口平均响应时间下降 35%', '落地 Redis 缓存与队列异步处理，提升高峰期系统稳定性'],
                },
                project: {
                    title: '项目经验',
                    subtitle: '智能招聘平台 / 后端负责人',
                    date: '2024.01 - 2024.08',
                    location: 'ToB SaaS',
                    content: '负责项目后端架构设计、权限模型和简历智能处理能力建设。',
                    items: ['设计多租户权限模型，支持 100+ 企业客户隔离接入', '完成 AI 简历优化链路接入，显著提升用户编辑效率与转化'],
                },
                skill: {
                    title: '技能证书',
                    items: ['熟悉 Laravel、PHP 8、RESTful API 设计与服务拆分', '掌握 MySQL、Redis、消息队列与常见性能优化手段', '具备 Docker、Linux、Nginx 部署与排障经验'],
                },
                certificate: {
                    title: '获奖情况',
                    items: ['阿里云 ACP 认证', '全国大学生软件设计竞赛省级二等奖'],
                },
                summary: {
                    title: '自我评价',
                    content: '5 年后端开发经验，长期参与企业级后台系统建设，熟悉 Laravel 技术栈、缓存队列和工程化协作流程，能够独立负责模块设计、性能优化与线上问题排查。',
                },
            };

            return JSON.parse(JSON.stringify(examples[type] || {}));
        },

        applyJobTailoredExample(example, type) {
            const targetJob = String(this.streamTargetJob || this.streamTargetJobTitle || '').trim();
            const topKeywords = this.focusKeywordsForOptimize().slice(0, 3);
            if (targetJob === '' && topKeywords.length === 0) {
                return example;
            }

            const cloned = JSON.parse(JSON.stringify(example || {}));
            const keywordText = topKeywords.join('、');
            const roleText = targetJob || '目标岗位';

            if (type === 'objective') {
                cloned.target_job = targetJob || cloned.target_job || '';
                const base = String(cloned.content || '').trim();
                const jobLine = targetJob !== '' ? `目标岗位聚焦${targetJob}。` : '';
                const keywordLine = keywordText !== '' ? `重点强化 ${keywordText} 等能力与项目结果表达。` : '';
                cloned.content = [base, jobLine, keywordLine].filter(Boolean).join(' ');
            }

            if (['experience', 'project', 'summary'].includes(type)) {
                if (typeof cloned.subtitle === 'string' && targetJob !== '') {
                    cloned.subtitle = cloned.subtitle.replace(/PHP 开发工程师|后端负责人/g, targetJob);
                }
                if (typeof cloned.content === 'string') {
                    const ext = [];
                    if (targetJob !== '') ext.push(`围绕${targetJob}职责持续交付。`);
                    if (keywordText !== '') ext.push(`重点应用 ${keywordText}。`);
                    cloned.content = [cloned.content.trim(), ...ext].filter(Boolean).join(' ');
                }
                if (Array.isArray(cloned.items) && keywordText !== '') {
                    cloned.items = [...cloned.items, `结合${keywordText}完成关键需求，推动指标提升。`];
                }
            }

            if (type === 'skill' && Array.isArray(cloned.items) && keywordText !== '') {
                const extra = topKeywords.map((keyword) => `掌握 ${keyword} 并可在实战中独立落地`);
                cloned.items = Array.from(new Set([...cloned.items, ...extra]));
            }

            return cloned;
        },

        applyModuleExample(index) {
            const mod = this.modules[index];
            if (!mod) {
                return;
            }

            const example = this.applyJobTailoredExample(this.exampleModuleData(mod.type), mod.type);
            if (!example || Object.keys(example).length === 0) {
                return;
            }

            this.pushUndo();
            const nextData = { ...(mod.data || {}) };
            Object.entries(example).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    const currentItems = Array.isArray(nextData[key]) ? nextData[key].filter((item) => typeof item === 'string' && item.trim() !== '') : [];
                    if (currentItems.length === 0) {
                        nextData[key] = [...value];
                    }
                    return;
                }

                const currentValue = typeof nextData[key] === 'string' ? nextData[key].trim() : '';
                if (currentValue === '') {
                    nextData[key] = value;
                }
            });

            if (mod.type === 'objective' && (!this.streamTargetJob || this.streamTargetJob.trim() === '') && typeof nextData.target_job === 'string') {
                this.streamTargetJob = nextData.target_job;
            }

            this.modules.splice(index, 1, {
                ...mod,
                data: nextData,
            });
            this.activeIndex = index;
            this.dirty = true;
            this.showToast('已插入示例内容，可按你的真实经历修改', 'success');
            this.$nextTick(() => this.scrollToModule(index, 'editor'));
        },
