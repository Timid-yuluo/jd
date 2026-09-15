<?php

declare(strict_types=1);

/**
 * 霍兰德 RIASEC 30题版题库配置
 *
 * 六维度：
 * - R 现实型 Realistic
 * - I 研究型 Investigative
 * - A 艺术型 Artistic
 * - S 社会型 Social
 * - E 企业型 Enterprising
 * - C 常规型 Conventional
 *
 * @return array{
 *   title: string,
 *   description: string,
 *   estimated_minutes: int,
 *   dimensions: array<string, array{name: string, code: string}>,
 *   questions: array<int, array{id: int, type: string, text: string, options: array<int, array{text: string, value: int}>}>|array<int, array{id: int, dimension: string, text: string, score: int}>
 * }
 */
return [
    'title' => '霍兰德 RIASEC 30题版',
    'description' => '通过评估你对不同职业活动的兴趣，识别你的职业倾向类型。',
    'estimated_minutes' => 6,
    'dimensions' => [
        'R' => ['name' => '现实型 Realistic', 'desc' => '喜欢动手操作、与物打交道'],
        'I' => ['name' => '研究型 Investigative', 'desc' => '喜欢思考、探索和研究'],
        'A' => ['name' => '艺术型 Artistic', 'desc' => '喜欢创造、表达和设计'],
        'S' => ['name' => '社会型 Social', 'desc' => '喜欢帮助、教育和与人交往'],
        'E' => ['name' => '企业型 Enterprising', 'desc' => '喜欢领导、说服和管理'],
        'C' => ['name' => '常规型 Conventional', 'desc' => '喜欢组织、整理和处理数据'],
    ],
    // 题目格式：每题对应一个维度，用户选择"喜欢/不喜欢"
    // score: 1=不喜欢, 2=一般, 3=喜欢
    'questions' => [
        // R 现实型（5题）
        ['id' => 1, 'dimension' => 'R', 'text' => '修理电器或机械设备', 'score' => 1],
        ['id' => 2, 'dimension' => 'R', 'text' => '组装家具或搭建物品', 'score' => 1],
        ['id' => 3, 'dimension' => 'R', 'text' => '操作车辆、机械或工具', 'score' => 1],
        ['id' => 4, 'dimension' => 'R', 'text' => '户外作业、种植或养殖', 'score' => 1],
        ['id' => 5, 'dimension' => 'R', 'text' => '学习技术操作和工艺', 'score' => 1],
        // I 研究型（5题）
        ['id' => 6, 'dimension' => 'I', 'text' => '研究科学问题或现象', 'score' => 1],
        ['id' => 7, 'dimension' => 'I', 'text' => '分析数据、探索规律', 'score' => 1],
        ['id' => 8, 'dimension' => 'I', 'text' => '阅读学术文章或专业书籍', 'score' => 1],
        ['id' => 9, 'dimension' => 'I', 'text' => '做实验、验证假设', 'score' => 1],
        ['id' => 10, 'dimension' => 'I', 'text' => '解决复杂的逻辑难题', 'score' => 1],
        // A 艺术型（5题）
        ['id' => 11, 'dimension' => 'A', 'text' => '绘画、写作或音乐创作', 'score' => 1],
        ['id' => 12, 'dimension' => 'A', 'text' => '设计视觉作品或产品外观', 'score' => 1],
        ['id' => 13, 'dimension' => 'A', 'text' => '表演、演讲或主持', 'score' => 1],
        ['id' => 14, 'dimension' => 'A', 'text' => '布置空间、搭配色彩', 'score' => 1],
        ['id' => 15, 'dimension' => 'A', 'text' => '欣赏和评论艺术作品', 'score' => 1],
        // S 社会型（5题）
        ['id' => 16, 'dimension' => 'S', 'text' => '教导他人学习新知识', 'score' => 1],
        ['id' => 17, 'dimension' => 'S', 'text' => '帮助他人解决心理或生活问题', 'score' => 1],
        ['id' => 18, 'dimension' => 'S', 'text' => '组织社区或公益活动', 'score' => 1],
        ['id' => 19, 'dimension' => 'S', 'text' => '照顾老人、儿童或病人', 'score' => 1],
        ['id' => 20, 'dimension' => 'S', 'text' => '调解人际冲突', 'score' => 1],
        // E 企业型（5题）
        ['id' => 21, 'dimension' => 'E', 'text' => '领导团队完成项目', 'score' => 1],
        ['id' => 22, 'dimension' => 'E', 'text' => '说服他人接受你的观点', 'score' => 1],
        ['id' => 23, 'dimension' => 'E', 'text' => '策划商业活动或营销方案', 'score' => 1],
        ['id' => 24, 'dimension' => 'E', 'text' => '管理预算和资源', 'score' => 1],
        ['id' => 25, 'dimension' => 'E', 'text' => '谈判、签订合同', 'score' => 1],
        // C 常规型（5题）
        ['id' => 26, 'dimension' => 'C', 'text' => '整理文件、归档资料', 'score' => 1],
        ['id' => 27, 'dimension' => 'C', 'text' => '录入和核对数据', 'score' => 1],
        ['id' => 28, 'dimension' => 'C', 'text' => '制定计划、安排日程', 'score' => 1],
        ['id' => 29, 'dimension' => 'C', 'text' => '审核账目、报表', 'score' => 1],
        ['id' => 30, 'dimension' => 'C', 'text' => '按流程操作、遵守规范', 'score' => 1],
    ],
    // 选项统一：用户对每题选择喜欢程度
    'options' => [
        1 => ['text' => '不喜欢', 'score' => 1],
        2 => ['text' => '一般', 'score' => 2],
        3 => ['text' => '喜欢', 'score' => 3],
    ],
    // 典型职业推荐（按 3 字母代码）
    'careers' => [
        'RIA' => ['机械工程师', '建筑师', '工业设计师'],
        'RIS' => ['体育教练', '康复治疗师', '技术培训师'],
        'RIC' => ['设备维护工程师', '质量检验员', '生产调度员'],
        'RIE' => ['项目经理', '施工队长', '技术主管'],
        'IRA' => ['工业研究员', '产品设计师', '技术艺术家'],
        'IRS' => ['医学研究员', '心理咨询师', '营养师'],
        'IRC' => ['实验室技术员', '数据分析师', '科研助理'],
        'IRE' => ['研发主管', '技术总监', '产品总监'],
        'AIR' => ['工业设计师', '建筑师', '游戏美术'],
        'AIS' => ['作家', '音乐治疗师', '艺术教师'],
        'AIC' => ['平面设计师', '编辑', '动画师'],
        'AIE' => ['广告创意总监', '艺术经纪人', '内容创作者'],
        'SIR' => ['医生', '理疗师', '特殊教育教师'],
        'SIA' => ['心理咨询师', '社会工作者', '教育顾问'],
        'SIC' => ['人力资源专员', '培训师', '客户服务主管'],
        'SIE' => ['公关经理', '社区主任', '公益项目主管'],
        'EIR' => ['工程经理', '运营总监', '技术合伙人'],
        'EIS' => ['销售经理', '教育机构校长', '咨询顾问'],
        'EIC' => ['财务经理', '采购经理', '供应链总监'],
        'EIA' => ['市场总监', '品牌经理', '创意总监'],
        'CIR' => ['质量工程师', '设备管理员', '技术文档工程师'],
        'CIS' => ['行政主管', '人事专员', '教务管理员'],
        'CIA' => ['编辑', '校对', '内容运营'],
        'CIE' => ['会计', '审计', '项目经理助理'],
    ],
];
