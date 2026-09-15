<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Interview Max Questions
    |--------------------------------------------------------------------------
    |
    | Controls how many rounds a single interview session can contain.
    | You can override with INTERVIEW_MAX_QUESTIONS in environment.
    |
    */
    'max_questions' => (int) env('INTERVIEW_MAX_QUESTIONS', 15),

    'supported_types' => [
        'technical', 'behavioral', 'mixed',
        'sales', 'management', 'creative', 'finance',
        'retail', 'manufacturing',
        'service', 'media', 'education',
        'deep',
    ],

    /*
    |--------------------------------------------------------------------------
    | Interview Session Timeout (Minutes)
    |--------------------------------------------------------------------------
    */
    'session_timeout_minutes' => (int) env('INTERVIEW_SESSION_TIMEOUT_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Interview Question Generation
    |--------------------------------------------------------------------------
    */
    'resume_excerpt_chars' => (int) env('INTERVIEW_RESUME_EXCERPT_CHARS', 1200),
    'question_max_length' => (int) env('INTERVIEW_QUESTION_MAX_LENGTH', 180),
    'evaluation_queue' => env('INTERVIEW_EVALUATION_QUEUE', 'default'),
    'evaluation_poll_interval_ms' => (int) env('INTERVIEW_EVALUATION_POLL_INTERVAL_MS', 1200),
    'evaluation_poll_fast_attempts' => (int) env('INTERVIEW_EVALUATION_POLL_FAST_ATTEMPTS', 3),
    'evaluation_poll_fast_interval_ms' => (int) env('INTERVIEW_EVALUATION_POLL_FAST_INTERVAL_MS', 600),
    'evaluation_poll_slow_interval_ms' => (int) env('INTERVIEW_EVALUATION_POLL_SLOW_INTERVAL_MS', 1300),
    'evaluation_poll_jitter_ms' => (int) env('INTERVIEW_EVALUATION_POLL_JITTER_MS', 120),
    'evaluation_poll_request_timeout_ms' => (int) env('INTERVIEW_EVALUATION_POLL_REQUEST_TIMEOUT_MS', 6000),
    'evaluation_poll_max_attempts' => (int) env('INTERVIEW_EVALUATION_POLL_MAX_ATTEMPTS', 30),
    'evaluation_resume_poll_delay_ms' => (int) env('INTERVIEW_EVALUATION_RESUME_POLL_DELAY_MS', 15000),
    'evaluation_pending_timeout_seconds' => (int) env('INTERVIEW_EVALUATION_PENDING_TIMEOUT_SECONDS', 90),
    'evaluation_result_cache_enabled' => (bool) env('INTERVIEW_EVALUATION_RESULT_CACHE_ENABLED', true),
    'evaluation_result_cache_force_ai_enabled' => (bool) env('INTERVIEW_EVALUATION_RESULT_CACHE_FORCE_AI_ENABLED', true),
    'evaluation_result_cache_ttl_seconds' => (int) env('INTERVIEW_EVALUATION_RESULT_CACHE_TTL_SECONDS', 900),
    'evaluation_result_cache_max_answer_chars' => (int) env('INTERVIEW_EVALUATION_RESULT_CACHE_MAX_ANSWER_CHARS', 3000),
    'evaluation_heal_enabled' => (bool) env('INTERVIEW_EVALUATION_HEAL_ENABLED', true),
    'evaluation_heal_older_seconds' => (int) env('INTERVIEW_EVALUATION_HEAL_OLDER_SECONDS', 90),
    'evaluation_heal_limit' => (int) env('INTERVIEW_EVALUATION_HEAL_LIMIT', 100),
    'evaluation_heal_fallback_after_seconds' => (int) env('INTERVIEW_EVALUATION_HEAL_FALLBACK_AFTER_SECONDS', 240),
    'ai_early_termination_enabled' => (bool) env('INTERVIEW_AI_EARLY_TERMINATION_ENABLED', true),
    'ai_early_termination_min_consecutive_low_answers' => (int) env('INTERVIEW_AI_EARLY_TERMINATION_MIN_CONSECUTIVE_LOW_ANSWERS', 2),
    'ai_early_termination_min_answered_questions' => (int) env('INTERVIEW_AI_EARLY_TERMINATION_MIN_ANSWERED_QUESTIONS', 2),
    'ai_early_termination_min_confidence' => (float) env('INTERVIEW_AI_EARLY_TERMINATION_MIN_CONFIDENCE', 0.75),
    'evaluation_fast_path_enabled' => (bool) env('INTERVIEW_EVALUATION_FAST_PATH_ENABLED', true),
    'evaluation_fast_path_short_answer_chars' => (int) env('INTERVIEW_EVALUATION_FAST_PATH_SHORT_ANSWER_CHARS', 20),
    'evaluation_fast_path_medium_enabled' => (bool) env('INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ENABLED', true),
    'evaluation_fast_path_medium_answer_chars' => (int) env('INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ANSWER_CHARS', 60),
    'ai_fluency_detection_enabled' => (bool) env('INTERVIEW_AI_FLUENCY_DETECTION_ENABLED', true),
    'ai_dialogue_decision_enabled' => (bool) env('INTERVIEW_AI_DIALOGUE_DECISION_ENABLED', true),
    'ai_dialogue_sync_enabled' => (bool) env('INTERVIEW_AI_DIALOGUE_SYNC_ENABLED', true),
    'ai_dialogue_sync_max_answer_chars' => (int) env('INTERVIEW_AI_DIALOGUE_SYNC_MAX_ANSWER_CHARS', 90),
    'ai_dialogue_sync_low_quality_only' => (bool) env('INTERVIEW_AI_DIALOGUE_SYNC_LOW_QUALITY_ONLY', false),
    'ai_dialogue_max_followups_per_answer' => (int) env('INTERVIEW_AI_DIALOGUE_MAX_FOLLOWUPS_PER_ANSWER', 2),

    /*
    |--------------------------------------------------------------------------
    | Interview Dimension Matrix
    |--------------------------------------------------------------------------
    |
    | Define fixed capability dimensions per interview type. The generator
    | rotates dimensions by round to ensure stable coverage.
    |
    */
    'dimension_matrix' => [
        'technical' => [
            '基础原理与工程实践',
            '系统设计与性能优化',
            '故障排查与质量保障',
            '技术决策与架构权衡',
            '业务理解与技术落地',
            '项目管理与进度把控',
            '代码审查与质量规范',
            '技术选型与升级迁移',
            '跨团队技术协作与推动',
            '数据驱动与指标体系建设',
            '技术视野与前沿探索',
            '开源贡献与社区参与',
            '技术写作与知识分享',
            '面试官视角与人才评估',
            '职业规划与技术成长路径',
        ],
        'behavioral' => [
            '沟通表达与结构化思维',
            '团队协作与跨团队推进',
            '冲突处理与影响力',
            '抗压能力与优先级管理',
            '复盘成长与自驱力',
            '职业发展规划与目标',
            '客户需求理解与对接',
            '领导力与团队管理',
            '危机处理与应变能力',
            '文档规范与知识沉淀',
            '工作生活平衡与时间管理',
            '价值观与职业选择',
            '失败反思与韧性成长',
            '跨界学习与多元视角',
            '利他精神与团队贡献',
        ],
        'mixed' => [
            '岗位基础能力',
            '复杂问题拆解',
            '协作推进与沟通',
            '结果导向与业务价值',
            '复盘优化与持续成长',
            '项目管理与交付意识',
            '工作流程优化与效率',
            '跨角色协调与向上汇报',
            '自主学习与技术视野',
            '团队贡献与文化建设',
            '抗压韧性与心态管理',
            '职业方向与长期规划',
            '行业洞察与趋势判断',
            '个人品牌与影响力建设',
            '多元化背景与包容性',
        ],
        'sales' => [
            '客户需求挖掘与分析',
            '销售策略与漏斗管理',
            '商务谈判与合同推进',
            '客户关系维护与续约',
            '业绩目标拆解与达成',
            '市场洞察与竞争分析',
            '跨部门协调与资源整合',
            '销售工具与数据驱动',
            '团队协作与经验分享',
            '行业认知与趋势判断',
        ],
        'management' => [
            '团队搭建与人才梯队',
            '目标制定与绩效管理',
            '跨部门协调与资源统筹',
            '变革管理与组织推动',
            '战略规划与业务增长',
            '风险识别与危机处理',
            '预算管控与成本优化',
            '文化塑造与凝聚力建设',
            '向上管理与领导力',
            '决策质量与复盘改进',
        ],
        'creative' => [
            '创意思维与方案产出',
            '用户洞察与需求转化',
            '设计规范与品质把控',
            '跨角色协作与方案表达',
            '数据验证与迭代优化',
            '工具链与效率提升',
            '品牌调性与一致性',
            '内容策略与传播效果',
            '项目排期与交付管理',
            '行业趋势与前沿跟踪',
        ],
        'finance' => [
            '财务报表分析与解读',
            '预算管理与成本控制',
            '投资分析与风险评估',
            '税务筹划与合规管理',
            '资金管理与现金流规划',
            '内部控制与审计实务',
            '业务伙伴与决策支持',
            '金融产品与市场理解',
            '数据建模与量化分析',
            '监管政策与行业认知',
        ],
        'retail' => [
            '电商运营与平台策略',
            '品类管理与选品能力',
            '用户增长与转化优化',
            '供应链与库存管理',
            '数据驱动与精细化运营',
            '客户体验与服务设计',
            '营销策划与活动运营',
            '直播带货与内容运营',
            '跨境业务与海外市场',
            '竞争分析与行业趋势',
        ],
        'manufacturing' => [
            '生产管理与排产优化',
            '质量管理与六西格玛',
            '精益生产与成本控制',
            '供应链协同与采购',
            '工艺设计与持续改进',
            '设备管理与智能化',
            '安全管理与风险控制',
            '研发转化与新品导入',
            '环保合规与绿色制造',
            '数字化转型与工业4.0',
        ],
        'service' => [
            '客户服务与体验设计',
            '服务流程与标准制定',
            '投诉处理与危机应对',
            '团队管理与服务绩效',
            '客源开发与会员运营',
            '供应链与采购管理',
            '食品安全与卫生管控',
            '收益管理与定价策略',
            '品牌建设与口碑维护',
            '多门店/多业态运营',
        ],
        'media' => [
            '内容策划与选题能力',
            '传播策略与渠道运营',
            '创意产出与脚本撰写',
            '数据分析与效果评估',
            '热点捕捉与快速响应',
            '品牌合作与商务谈判',
            '用户增长与社群运营',
            '视频制作与后期能力',
            '危机公关与舆情管理',
            '行业趋势与媒介创新',
        ],
        'education' => [
            '课程设计与教学目标',
            '教学方法与课堂管理',
            '学生评估与反馈改进',
            '教育技术与工具运用',
            '家校沟通与协作',
            '学科知识与教研能力',
            '个性化教育与因材施教',
            '教育产品与内容开发',
            '机构运营与招生策略',
            '教育政策与行业趋势',
        ],
        'deep' => [
            '职业价值观与人生追求',
            '关键时刻的决策与取舍',
            '失败经历与成长反思',
            '工作与生活的平衡智慧',
            '压力管理与情绪调节',
            '跨界思考与多元视角',
            '利他与社会责任',
            '长期主义与延迟满足',
            '自我认知与盲区觉察',
            '人际关系与信任构建',
            '创新思维与突破舒适区',
            '文化适应与包容心态',
            '学习力与元认知能力',
            '影响力与使命驱动',
            '年龄增长与持续进化',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Candidate Profile Strategy
    |--------------------------------------------------------------------------
    |
    | Used by question generator to adapt difficulty, tone and focus.
    |
    */
    'candidate_profile_strategy' => [
        'fresh_graduate' => [
            'label' => '应届生',
            'tone' => 'friendly',
            'difficulty' => 'low_to_mid',
            'focus' => [
                '基础能力', '学习潜力', '课程项目实践', '表达结构',
                '实习经历还原', '职业兴趣与规划', '团队协作意识',
                '校园到职场过渡','自我认知与反思','成长型思维',
                '信息搜索与自学能力','时间管理习惯','有效沟通与向上汇报',
            ],
        ],
        'no_experience' => [
            'label' => '无经验转岗',
            'tone' => 'friendly',
            'difficulty' => 'low_to_mid',
            'focus' => ['迁移能力', '学习路径', '小型实践成果', '动机匹配', '自驱力与执行力', '行业认知深度', '快速上手能力'],
        ],
        'junior' => [
            'label' => '1-3年经验',
            'tone' => 'balanced',
            'difficulty' => 'mid',
            'focus' => ['项目落地', '问题拆解', '跨团队协作', '复盘改进', '需求理解与对接', '工作流程优化', '文档与知识沉淀'],
        ],
        'experienced' => [
            'label' => '3年+经验',
            'tone' => 'strict',
            'difficulty' => 'mid_to_high',
            'focus' => ['复杂场景决策', '业务结果', '风险控制', '方法论沉淀', '团队带领与培养', '跨部门推动与影响力', '技术规划与前瞻性', '成本意识与资源优化'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fresh Graduate Interview Growth Path
    |--------------------------------------------------------------------------
    |
    | 6-stage progressive interview guide for fresh graduates.
    | Each stage maps to a round and includes a goal, question angle, and advice.
    |
    */
    'fresh_graduate_growth_path' => [
        1 => [
            'stage' => '破冰摸底',
            'goal' => '评估基础知识扎实程度、校园经历多样性、表达逻辑清晰度',
            'angle' => '从课堂/社团/比赛经历切入，让候选人在熟悉场景中打开话匣子',
            'advice' => '问题示例：大学期间你投入最多的一门课或一个项目是什么？你学到了什么？',
        ],
        2 => [
            'stage' => '项目还原',
            'goal' => '验证课程/实习项目的真实性，评估动手能力和问题解决思路',
            'angle' => '深挖一个具体项目，追问技术选型、遇到的困难、如何解决',
            'advice' => '引导候选人用 STAR 框架还原项目：背景→任务→你做了什么→结果如何',
        ],
        3 => [
            'stage' => '协作与沟通',
            'goal' => '评估团队协作意识、沟通主动性和冲突处理能力',
            'angle' => '引入小组作业/社团合作/实习协作场景，考察角色认知',
            'advice' => '问题示例：在团队中你和队友意见不合时怎么处理？你的角色是什么？',
        ],
        4 => [
            'stage' => '学习与适应',
            'goal' => '评估自学能力、信息搜索能力和面对新技术的适应速度',
            'angle' => '考察"学习新东西"的方法论，从最近的课程/新技术切入',
            'advice' => '问题示例：你最近自学的一个新技能或新工具是什么？学习过程是怎样的？',
        ],
        5 => [
            'stage' => '职业认知',
            'goal' => '评估职业方向清晰度、行业认知深度、求职动机真实性',
            'angle' => '引导表达为什么选择这个岗位/行业，过去做了什么准备',
            'advice' => '问题示例：你为什么选择前端这个方向？你通过什么渠道了解这个领域的？',
        ],
        6 => [
            'stage' => '成长总结',
            'goal' => '综合回顾整场面试，引导自我反思并规划未来3年',
            'angle' => '温和收尾：邀请回顾面试、连接不同话题、描述成长愿景',
            'advice' => '问题示例：回顾今天聊的内容，你觉得最打动面试官的地方是哪点？未来3年你想成为什么样的开发者？',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fresh Graduate Career Path Mapping
    |--------------------------------------------------------------------------
    */
    'fresh_grad_career_paths' => [
        'backend' => ['label' => '后端开发', 'keywords' => ['Java','Python','Go','数据库','API','Spring','微服务','分布式','SQL','Linux'], 'resources' => ['《Java编程思想》|基础','《深入理解计算机系统》|进阶','自己写一个 RESTful API 服务|项目','LeetCode 刷 100 题|练习']],
        'frontend' => ['label' => '前端开发', 'keywords' => ['React','Vue','TypeScript','JavaScript','CSS','HTML','Node.js'], 'resources' => ['《JavaScript 高级程序设计》|基础','Vue/React 官方文档 + 3 个实战项目|进阶','做一个完整的 Todo App 并部署|项目','学习响应式设计和 CSS Grid|练习']],
        'fullstack' => ['label' => '全栈开发', 'keywords' => ['Java','Python','React','Vue','SQL','Docker','Linux','Git'], 'resources' => ['《Web 全栈工程师修炼指南》|基础','前后端分离项目实战（博客/商城）|项目','Docker + CI/CD 部署流程|进阶','参与开源项目贡献代码|练习']],
        'data' => ['label' => '数据分析', 'keywords' => ['Python','SQL','Pandas','数据分析','Tableau','Spark','机器学习','统计学'], 'resources' => ['《Python 数据处理》|基础','Kaggle 入门竞赛 3 个|项目','学习 Tableau/Power BI 做可视化|进阶','统计学基础 + A/B 测试|理论']],
        'product' => ['label' => '产品经理', 'keywords' => ['需求分析','用户研究','竞品分析','PRD','数据分析','A/B测试','项目管理'], 'resources' => ['《人人都是产品经理》|基础','写 3 篇产品分析报告|项目','学习 Figma 原型设计|工具','参加产品 Hackathon|练习']],
        'devops' => ['label' => '运维/DevOps', 'keywords' => ['Linux','Docker','K8s','AWS','CI/CD','Shell','监控','Nginx'], 'resources' => ['《鸟哥的 Linux 私房菜》|基础','搭建个人博客并自动化部署|项目','AWS/GCP 免费试用搭建服务|进阶','学习 Terraform/IaC|进阶']],
        'ai' => ['label' => 'AI/算法', 'keywords' => ['Python','机器学习','深度学习','PyTorch','TensorFlow','NLP','CV','数学'], 'resources' => ['吴恩达《机器学习》课程|基础','Kaggle 竞赛 Top 10%|项目','《深度学习》（花书）|理论','复现一篇经典论文|进阶']],
        'general' => ['label' => '通用建议', 'keywords' => [], 'resources' => ['用 STAR 框架重构简历|核心','准备 1 分钟自我介绍电梯演讲|核心','在牛客/LeetCode 刷 50 题|练习','联系 3 位校友做模拟面试|人脉']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fresh Graduate Coaching Messages (per round)
    |--------------------------------------------------------------------------
    */
    'interview_type_groups' => [
        'core' => [
            'label' => '核心面试',
            'types' => [
                'technical' => ['label' => '技术面试', 'icon' => 'ti-code'],
                'behavioral' => ['label' => '行为面试', 'icon' => 'ti-users'],
                'mixed' => ['label' => '综合面试', 'icon' => 'ti-adjustments'],
            ],
        ],
        'industry' => [
            'label' => '行业面试',
            'types' => [
                'sales' => ['label' => '销售/BD', 'icon' => 'ti-chart-bar'],
                'management' => ['label' => '管理岗', 'icon' => 'ti-building'],
                'creative' => ['label' => '创意/设计', 'icon' => 'ti-palette'],
                'finance' => ['label' => '金融/财务', 'icon' => 'ti-report-money'],
                'retail' => ['label' => '零售/电商', 'icon' => 'ti-shopping-cart'],
                'manufacturing' => ['label' => '制造/工业', 'icon' => 'ti-tool'],
                'service' => ['label' => '服务行业', 'icon' => 'ti-headset'],
                'media' => ['label' => '传媒/广告', 'icon' => 'ti-speakerphone'],
                'education' => ['label' => '教育/培训', 'icon' => 'ti-book'],
            ],
        ],
        'special' => [
            'label' => '特色面试',
            'types' => [
                'deep' => ['label' => '深度面谈', 'icon' => 'ti-brain'],
            ],
        ],
    ],

    'profile_type_recommendations' => [
        'fresh_graduate' => ['mixed', 'technical', 'behavioral'],
        'no_experience' => ['mixed', 'behavioral', 'technical'],
        'junior' => ['technical', 'mixed', 'behavioral'],
        'experienced' => ['technical', 'management', 'mixed'],
    ],

    'tech_keywords' => [
        'technical' => ['Java','Spring','Python','Django','Go','Rust','React','Vue','TypeScript','Node.js','SQL','MySQL','PostgreSQL','Redis','MongoDB','Docker','K8s','AWS','Linux','Git','CI/CD','微服务','分布式','REST','GraphQL','Kafka','Nginx','Flutter','Swift','Kotlin','机器学习','深度学习','NLP','数据分析','Pandas','Spark','Hadoop','Tableau','Power BI','数据仓库','A/B测试','推荐系统'],
        'behavioral' => ['项目管理','OKR','SaaS','CRM','数据分析','招聘','绩效管理','薪酬设计','员工关系','组织发展','劳动法','HRBP','培训发展','企业文化','人才盘点','时间管理','压力管理','情绪调节','成长型思维','深度工作','终身学习','阅读写作','演讲','谈判','冥想','健身','营养','睡眠管理','个人理财','创业','副业','自媒体','数字游民','远程协作','自由职业','T型人才','斜杠青年'],
        'mixed' => ['项目管理','OKR','SaaS','CRM','数据分析','用户研究','产品运营','数据可视化','内容策略','增长黑客','社群运营','品牌营销','SEO','UX/UI','时间管理','压力管理','情绪调节','成长型思维','深度工作','终身学习','阅读写作','演讲','谈判','创业','副业','自媒体','远程协作','自由职业','T型人才','斜杠青年'],
        'sales' => ['销售管理','商务谈判','渠道管理','客户开发','提案能力','招投标','合同管理','CRM','电销','会销','品牌策划','SEO/SEM','社交媒体营销','内容营销','数字营销','私域运营','KOL营销','Google Analytics','市场调研','活动策划','广告策划','媒介投放','品牌传播','危机公关','舆情监测','整合营销'],
        'management' => ['项目管理','OKR','SaaS','CRM','数据分析','招聘','绩效管理','薪酬设计','员工关系','组织发展','劳动法','HRBP','培训发展','企业文化','人才盘点','战略规划','尽职调查','商业分析','PPT制作','行业研究','管理咨询','预算管理','资金管理','风控','会计','财务分析','投资分析'],
        'creative' => ['Figma','Sketch','UI设计','交互设计','用户研究','设计系统','原型设计','视觉设计','品牌设计','插画','C4D','Blender','文案策划','视频剪辑','公众号运营','抖音运营','直播运营','脚本撰写','新闻采编','游戏策划','游戏开发','游戏运营','音效设计','UE4','Unity','游戏美术','策展','艺术品管理','出版编辑','音乐制作','演出经纪'],
        'finance' => ['财务分析','投资分析','审计','税务','风控','会计','预算管理','资金管理','估值','CFA','CPA','Bloomberg','保险精算','保险产品','理赔管理','再保险','核保','合同审查','知识产权','合规管理','法律研究','诉讼仲裁'],
        'retail' => ['电商运营','直播带货','店铺运营','品类管理','供应链管理','库存管理','私域流量','转化率优化','跨境电商','社群运营','选品策略','用户体验','品牌策划','SEO/SEM','社交媒体营销','内容营销','数字营销','私域运营','KOL营销','市场调研','活动策划','物流管理','仓储管理','ERP','采购管理','库存优化'],
        'manufacturing' => ['生产管理','精益生产','六西格玛','质量管理','PMC','IE','设备维护','工艺流程','安全管理','AutoCAD','BIM','施工管理','工程造价','结构设计','供应链管理','物流管理','仓储管理','ERP','采购管理','库存优化','检测技术','实验室管理','认证审核','ISO','CNAS','计量校准'],
        'service' => ['酒店管理','旅游规划','餐饮运营','客户服务','票务系统','OTA','收益管理','宴会策划','食品安全','养老服务','家政管理','母婴护理','社区服务','康复护理','宠物医疗','宠物美容','宠物食品','门店运营','宠物训练','动物福利','供应链管理','物流管理','仓储管理','ERP','采购管理'],
        'media' => ['文案策划','视频剪辑','公众号运营','抖音运营','直播运营','脚本撰写','新闻采编','广告策划','媒介投放','品牌传播','危机公关','舆情监测','整合营销','展会策划','会展运营','会议管理','展览设计','活动执行','翻译','本地化','口译','CAT工具'],
        'education' => ['课程设计','教学设计','在线教育','培训管理','教育技术','MOOC','课件开发','招聘','绩效管理','培训发展','企业文化','人才盘点','HRBP'],
        'deep' => ['时间管理','压力管理','情绪调节','成长型思维','正念','深度工作','终身学习','阅读写作','演讲','谈判','冥想','健身','营养','睡眠管理','个人理财','创业','副业','自媒体','数字游民','远程协作','自由职业','T型人才','斜杠青年','UX/UI','用户研究','产品运营','数据分析','项目管理','数据可视化','内容策略','增长黑客','社群运营','品牌营销','SEO'],
        'common' => ['项目管理','数据分析','用户研究','SQL','Excel','PPT制作','沟通协作','英语','日语','法语','德语','韩语'],
    ],

    'fresh_grad_coaching' => [
        'intro' => '别紧张，就像和前辈聊天一样。面试官更想看到真实的你，而不是完美的你。',
        'after_r1' => '很好的开始！接下来我们可以聊得更深入一点，谈谈你实际做过的项目。',
        'after_r2' => '你的项目经历很有意思。现在让我们聊聊你在团队中的角色和协作方式。',
        'after_r3' => '你已经展现了不错的团队意识。接下来聊聊你是怎么学新东西的？',
        'after_r4' => '学习能力是职场最重要的能力之一。最后我们聊聊你对职业的想法和规划。',
        'final' => '面试结束了！表现很棒 👏 无论结果如何，每次面试都是一次成长。回头看看报告中的建议，继续加油！',
    ],
];
