<?php

declare(strict_types=1);

/**
 * DISC 24题版题库配置
 *
 * 四维度：
 * - D 支配型 Dominance
 * - I 影响型 Influence
 * - S 稳健型 Steadiness
 * - C 谨慎型 Conscientiousness
 *
 * @return array{
 *   title: string,
 *   description: string,
 *   estimated_minutes: int,
 *   dimensions: array<string, array{name: string, color: string}>,
 *   questions: array<int, array{id: int, text: string, options: array<int, array{text: string, value: string}>}>
 * }
 */
return [
    'title' => 'DISC 24题版',
    'description' => '评估你的行为风格，识别你在工作场景中的沟通、决策和执行方式。',
    'estimated_minutes' => 5,
    'dimensions' => [
        'D' => ['name' => '支配型 Dominance', 'color' => 'danger', 'desc' => '直接、果断、目标导向'],
        'I' => ['name' => '影响型 Influence', 'color' => 'warning', 'desc' => '热情、乐观、善于交际'],
        'S' => ['name' => '稳健型 Steadiness', 'color' => 'success', 'desc' => '耐心、稳定、善于倾听'],
        'C' => ['name' => '谨慎型 Conscientiousness', 'color' => 'info', 'desc' => '精确、分析、注重细节'],
    ],
    'questions' => [
        ['id' => 1, 'text' => '面对压力时，我倾向于：', 'options' => [
            ['text' => '直接面对，迅速解决', 'value' => 'D'],
            ['text' => '寻求他人支持', 'value' => 'I'],
            ['text' => '保持冷静，按部就班', 'value' => 'S'],
            ['text' => '分析原因，制定方案', 'value' => 'C'],
        ]],
        ['id' => 2, 'text' => '在团队中，我通常：', 'options' => [
            ['text' => '推动决策、掌控局面', 'value' => 'D'],
            ['text' => '活跃气氛、激励他人', 'value' => 'I'],
            ['text' => '协调关系、稳定团队', 'value' => 'S'],
            ['text' => '提供数据、确保准确', 'value' => 'C'],
        ]],
        ['id' => 3, 'text' => '做决定时，我更看重：', 'options' => [
            ['text' => '结果和效率', 'value' => 'D'],
            ['text' => '人际关系和氛围', 'value' => 'I'],
            ['text' => '团队共识和稳定', 'value' => 'S'],
            ['text' => '数据和逻辑', 'value' => 'C'],
        ]],
        ['id' => 4, 'text' => '我的工作节奏：', 'options' => [
            ['text' => '快速、有冲劲', 'value' => 'D'],
            ['text' => '灵活、有激情', 'value' => 'I'],
            ['text' => '稳定、有规律', 'value' => 'S'],
            ['text' => '严谨、有标准', 'value' => 'C'],
        ]],
        ['id' => 5, 'text' => '面对变化，我：', 'options' => [
            ['text' => '主动推动、把握机会', 'value' => 'D'],
            ['text' => '兴奋、期待新体验', 'value' => 'I'],
            ['text' => '谨慎适应、保持稳定', 'value' => 'S'],
            ['text' => '评估风险、做好准备', 'value' => 'C'],
        ]],
        ['id' => 6, 'text' => '沟通时，我倾向于：', 'options' => [
            ['text' => '直接、简洁', 'value' => 'D'],
            ['text' => '生动、有感染力', 'value' => 'I'],
            ['text' => '温和、耐心', 'value' => 'S'],
            ['text' => '准确、有条理', 'value' => 'C'],
        ]],
        ['id' => 7, 'text' => '面对冲突，我会：', 'options' => [
            ['text' => '正面交锋、争取胜利', 'value' => 'D'],
            ['text' => '化解尴尬、调和气氛', 'value' => 'I'],
            ['text' => '退让、维护和谐', 'value' => 'S'],
            ['text' => '用事实说话、理性分析', 'value' => 'C'],
        ]],
        ['id' => 8, 'text' => '我最看重的工作环境：', 'options' => [
            ['text' => '充满挑战和竞争', 'value' => 'D'],
            ['text' => '活跃、自由、有创意', 'value' => 'I'],
            ['text' => '稳定、和谐、有支持', 'value' => 'S'],
            ['text' => '规范、专业、有秩序', 'value' => 'C'],
        ]],
        ['id' => 9, 'text' => '我的领导风格：', 'options' => [
            ['text' => '指令式、目标导向', 'value' => 'D'],
            ['text' => '激励式、愿景导向', 'value' => 'I'],
            ['text' => '服务式、支持导向', 'value' => 'S'],
            ['text' => '指导式、质量导向', 'value' => 'C'],
        ]],
        ['id' => 10, 'text' => '面对新任务，我：', 'options' => [
            ['text' => '立刻行动、抢占先机', 'value' => 'D'],
            ['text' => '召集伙伴、一起推进', 'value' => 'I'],
            ['text' => '了解全貌、稳妥推进', 'value' => 'S'],
            ['text' => '研究要求、制定计划', 'value' => 'C'],
        ]],
        ['id' => 11, 'text' => '我处理细节的方式：', 'options' => [
            ['text' => '抓大放小、关注结果', 'value' => 'D'],
            ['text' => '凭直觉、跳跃处理', 'value' => 'I'],
            ['text' => '认真对待、不遗漏', 'value' => 'S'],
            ['text' => '严格核对、追求完美', 'value' => 'C'],
        ]],
        ['id' => 12, 'text' => '面对规则，我：', 'options' => [
            ['text' => '必要时打破规则', 'value' => 'D'],
            ['text' => '灵活变通、寻找乐趣', 'value' => 'I'],
            ['text' => '遵守规则、维护秩序', 'value' => 'S'],
            ['text' => '研究规则、确保合规', 'value' => 'C'],
        ]],
        ['id' => 13, 'text' => '我的时间管理：', 'options' => [
            ['text' => '高效、目标明确', 'value' => 'D'],
            ['text' => '灵活、随性', 'value' => 'I'],
            ['text' => '稳定、按计划', 'value' => 'S'],
            ['text' => '精确、有日程表', 'value' => 'C'],
        ]],
        ['id' => 14, 'text' => '面对失败，我：', 'options' => [
            ['text' => '迅速调整、再战', 'value' => 'D'],
            ['text' => '乐观面对、寻找新机会', 'value' => 'I'],
            ['text' => '默默承受、继续努力', 'value' => 'S'],
            ['text' => '复盘分析、避免再犯', 'value' => 'C'],
        ]],
        ['id' => 15, 'text' => '在社交场合，我：', 'options' => [
            ['text' => '主导话题、掌控节奏', 'value' => 'D'],
            ['text' => '成为焦点、活跃气氛', 'value' => 'I'],
            ['text' => '倾听为主、适时发言', 'value' => 'S'],
            ['text' => '选择性交流、深度对话', 'value' => 'C'],
        ]],
        ['id' => 16, 'text' => '我对待承诺：', 'options' => [
            ['text' => '言出必行、追求结果', 'value' => 'D'],
            ['text' => '热情答应、尽力兑现', 'value' => 'I'],
            ['text' => '谨慎承诺、一定兑现', 'value' => 'S'],
            ['text' => '评估能力、确保兑现', 'value' => 'C'],
        ]],
        ['id' => 17, 'text' => '面对批评，我：', 'options' => [
            ['text' => '据理力争、维护立场', 'value' => 'D'],
            ['text' => '可能受伤、需要鼓励', 'value' => 'I'],
            ['text' => '默默接受、自我调整', 'value' => 'S'],
            ['text' => '分析合理性、改进', 'value' => 'C'],
        ]],
        ['id' => 18, 'text' => '我的学习方式：', 'options' => [
            ['text' => '实践为主、快速试错', 'value' => 'D'],
            ['text' => '讨论交流、互动学习', 'value' => 'I'],
            ['text' => '循序渐进、扎实掌握', 'value' => 'S'],
            ['text' => '研究理论、系统学习', 'value' => 'C'],
        ]],
        ['id' => 19, 'text' => '我对待风险：', 'options' => [
            ['text' => '敢于冒险、追求高回报', 'value' => 'D'],
            ['text' => '乐观面对、相信运气', 'value' => 'I'],
            ['text' => '回避风险、求稳', 'value' => 'S'],
            ['text' => '评估风险、做好预案', 'value' => 'C'],
        ]],
        ['id' => 20, 'text' => '面对复杂问题，我：', 'options' => [
            ['text' => '快速决断、边做边调', 'value' => 'D'],
            ['text' => '集思广益、寻求支持', 'value' => 'I'],
            ['text' => '耐心拆解、稳步推进', 'value' => 'S'],
            ['text' => '深入分析、找到根因', 'value' => 'C'],
        ]],
        ['id' => 21, 'text' => '我的工作动力来自：', 'options' => [
            ['text' => '挑战和成就感', 'value' => 'D'],
            ['text' => '认可和社交', 'value' => 'I'],
            ['text' => '稳定和团队归属', 'value' => 'S'],
            ['text' => '专业和品质', 'value' => 'C'],
        ]],
        ['id' => 22, 'text' => '我对待细节：', 'options' => [
            ['text' => '抓重点、不纠结', 'value' => 'D'],
            ['text' => '凭感觉、看心情', 'value' => 'I'],
            ['text' => '认真对待、不马虎', 'value' => 'S'],
            ['text' => '精益求精、追求完美', 'value' => 'C'],
        ]],
        ['id' => 23, 'text' => '面对新环境，我：', 'options' => [
            ['text' => '迅速融入、抢占位置', 'value' => 'D'],
            ['text' => '热情社交、广交朋友', 'value' => 'I'],
            ['text' => '观察适应、慢慢融入', 'value' => 'S'],
            ['text' => '了解规则、做好准备', 'value' => 'C'],
        ]],
        ['id' => 24, 'text' => '我的决策速度：', 'options' => [
            ['text' => '快速、果断', 'value' => 'D'],
            ['text' => '较快、凭直觉', 'value' => 'I'],
            ['text' => '较慢、求稳妥', 'value' => 'S'],
            ['text' => '慢、需充分信息', 'value' => 'C'],
        ]],
    ],
    // 主要类型说明
    'type_codes' => [
        'D' => ['label' => '指挥者', 'desc' => '目标导向、果断、有魄力，善于推动事情发生'],
        'I' => ['label' => '影响者', 'desc' => '热情、善于沟通、有感染力，善于激励团队'],
        'S' => ['label' => '支持者', 'desc' => '稳定、耐心、可靠，是团队的稳定器'],
        'C' => ['label' => '思考者', 'desc' => '严谨、精确、有标准，追求质量和准确'],
        'DI' => ['label' => '开拓者', 'desc' => '既有决断力又有感染力，适合创业和销售'],
        'DC' => ['label' => '执行者', 'desc' => '既有目标感又有严谨度，适合管理和工程'],
        'IS' => ['label' => '协调者', 'desc' => '既热情又稳定，适合人力资源和服务'],
        'SC' => ['label' => '规划者', 'desc' => '既稳定又严谨，适合财务和运营'],
    ],
];
