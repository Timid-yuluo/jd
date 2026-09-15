        moduleTitleLabel(mod) {
            if (mod?.type === 'certificate') {
                return '模块标题（可选）';
            }

            return '模块标题';
        },

        moduleTitlePlaceholder(mod) {
            const placeholderMap = {
                education: '例如：教育经历 / 学历信息',
                experience: '例如：实习经历 / 项目实践',
                project: '例如：项目经验 / 核心项目',
                skill: '例如：技能证书 / 专业技能',
                certificate: '例如：获奖情况 / 荣誉经历',
            };

            return placeholderMap[mod?.type] || '请输入模块标题';
        },

        moduleSubtitleLabel(mod) {
            const labelMap = {
                education: '学校 / 专业',
                experience: '公司 / 职位',
                project: '项目名称 / 角色',
            };

            return labelMap[mod?.type] || '副标题';
        },

        moduleSubtitlePlaceholder(mod) {
            const placeholderMap = {
                education: '例如：北京大学 计算机科学与技术',
                experience: '例如：字节跳动 前端实习生',
                project: '例如：电商小程序 前端负责人',
            };

            return placeholderMap[mod?.type] || '请输入';
        },

        moduleDateLabel(mod) {
            return '时间';
        },

        moduleDatePlaceholder(mod) {
            const placeholderMap = {
                education: '例如：2022.09 - 2026.06',
                experience: '例如：2024.06 - 2024.09',
                project: '例如：2024.03 - 2024.05',
            };

            return placeholderMap[mod?.type] || '例如：2022 - 2024';
        },

        moduleLocationLabel(mod) {
            const labelMap = {
                education: '地点',
                experience: '地点',
                project: '链接（可选）',
            };

            return labelMap[mod?.type] || '地点';
        },

        moduleLocationPlaceholder(mod) {
            const placeholderMap = {
                education: '城市',
                experience: '城市',
                project: 'GitHub / 演示地址',
            };

            return placeholderMap[mod?.type] || '请输入地点';
        },

        moduleContentLabel(mod) {
            const labelMap = {
                education: '描述',
                experience: '工作内容',
                project: '项目描述',
            };

            return labelMap[mod?.type] || '内容';
        },

        moduleContentPlaceholder(mod) {
            const placeholderMap = {
                education: '主修课程、GPA、荣誉等',
                experience: '职责描述、成果数据、技术栈等',
                project: '背景、职责、技术方案、量化成果',
            };

            return placeholderMap[mod?.type] || '请输入内容';
        },

        moduleItemsLabel(mod) {
            const labelMap = {
                education: '课程 / 奖项 / 成果',
                experience: '职责 / 成果要点',
                project: '项目亮点 / 技术贡献',
                certificate: '证书 / 荣誉列表',
                skill: '技能关键词',
            };

            return labelMap[mod?.type] || '条目列表';
        },

        moduleItemPlaceholder(mod) {
            const placeholderMap = {
                education: '例如：主修数据库系统、获得校一等奖学金',
                experience: '例如：负责招聘系统重构，接口响应时间下降 35%',
                project: '例如：设计多租户权限模型，支持 100+ 企业客户接入',
                certificate: '例如：PMP 认证 / 蓝桥杯省一等奖 / 阿里云 ACP',
                skill: '例如：Laravel / Redis / Docker / MySQL 优化',
            };

            return placeholderMap[mod?.type] || '请输入条目内容';
        },

        moduleAddItemText(mod) {
            const labelMap = {
                education: '添加课程或成果',
                experience: '添加职责或成果',
                project: '添加项目亮点',
                certificate: '添加证书或荣誉',
                skill: '添加技能项',
            };

            return labelMap[mod?.type] || '添加条目';
        },

        moduleEmptyItemsText(mod) {
            const textMap = {
                education: '建议至少补 1-2 条课程、奖项或研究成果，让教育经历更有信息量。',
                experience: '建议至少补 2 条职责或量化成果，ATS 和 AI 会更容易识别你的价值。',
                project: '建议至少补 2 条项目亮点，例如技术方案、性能优化或业务结果。',
                certificate: '把已获得的证书、奖项或竞赛荣誉列出来，能提升可信度。',
                skill: '优先填写与目标岗位最相关的技术栈、工具链和业务能力关键词。',
            };

            return textMap[mod?.type] || '先补充至少 1 条关键信息。';
        },

        moduleAssistText(mod) {
            if (mod?.type === 'personal') {
                const filled = ['name', 'phone', 'email', 'location', 'gender', 'birthday', 'wechat', 'github', 'website']
                    .filter((field) => this.hasMeaningfulText(mod, field)).length;
                return `基础信息已填写 ${filled}/9，建议至少补齐姓名、电话、邮箱、所在地，并补充外链信息提升完整度。`;
            }

            if (mod?.type === 'objective') {
                if (!this.hasMeaningfulText(mod, 'target_job')) {
                    return '目标岗位写得越具体，右侧 AI 优化与 ATS 评分越准确。';
                }

                return `当前目标岗位：${mod.data.target_job.trim()}，再补一句岗位方向说明会更完整。`;
            }

            if (mod?.type === 'summary') {
                const length = this.textLength(mod?.data?.content);
                if (length < 30) {
                    return `当前约 ${length} 字，建议写到 80 字以上，突出年限、技术栈、业务成果和协作能力。`;
                }

                if (length < 80) {
                    return `当前约 ${length} 字，信息已有基础，再补 1-2 句量化成果会更有说服力。`;
                }

                return `当前约 ${length} 字，长度较合适，可继续优化为更有结果导向的表达。`;
            }

            return '';
        },

        moduleFieldLabelByKey(field) {
            const map = {
                title: '标题',
                subtitle: '副标题',
                date: '时间',
                location: '地点',
                content: '描述',
                items: '条目',
                name: '姓名',
                phone: '电话',
                email: '邮箱',
                gender: '性别',
                birthday: '生日',
                wechat: '微信',
                github: 'GitHub',
                website: '作品集',
                custom_fields: '自定义字段',
                target_job: '目标岗位',
                avatar: '头像',
            };
            return map[String(field || '')] || String(field || '字段');
        },
