<?php

declare(strict_types=1);

/**
 * MBTI 28题精简版题库配置
 *
 * 维度说明：
 * - E/I 外向/内向
 * - S/N 实感/直觉
 * - T/F 思考/情感
 * - J/P 判断/感知
 *
 * @return array{
 *   title: string,
 *   description: string,
 *   estimated_minutes: int,
 *   dimensions: array<string, array{name: string, poles: array<int, string>}>,
 *   questions: array<int, array{id: int, dimension: string, text: string, options: array<int, array{text: string, value: string}>}>
 * }
 */
return [
    'title' => 'MBTI 28题精简版',
    'description' => '基于荣格类型理论，识别你的能量方向、信息接收、决策方式与生活态度。',
    'estimated_minutes' => 8,
    'dimensions' => [
        'EI' => [
            'name' => '能量方向',
            'poles' => ['E' => '外向 Extraversion', 'I' => '内向 Introversion'],
        ],
        'SN' => [
            'name' => '信息接收',
            'poles' => ['S' => '实感 Sensing', 'N' => '直觉 Intuition'],
        ],
        'TF' => [
            'name' => '决策方式',
            'poles' => ['T' => '思考 Thinking', 'F' => '情感 Feeling'],
        ],
        'JP' => [
            'name' => '生活态度',
            'poles' => ['J' => '判断 Judging', 'P' => '感知 Perceiving'],
        ],
    ],
    'questions' => [
        // ===== E/I 维度（7题）=====
        ['id' => 1, 'dimension' => 'EI', 'text' => '在聚会中，你通常：', 'options' => [
            ['text' => '主动结识新朋友，享受热闹', 'value' => 'E'],
            ['text' => '更愿意与熟人深入交流', 'value' => 'I'],
        ]],
        ['id' => 2, 'dimension' => 'EI', 'text' => '工作一段时间后，你会通过什么方式恢复精力？', 'options' => [
            ['text' => '外出活动或与人交流', 'value' => 'E'],
            ['text' => '独处、安静地做事', 'value' => 'I'],
        ]],
        ['id' => 3, 'dimension' => 'EI', 'text' => '面对新想法，你倾向于：', 'options' => [
            ['text' => '立刻与他人讨论', 'value' => 'E'],
            ['text' => '先自己思考清楚再说', 'value' => 'I'],
        ]],
        ['id' => 4, 'dimension' => 'EI', 'text' => '你的注意力更多放在：', 'options' => [
            ['text' => '周围的人和事', 'value' => 'E'],
            ['text' => '内心的想法和感受', 'value' => 'I'],
        ]],
        ['id' => 5, 'dimension' => 'EI', 'text' => '在团队中，你：', 'options' => [
            ['text' => '喜欢发言、推动讨论', 'value' => 'E'],
            ['text' => '更愿意倾听、择机表达', 'value' => 'I'],
        ]],
        ['id' => 6, 'dimension' => 'EI', 'text' => '认识新朋友时，你：', 'options' => [
            ['text' => '主动开口，话题不断', 'value' => 'E'],
            ['text' => '等对方先开口，慢慢了解', 'value' => 'I'],
        ]],
        ['id' => 7, 'dimension' => 'EI', 'text' => '周末休息时，你更倾向：', 'options' => [
            ['text' => '约朋友聚会或外出', 'value' => 'E'],
            ['text' => '在家看书/看剧/独处', 'value' => 'I'],
        ]],
        // ===== S/N 维度（7题）=====
        ['id' => 8, 'dimension' => 'SN', 'text' => '学习新知识时，你更关注：', 'options' => [
            ['text' => '具体的事实和操作步骤', 'value' => 'S'],
            ['text' => '背后的原理和可能性', 'value' => 'N'],
        ]],
        ['id' => 9, 'dimension' => 'SN', 'text' => '描述一件事时，你会：', 'options' => [
            ['text' => '按时间顺序、具体细节', 'value' => 'S'],
            ['text' => '讲整体含义、跳跃关联', 'value' => 'N'],
        ]],
        ['id' => 10, 'dimension' => 'SN', 'text' => '你更欣赏：', 'options' => [
            ['text' => '脚踏实地的实干家', 'value' => 'S'],
            ['text' => '有远见卓识的幻想家', 'value' => 'N'],
        ]],
        ['id' => 11, 'dimension' => 'SN', 'text' => '面对新任务，你倾向于：', 'options' => [
            ['text' => '参考已有经验和做法', 'value' => 'S'],
            ['text' => '寻找全新的解决思路', 'value' => 'N'],
        ]],
        ['id' => 12, 'dimension' => 'SN', 'text' => '阅读时，你更喜欢：', 'options' => [
            ['text' => '实用、可操作的内容', 'value' => 'S'],
            ['text' => '富有想象力的内容', 'value' => 'N'],
        ]],
        ['id' => 13, 'dimension' => 'SN', 'text' => '你认为更重要的：', 'options' => [
            ['text' => '把眼前的事做好', 'value' => 'S'],
            ['text' => '看到未来的可能性', 'value' => 'N'],
        ]],
        ['id' => 14, 'dimension' => 'SN', 'text' => '你说话的方式：', 'options' => [
            ['text' => '具体、明确、有细节', 'value' => 'S'],
            ['text' => '抽象、隐喻、有想象', 'value' => 'N'],
        ]],
        // ===== T/F 维度（7题）=====
        ['id' => 15, 'dimension' => 'TF', 'text' => '做决定时，你更看重：', 'options' => [
            ['text' => '客观逻辑和公平', 'value' => 'T'],
            ['text' => '人的感受和关系', 'value' => 'F'],
        ]],
        ['id' => 16, 'dimension' => 'TF', 'text' => '朋友遇到问题时，你会：', 'options' => [
            ['text' => '帮他分析原因、给方案', 'value' => 'T'],
            ['text' => '先共情、安抚情绪', 'value' => 'F'],
        ]],
        ['id' => 17, 'dimension' => 'TF', 'text' => '你更希望被评价为：', 'options' => [
            ['text' => '公正、有能力', 'value' => 'T'],
            ['text' => '温暖、有同理心', 'value' => 'F'],
        ]],
        ['id' => 18, 'dimension' => 'TF', 'text' => '批评他人时，你会：', 'options' => [
            ['text' => '直接指出问题', 'value' => 'T'],
            ['text' => '委婉表达、照顾感受', 'value' => 'F'],
        ]],
        ['id' => 19, 'dimension' => 'TF', 'text' => '你认为好的领导应：', 'options' => [
            ['text' => '坚持原则、赏罚分明', 'value' => 'T'],
            ['text' => '关心团队、凝聚人心', 'value' => 'F'],
        ]],
        ['id' => 20, 'dimension' => 'TF', 'text' => '面对冲突，你倾向于：', 'options' => [
            ['text' => '就事论事地解决', 'value' => 'T'],
            ['text' => '先修复关系再讨论', 'value' => 'F'],
        ]],
        ['id' => 21, 'dimension' => 'TF', 'text' => '你更在意：', 'options' => [
            ['text' => '事情是否正确', 'value' => 'T'],
            ['text' => '大家是否开心', 'value' => 'F'],
        ]],
        // ===== J/P 维度（7题）=====
        ['id' => 22, 'dimension' => 'JP', 'text' => '你的工作风格：', 'options' => [
            ['text' => '提前规划、按计划执行', 'value' => 'J'],
            ['text' => '灵活应变、临场发挥', 'value' => 'P'],
        ]],
        ['id' => 23, 'dimension' => 'JP', 'text' => '面对截止日期，你：', 'options' => [
            ['text' => '尽早完成、避免拖延', 'value' => 'J'],
            ['text' => '临近时才有动力', 'value' => 'P'],
        ]],
        ['id' => 24, 'dimension' => 'JP', 'text' => '你的桌面/房间通常：', 'options' => [
            ['text' => '整洁有序', 'value' => 'J'],
            ['text' => '比较随意', 'value' => 'P'],
        ]],
        ['id' => 25, 'dimension' => 'JP', 'text' => '旅行时，你更喜欢：', 'options' => [
            ['text' => '详细规划行程', 'value' => 'J'],
            ['text' => '说走就走、随机应变', 'value' => 'P'],
        ]],
        ['id' => 26, 'dimension' => 'JP', 'text' => '你对待规则的态度：', 'options' => [
            ['text' => '遵守规则、维护秩序', 'value' => 'J'],
            ['text' => '必要时可以变通', 'value' => 'P'],
        ]],
        ['id' => 27, 'dimension' => 'JP', 'text' => '做决定时，你：', 'options' => [
            ['text' => '尽快决定、开始行动', 'value' => 'J'],
            ['text' => '保留多种选择、推迟决定', 'value' => 'P'],
        ]],
        ['id' => 28, 'dimension' => 'JP', 'text' => '完成项目后，你：', 'options' => [
            ['text' => '收尾整理、归档', 'value' => 'J'],
            ['text' => '立刻转向下一个有趣的事', 'value' => 'P'],
        ]],
    ],
    'type_codes' => [
        'INTJ' => ['label' => '建筑师', 'desc' => '富有想象力又有战略思维，善于规划长期目标'],
        'INTP' => ['label' => '逻辑学家', 'desc' => '理性好奇，喜欢分析理论和抽象概念'],
        'ENTJ' => ['label' => '指挥官', 'desc' => '天生的领导者，果断、有魄力'],
        'ENTP' => ['label' => '辩论家', 'desc' => '聪明好奇的思想者，喜欢智力挑战'],
        'INFJ' => ['label' => '提倡者', 'desc' => '安静而神秘，理想主义又有原则'],
        'INFP' => ['label' => '调停者', 'desc' => '诗意、善良的利他主义者'],
        'ENFJ' => ['label' => '主人公', 'desc' => '富有魅力、鼓舞人心的领导者'],
        'ENFP' => ['label' => '竞选者', 'desc' => '热情、有创造力、爱社交的自由灵魂'],
        'ISTJ' => ['label' => '物流师', 'desc' => '实际、注重事实、可靠'],
        'ISFJ' => ['label' => '守卫者', 'desc' => '非常专注、温暖的守护者'],
        'ESTJ' => ['label' => '总经理', 'desc' => '出色的管理者，井井有条'],
        'ESFJ' => ['label' => '执政官', 'desc' => '极有同情心、受欢迎、爱帮助他人'],
        'ISTP' => ['label' => '鉴赏家', 'desc' => '大胆而实际的实验家'],
        'ISFP' => ['label' => '探险家', 'desc' => '灵活、有魅力的艺术家'],
        'ESTP' => ['label' => '企业家', 'desc' => '聪明、精力充沛、善于感知'],
        'ESFP' => ['label' => '表演者', 'desc' => '自发的、热情的表演者'],
    ],
];
