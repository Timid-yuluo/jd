# 应届生AI求职全流程助手 — 产品全案 PRD（v2.1）

> **版本**：v2.1  
> **日期**：2026-04-22  
> **技术栈**：Laravel 13 + PHP 8.3 + MySQL 8 + Redis 
> **AI 服务**：DeepSeek V3.2（主力）+ 腾讯混元 Lite（免费备用）  
> **目标用户**：应届毕业生（大专 / 本科 / 研究生）  
> **前端载体**：uni-app 微信小程序（MVP）→ 页面端 H5 / PC（后续迭代）

---

## 一、产品核心定位与市场机会

### 1.1 核心定位

国内首款专为应届生打造的**AI驱动一站式校招全流程助手**，以「简历智能优化」「1v1全真AI面试模拟」为两大核心王牌功能，覆盖从职业规划、岗位匹配、网申、笔试、面试、offer 对比的校招全链路，做应届生能负担、用得上、见效快的求职提效工具。

### 1.2 市场核心机会

| 维度 | 分析 |
|------|------|
| **需求刚性** | 2026 年高校应届毕业生规模超 **1200 万**，超 80% 应届生无系统求职经验，对简历优化、面试模拟付费意愿极强，决策成本低 |
| **市场空白** | 市面产品要么是通用大模型（无校招场景适配），要么是职场人向简历工具（不匹配应届生无全职经验特点），要么是高价人工辅导（客单价上千），**高性价比的 AI 垂直校招产品暂无绝对头部** |
| **周期稳定** | 每年秋招（9-12月）、春招（2-4月）两大固定流量高峰，日常实习需求全年不间断，现金流稳定 |
| **竞品参考** | 超级简历、职脉、BOSS直聘 AI 功能 |

### 1.3 用户画像

| 特征 | 描述 |
|------|------|
| 年龄 | 18–28 岁 |
| 身份 | 大专 / 本科 / 硕博应届毕业生 |
| 核心痛点 | 简历不会写、面试没经验、求职无方向、全流程无指导 |
| 付费能力 | 月卡 29.9 元完全可负担，决策周期短 |
| 使用习惯 | 微信生态为主，移动端优先 |

---

## 二、核心功能模块设计

### （一）王牌核心功能 1：AI 简历智能优化系统

完全贴合应届生校招场景，拒绝通用文案润色，核心解决「校园/实习经历不会转化、JD 匹配度低、ATS 系统过审难、校招简历避坑」四大核心痛点。

#### 1. 多格式简历导入与智能解析

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| Word/PDF/图片上传 | 通过 OCR + 大模型精准识别简历全内容，自动拆分教育背景、实习经历、项目经历、校园经历、技能证书、获奖荣誉等校招专属模块 | MVP |
| 基础问题自动修正 | 自动修正时间线混乱、格式错乱等基础问题 | MVP |
| 空白简历一键生成 | 通过引导式问答（院校、专业、实习/项目/校园经历、意向岗位等），AI 自动生成符合校招规范的完整简历 | Phase 2 |

#### 2. JD 精准匹配深度优化（核心壁垒）

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| 关键词智能提取 | 用户粘贴目标岗位 JD，AI 自动拆解岗位核心硬技能、软素质、招聘偏好、行业黑话，生成岗位需求画像 | MVP |
| 校招专属 STAR 法则重构 | 针对应届生无全职工作经验的特点，将校园经历、课程项目、短期实习、竞赛经历，用 STAR 法则重构，量化成果，植入 JD 核心关键词 | MVP |
| 分赛道定制化优化逻辑 | 预设互联网、国企/央企/选调、金融、制造业、外企、事业单位等 10+ 主流校招赛道，不同赛道匹配专属优化逻辑 | Phase 2 |

**分赛道优化逻辑示例：**

| 赛道 | 优化侧重点 |
|------|-----------|
| 互联网/大厂 | 突出技术栈、项目落地、数据指标、用户增长 |
| 国企/央企/选调 | 突出政治面貌、学生工作、集体荣誉、合规意识 |
| 技术岗 | 突出技术栈、项目落地、算法成果、开源贡献 |
| 运营岗 | 突出数据指标、用户增长、活动落地 |
| 金融/四大 | 突出专业证书、实习经历、数据分析能力 |

#### 3. 简历合规避坑与评分体系

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| 智能避坑检测 | 自动识别应届生简历高频问题：篇幅过长（强制1页纸规范）、无量化成果、虚假夸大、错别字、敏感信息、时间断层、岗位不匹配 | MVP |
| ATS 兼容检测 | 检测简历格式、关键词密度、排版规范，确保能被各大厂 ATS 系统精准识别 | MVP |
| 匹配度量化评分 | 生成简历-岗位匹配度综合评分，拆解学历、经历、技能、关键词等维度得分，展示优化前后分数对比 | MVP |

#### 4. 多版本管理与专属模板

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| 多版本管理 | 针对不同岗位生成多个简历版本，一键切换、云端存储 | MVP |
| 校招专属模板库 | 实习/秋招/春招/国企/外企/技术岗/非技术岗等，支持可视化编辑，一键导出 PDF/Word | Phase 2 |

**AI Prompt 设计（简历优化）：**
```
你是一位资深的职业顾问，专注于帮助应届毕业生优化求职简历。

用户的简历板块内容如下：
{section_type}：{content}

目标岗位 JD：{job_description}

请从以下四个维度给出优化建议：
1. 【表达优化】：改进措辞，使用更专业的动词和量化表达
2. 【关键词补充】：列出该岗位招聘者最关注的 5 个关键词，标注哪些已包含
3. 【STAR 法则重构】：将该段经历用 STAR 法则重写（情境-任务-行动-结果）
4. 【改写示例】：提供一段改写后的参考文本

严格以 JSON 格式输出，字段包括：
{
  "expression_optimization": "...",
  "keyword_analysis": { "required": [...], "matched": [...], "missing": [...] },
  "star_rewrite": "..."
}
```

**AI Prompt 设计（ATS 评分）：**
```
你是一位 ATS（申请跟踪系统）评估专家。请对以下应届生简历进行评分。

简历内容：{resume_content}
目标岗位 JD：{job_description}

请按以下维度打分（每项 0-100）：
1. 关键词匹配度：简历中包含的 JD 关键词比例
2. 经历相关度：实习/项目经历与岗位要求的匹配度
3. 技能覆盖度：技能清单与岗位要求的覆盖度
4. 教育背景匹配：学历、专业、院校与岗位门槛的匹配度
5. 排版规范性：篇幅是否控制在1页、信息是否完整、有无错别字
6. 综合评分：加权平均分

以 JSON 格式输出：
{
  "keyword_score": 0, "experience_score": 0, "skill_score": 0,
  "education_score": 0, "format_score": 0, "total_score": 0,
  "matched_keywords": [...], "missing_keywords": [...],
  "critical_issues": ["..."],  // 可能导致直接淘汰的问题
  "improvement_suggestions": ["..."]
}
```

---

### （二）王牌核心功能 2：1v1 全真 AI 面试模拟系统

完全还原真实校招面试全流程，解决应届生「面试紧张、不会答题、被追问就卡壳、无实战经验」的核心痛点。

#### 1. 全场景校招面试题库定制

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| 细分面试类型 | 通用行为面、专业技能面、半结构化面试（国企/公考）、无领导小组讨论（群面）、压力面、英文面试、即兴演讲、大厂历年真题 | MVP 核心类型 |
| 岗位专属题库 | 按行业、岗位、公司精准匹配题库（腾讯产品岗、华为技术岗、四大审计岗、银行管培生、选调生等） | Phase 2 |
| 高频考点专项题库 | 宝洁八大问、自我介绍、优缺点、职业规划、项目深挖等校招高频必考问题 | MVP |

**面试题类型矩阵：**

| 类型 | 比例 | 示例 |
|------|------|------|
| 自我介绍 / 动机类 | 20% | 「请介绍一下你自己」 |
| 行为面试题（STAR） | 35% | 「描述一次你独立解决问题的经历」 |
| 岗位专业知识 | 30% | 「解释一下 RESTful API 的设计原则」 |
| 情景题 | 15% | 「如果你接手一个烂尾项目，你会怎么做」 |

#### 2. 1v1 全真实时模拟面试（核心壁垒）

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| **文字模式**（MVP） | 文字输入答案，AI 即时点评 | MVP |
| **语音模式** | 调用浏览器录音 + ASR 转文字后 AI 评分 | Phase 2 |
| **视频模式** | WebRTC 实时交互 + ASR + 人脸表情分析，检测语速、眼神、肢体动作、紧张程度 | Phase 3 |
| 动态多轮追问 | AI 面试官基于用户回答模拟真实追问逻辑，深度深挖 | MVP |
| 全流程还原 | 从开场白、自我介绍、核心问答、反问环节、结束语全流程覆盖 | MVP |

**动态追问 Prompt 设计：**
```
你是一位经验丰富的 {position} 面试官，正在进行一场模拟面试。

候选人简历摘要：{resume_summary}
当前面试题目：{current_question}
候选人上一次回答：{previous_answer}
面试已进行轮次：{round}

请根据候选人上一轮回答，决定下一步行动：
1. 如果回答质量高：进入下一题，生成一道新的面试题（类型需与上一题不同）
2. 如果回答有明显不足：进行追问深挖，要求候选人补充细节
3. 如果回答有亮点：先给予肯定，然后追问相关深度问题

输出 JSON：
{
  "action": "next_question" | "follow_up",
  "message": "面试官过渡话术",
  "question": "新题目或追问题目",
  "q_type": "自我介绍|行为题|专业知识|情景题"
}
```

#### 3. 全维度专业点评与复盘优化

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| 面试报告生成 | 整体评分、岗位匹配度、回答流畅度、逻辑清晰度、表达感染力等多维度评分 | MVP |
| 逐题精细化优化 | 逐句修改建议、高分标准答案参考、高频追问应答话术 | MVP |
| 错题本与专项训练 | 自动记录薄弱题型与知识点，推送专项练习题与解题框架 | Phase 2 |

**AI Prompt 设计（面试答题评分）：**
```
你是一位严格但公正的面试官，正在评估应届生的面试表现。

面试题目：{question}
候选人回答：{answer}
目标岗位：{position}
岗位核心要求：{position_requirements}

请从以下维度打分（满分 10 分）并给出点评：
- 完整性：是否回答了问题的所有要点
- 逻辑性：表达是否清晰有条理
- 专业性：答案是否符合岗位要求
- 亮点提炼：该回答中最值得保留的部分
- 改进建议：如何让这个回答更出色
- STAR 法则应用：行为题是否运用了 STAR 法则（如适用）

以 JSON 格式输出：
{
  "scores": { "completeness": 0, "logic": 0, "professionalism": 0, "overall": 0 },
  "highlights": ["..."],
  "improvements": ["..."],
  "star_analysis": "...",
  "reference_answer": "..."
}
```

#### 4. 校招面试专项突击训练

| 子功能 | 说明 | 优先级 |
|--------|------|--------|
| 自我介绍定制 | 根据用户背景与目标岗位，生成 30 秒 / 1 分钟 / 3 分钟三个版本 | Phase 2 |
| 专项能力训练 | 英文面试口语、即兴演讲、群面话术、薪资谈判技巧、反问环节话术 | Phase 2 |
| 公司专属面试攻略 | 内置各大厂、国企、外企校招面试流程、偏好、避坑指南 | Phase 3 |

---

### （三）校招全流程辅助功能（提升留存与生命周期价值）

| 模块 | 核心功能 | 优先级 |
|------|----------|--------|
| **求职进度看板** | Kanban 可视化跟踪投递状态（待投递→已投递→简历筛选→笔试→面试中→等待 Offer→Offer 已收→已拒绝），跟进提醒，数据统计 | MVP |
| **岗位匹配推荐** | 粘贴 JD，AI 分析技能匹配度 + 投递建议 + 关键词提取同步到简历优化 | MVP |
| **职业规划测评** | 基于用户专业/学历/实习经历/性格兴趣，生成职业测评报告，推荐适配行业与岗位 | Phase 2 |
| **Offer 对比** | 多维度对比多个 Offer（薪资/福利/城市/行业/发展空间），AI 给出决策建议 | Phase 2 |
| **校招知识库** | 覆盖三方协议、违约金、背调、劳动法、网申避坑等，7×24 AI 答疑 | Phase 2 |
| **笔试刷题** | 行测、申论、专业知识笔试题库，AI 自动批改 + 错题解析 | Phase 3 |
| **校招信息推送** | 实时同步校招/实习/内推信息，AI 精准推送匹配岗位 + 网申截止提醒 | Phase 3 |

**匹配度分析 Prompt：**
```
对比以下简历摘要与招聘 JD，给出匹配度分析：

简历摘要：{resume_summary}
招聘 JD：{job_description}

请输出 JSON：
{
  "total_score": 0-100,
  "dimension_scores": {
    "education": 0-100, "experience": 0-100,
    "skills": 0-100, "keywords": 0-100
  },
  "matched_skills": ["..."],
  "missing_skills": [{ "skill": "...", "importance": "高|中|低", "learning_suggestion": "..." }],
  "recommendation": "强烈推荐|建议|不推荐",
  "reason": "...",
  "resume_adjustments": ["..."]
}
```

---

## 三、技术实现方案

### 3.1 整体技术架构

```
┌──────────────────────────────────────────────────────────┐
│                       前端层                              │
│   uni-app 微信小程序（优先）→ 后续迭代 H5 / PC 页面端      │
└──────────────────────┬───────────────────────────────────┘
                       │ HTTPS / WebSocket
┌──────────────────────▼───────────────────────────────────┐
│                   Laravel 13 后端                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │ 用户模块 │ │ 简历模块 │ │ 面试模块 │ │ 岗位模块 │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │ 看板模块 │ │ 支付模块 │ │ 通知模块 │ │ AI服务层 │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
└──────────────────────┬───────────────────────────────────┘
                       │
      ┌────────────────┼────────────────┐
      ▼                ▼                ▼
 ┌─────────┐    ┌──────────┐    ┌──────────┐
 │ MySQL 8 │    │  Redis   │    │  OSS 存储  │
 │ (主库)   │    │(缓存/队列)│    │(简历/音视频)│
 └─────────┘    └──────────┘    └──────────┘
                      │
               ┌──────▼──────┐
               │  AI 能力层   │
               │ DeepSeek V3 │
               │ + 混元 Lite │
               └─────────────┘
```

### 3.2 关键技术选型

| 层级 | 技术 | 选型 | 说明 |
|------|------|------|------|
| **后端框架** | PHP | **Laravel 13** | 长期可维护、生态成熟，队列/事件/广播等能力完善，适合团队化开发 |
| **PHP 版本** | PHP | **8.3+** | 与 Laravel 13 保持版本兼容，具备更好的性能与语言特性 |
| **数据库** | RDBMS | **MySQL 8.0** | JSON 字段支持、窗口函数，满足需求 |
| **缓存/队列** | — | **Redis** | AI 响应缓存、Laravel Queue 驱动、限流 |
| **文件存储** | — | **阿里云 OSS / 腾讯云 COS** | 简历 PDF、音视频文件存储 |
| **前端 MVP** | 小程序 | **uni-app（Vue3 + TypeScript）** | 优先交付微信小程序，快速验证核心功能闭环 |
| **前端迭代** | 页面端 | **H5 / PC 页面端（后续）** | 在小程序稳定后扩展页面端，复用 API 与业务能力 |
| **PDF 生成** | — | **barryvdh/laravel-dompdf** | 简历 PDF 导出 |
| **实时通信** | — | **Laravel Reverb / Soketi** | 流式 AI 响应推送（SSE/WebSocket） |
| **AI 主力** | — | **DeepSeek V3.2** | ¥2/¥3 百万 tokens，性价比最高，兼容 OpenAI 格式 |
| **AI 备用** | — | **腾讯混元 Lite** | 完全免费，降级备份 + 开发测试 |
| **部署** | — | **阿里云/腾讯云** | Serverless 架构，弹性扩容，降低前期运维成本 |

> **注意**：后端统一采用 **Laravel 13**；前端阶段策略为「先 uni-app 微信小程序，再扩展 H5 / PC 页面端」。Laravel 的 Queue + Redis + HTTP Client 可满足 AI 异步调用与削峰需求。

### 3.3 Laravel 项目目录结构

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php
│   │   ├── ResumeController.php          # 简历管理
│   │   ├── InterviewController.php       # 面试模拟
│   │   ├── JobMatchController.php        # 岗位匹配
│   │   ├── KanbanController.php          # 求职看板
│   │   ├── SubscriptionController.php    # 会员订阅
│   │   └── UserProfileController.php     # 用户档案
│   ├── Middleware/
│   │   ├── RateLimiter.php               # AI 调用限流
│   │   └── EnsureSubscription.php        # 会员权益校验
│   ├── Requests/
│   │   ├── ResumeRequest.php
│   │   ├── InterviewRequest.php
│   │   └── JobAnalyzeRequest.php
│   └── Resources/
│       ├── ResumeResource.php
│       ├── InterviewResource.php
│       └── KanbanResource.php
├── Services/
│   └── AI/
│       ├── AIProviderInterface.php       # AI 服务抽象接口
│       ├── DeepSeekService.php           # DeepSeek 实现
│       ├── HunyuanService.php            # 混元实现
│       ├── ResumeAnalysisService.php     # 简历分析（ATS评分+优化建议）
│       ├── InterviewService.php          # 面试出题+评分+追问
│       └── JobMatchService.php           # 岗位匹配分析
├── Models/
│   ├── User.php
│   ├── Resume.php
│   ├── InterviewSession.php
│   ├── InterviewQuestion.php
│   ├── JobApplication.php
│   ├── Subscription.php
│   └── UsageLog.php                      # AI 调用用量记录
├── Jobs/
│   ├── ProcessAIRequest.php              # 异步 AI 处理
│   └── GenerateInterviewReport.php       # 异步生成面试报告
├── Events/
│   ├── InterviewAnswered.php
│   └── ResumeOptimized.php
└── Listeners/
    └── UpdateUsageStats.php
```

---

## 四、数据库完整设计

### 4.1 ER 关系图

```
users 1──N resumes
users 1──N interview_sessions
users 1──N job_applications
users 1──N subscriptions
users 1──N usage_logs
interview_sessions 1──N interview_questions
resumes (JSON content → 不单独建子表)
```

### 4.2 核心表结构

```sql
-- ============================================================
-- 用户表
-- ============================================================
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50)  NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    phone           VARCHAR(20)  UNIQUE,
    password        VARCHAR(255) NOT NULL,
    wechat_openid   VARCHAR(100) UNIQUE,          -- 微信小程序 OpenID
    wechat_unionid  VARCHAR(100) UNIQUE,          -- 微信 UnionID
    github_id       VARCHAR(50)  UNIQUE,
    
    -- 校招档案信息
    school          VARCHAR(100),
    major           VARCHAR(100),
    degree          ENUM('大专','本科','硕士','博士') DEFAULT '本科',
    grad_year       YEAR,
    political_status ENUM('中共党员','共青团员','群众','其他'),  -- 国企赛道需要
    target_jobs     JSON,                         -- ["后端开发","产品经理"]
    target_cities   JSON,                         -- ["北京","上海","深圳"]
    target_industries JSON,                       -- ["互联网","金融"]
    
    -- 会员状态
    subscription_status ENUM('free','monthly','quarterly','annual') DEFAULT 'free',
    subscription_expires_at TIMESTAMP NULL,
    
    avatar          VARCHAR(255),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_school (school),
    INDEX idx_grad_year (grad_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 简历表
-- ============================================================
CREATE TABLE resumes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(100) NOT NULL COMMENT '简历标题，如"互联网后端方向"',
    target_pos      VARCHAR(100) COMMENT '目标岗位',
    track_type      ENUM('互联网','国企央企','金融','制造业','外企','事业单位','其他') DEFAULT '互联网',
    
    -- 简历内容（JSON 结构化存储）
    content         JSON NOT NULL COMMENT '结构化简历内容',
    /*
    content JSON 结构示例：
    {
      "education": [
        {
          "school": "XX大学", "major": "计算机科学", "degree": "本科",
          "start": "2022-09", "end": "2026-06", "gpa": "3.8/4.0"
        }
      ],
      "internships": [
        {
          "company": "XX科技", "position": "后端实习", "start": "2025-06", "end": "2025-09",
          "description": "..."
        }
      ],
      "projects": [...],
      "campus_activities": [...],
      "skills": ["PHP", "Laravel", "MySQL", "Redis"],
      "certifications": ["CET-6"],
      "honors": ["校级一等奖学金"],
      "self_evaluation": "..."
    }
    */
    
    -- AI 优化相关
    ats_score       TINYINT UNSIGNED COMMENT 'ATS 评分 0-100',
    ats_report      JSON COMMENT 'ATS 详细评分报告',
    ai_suggestions  JSON COMMENT 'AI 优化建议（按板块缓存）',
    last_optimized_at TIMESTAMP NULL,
    
    -- 版本与文件
    version         TINYINT DEFAULT 1,
    is_primary      BOOLEAN DEFAULT FALSE,
    pdf_path        VARCHAR(500),
    template_id     VARCHAR(50) DEFAULT 'default',
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_primary (user_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 面试会话表
-- ============================================================
CREATE TABLE interview_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    resume_id       BIGINT UNSIGNED,
    
    -- 面试配置
    position        VARCHAR(100) NOT NULL COMMENT '面试岗位',
    round_type      ENUM('hr','tech','综合','群面','压力面','英文面','终面') DEFAULT 'hr',
    interview_mode  ENUM('文字','语音','视频') DEFAULT '文字',
    company_name    VARCHAR(100) COMMENT '目标公司（可选）',
    
    -- 会话状态
    status          ENUM('进行中','已完成','已放弃') DEFAULT '进行中',
    current_round   TINYINT DEFAULT 1 COMMENT '当前第几题',
    total_questions TINYINT DEFAULT 10 COMMENT '计划题目数',
    
    -- 评分与报告
    total_score     DECIMAL(4,1) COMMENT '综合评分（0-10）',
    dimension_scores JSON COMMENT '各维度评分详情',
    summary         TEXT COMMENT 'AI 总结报告',
    strengths       JSON COMMENT '优势分析',
    weaknesses      JSON COMMENT '不足分析',
    
    started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at        TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_user_date (user_id, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 面试题目表
-- ============================================================
CREATE TABLE interview_questions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      BIGINT UNSIGNED NOT NULL,
    
    -- 题目信息
    q_order         TINYINT UNSIGNED NOT NULL COMMENT '第几题',
    q_type          ENUM('自我介绍','行为题','专业知识','情景题','动机题','英文题','其他') NOT NULL,
    question        TEXT NOT NULL,
    is_follow_up    BOOLEAN DEFAULT FALSE COMMENT '是否为追问',
    parent_q_id     BIGINT UNSIGNED NULL COMMENT '追问的原题 ID',
    
    -- 用户回答与 AI 反馈
    user_answer     TEXT,
    ai_feedback     JSON COMMENT 'AI 点评反馈',
    /*
    ai_feedback JSON 结构：
    {
      "scores": { "completeness": 8, "logic": 7, "professionalism": 6, "overall": 7 },
      "highlights": ["使用了具体数据量化成果"],
      "improvements": ["建议增加项目难度描述", "可用STAR法则重构"],
      "reference_answer": "..."
    }
    */
    
    -- 用于推荐的题目
    score           TINYINT UNSIGNED COMMENT '单题得分 0-10',
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES interview_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_q_id) REFERENCES interview_questions(id) ON DELETE SET NULL,
    INDEX idx_session_order (session_id, q_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 求职投递看板表
-- ============================================================
CREATE TABLE job_applications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    
    -- 投递信息
    company         VARCHAR(100) NOT NULL,
    position        VARCHAR(100) NOT NULL,
    channel         VARCHAR(50) COMMENT 'BOSS直聘/内推/官网/校招宣讲会',
    salary_range    VARCHAR(50) COMMENT '薪资范围',
    city            VARCHAR(50),
    
    -- JD 与匹配
    jd_content      TEXT COMMENT '职位描述原文',
    jd_keywords     JSON COMMENT 'AI 提取的关键词',
    match_score     TINYINT UNSIGNED COMMENT 'AI 匹配度评分 0-100',
    match_report    JSON COMMENT '匹配度详细分析',
    
    -- 投递状态流转
    status          ENUM(
                        '待投递','已投递','简历筛选','笔试',
                        '一面','二面','三面','HR面',
                        '等待Offer','Offer已收','已接受','已拒绝','已放弃'
                    ) DEFAULT '待投递',
    applied_at      DATE,
    last_update_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    next_follow_at  DATE COMMENT '跟进提醒日期',
    result_notes    TEXT COMMENT '最终结果备注',
    
    notes           TEXT COMMENT '用户备注',
    priority        ENUM('高','中','低') DEFAULT '中',
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_follow_date (user_id, next_follow_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 会员订阅表
-- ============================================================
CREATE TABLE subscriptions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    plan_type       ENUM('monthly','quarterly','annual') NOT NULL,
    
    -- 订阅状态
    status          ENUM('active','expired','cancelled','refunded') DEFAULT 'active',
    started_at      TIMESTAMP NOT NULL,
    expires_at      TIMESTAMP NOT NULL,
    
    -- 支付信息
    payment_method  ENUM('wechat','alipay') NOT NULL,
    transaction_id  VARCHAR(100) COMMENT '第三方支付交易号',
    amount          DECIMAL(6,2) NOT NULL COMMENT '实付金额',
    
    -- 权益记录
    features        JSON COMMENT '解锁的功能列表',
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AI 调用用量记录表（计费与限流）
-- ============================================================
CREATE TABLE usage_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    
    -- 调用信息
    service_type    ENUM('resume_optimize','ats_score','interview_generate','interview_score','job_match','career_plan') NOT NULL,
    ai_provider     ENUM('deepseek','hunyuan') NOT NULL,
    model           VARCHAR(50) NOT NULL COMMENT 'deepseek-chat / deepseek-reasoner / hunyuan-lite',
    
    -- 用量统计
    input_tokens    INT UNSIGNED NOT NULL,
    output_tokens   INT UNSIGNED NOT NULL,
    latency_ms      INT UNSIGNED COMMENT '响应耗时',
    
    -- 结果缓存（可选）
    request_hash    VARCHAR(64) COMMENT '请求内容哈希，用于缓存判断',
    response_cache  JSON COMMENT 'AI 响应内容缓存',
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_service_type (service_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 五、API 接口文档

### 5.1 认证方式

所有 API 使用 **Sanctum Token** 认证：

```
Header: Authorization: Bearer {token}
```

### 5.2 通用响应格式

```json
{
  "code": 0,
  "message": "success",
  "data": { ... }
}
```

### 5.3 用户模块

```
POST   /api/auth/register          # 注册
POST   /api/auth/login             # 登录
POST   /api/v1/auth/refresh         # 刷新令牌（微信登录已下线）
GET    /api/user/profile           # 获取用户档案
PUT    /api/user/profile           # 更新用户档案
```

### 5.4 简历模块

```
POST   /api/resumes                          # 创建空白简历
POST   /api/resumes/import                   # 上传文件导入简历（OCR+解析）
GET    /api/resumes                          # 简历列表
GET    /api/resumes/{id}                     # 简历详情
PUT    /api/resumes/{id}                     # 更新简历内容
DELETE /api/resumes/{id}                     # 删除简历
POST   /api/resumes/{id}/optimize            # AI 优化指定板块
POST   /api/resumes/{id}/ats-score           # 生成 ATS 评分
GET    /api/resumes/{id}/export-pdf          # 导出 PDF
PUT    /api/resumes/{id}/set-primary         # 设为主简历
```

### 5.5 面试模块

```
POST   /api/interviews                          # 创建面试会话（AI 出题）
GET    /api/interviews                          # 面试历史列表
GET    /api/interviews/{id}                     # 会话详情
GET    /api/interviews/{id}/questions           # 获取所有题目
GET    /api/interviews/{id}/current-question    # 获取当前题目
POST   /api/interviews/{id}/submit-answer       # 提交答案 → AI 点评 + 追问/下一题
POST   /api/interviews/{id}/finish              # 结束面试 → 生成总结报告
GET    /api/interviews/{id}/report              # 获取面试报告
```

### 5.6 岗位匹配模块

```
POST   /api/jobs/analyze                # 分析 JD 匹配度（粘贴 JD）
GET    /api/jobs                        # 获取已分析岗位列表
POST   /api/jobs                        # 手动添加岗位
DELETE /api/jobs/{id}                   # 删除岗位
```

### 5.7 求职看板模块

```
GET    /api/kanban                      # 获取看板数据（按状态分组）
POST   /api/kanban                      # 添加投递记录
PUT    /api/kanban/{id}                 # 更新投递信息
PATCH  /api/kanban/{id}/status          # 更新状态
DELETE /api/kanban/{id}                 # 删除记录
GET    /api/kanban/reminders            # 获取跟进提醒列表
GET    /api/kanban/statistics           # 数据统计（投递数/回复率/转化率）
```

### 5.8 会员订阅模块

```
GET    /api/subscription/plans          # 获取套餐列表
POST   /api/subscription/subscribe      # 创建订阅订单
POST   /api/subscription/wechat/pay     # 微信支付
POST   /api/subscription/notify         # 支付回调（微信）
GET    /api/subscription/status         # 当前订阅状态
GET    /api/subscription/usage          # AI 调用用量统计
```

---

## 六、商业化变现模式

### 6.1 核心变现：免费 + 会员订阅制

| 套餐 | 定价 | 核心权益 | 适配场景 |
|------|------|----------|----------|
| 免费体验 | ¥0 | 1 次简历优化 + 1 次面试模拟 + 基础模板 | 体验转化 |
| **月卡** | **¥29.9** | 无限次简历优化 + 无限次文字面试 + 全部题库 + 基础模板 | 短期实习/春招补录 |
| **季卡** | **¥59.9** | 月卡全部 + 语音面试 + 笔试 AI 辅导 + 专属模板 | 秋招/春招核心周期 |
| **校招通关卡** | **¥99** | 季卡全部 + 视频面试 + 6 个月有效 + Offer 对比 + 专属客服 | 应届生全年校招（转化率最高） |

### 6.2 补充变现

| 模式 | 定价 | 说明 |
|------|------|------|
| 单次简历优化 | ¥9.9/次 | 低频用户降低决策门槛 |
| 单次视频面试模拟 | ¥19.9/次 | 高价值单次体验 |
| 人工简历精修 | ¥99/份 | 高端人工定制服务 |
| 大厂导师面试辅导 | ¥199/小时 | 1v1 人工服务 |
| 校招全程陪跑 | ¥299/人 | 与 AI 产品形成互补 |

### 6.3 延伸变现

- **企业校招合作**：岗位发布费 + 简历推荐费 + 按入职佣金
- **内推资源变现**：聚合大厂内推官，收取小额内推服务费
- **求职课程**：简历写作课 / 大厂面试突击课 / 国企备考课（¥99-299）

---

## 七、获客与增长策略

| 渠道 | 策略 | 预期效果 |
|------|------|----------|
| **高校社群裂变**（核心冷启动） | 联合学生会/就业办/班级群，裂变活动：「邀请 3 好友送 7 天会员」「分享送简历优化」 | 低成本精准种子用户 |
| **内容平台引流** | 小红书/抖音/B站/知乎发布：《简历优化前后对比》《AI 模拟面试真实体验》《秋招避坑指南》 | 高转化精准用户 |
| **校园线下地推** | 双选会/招聘会/宣讲会现场扫码注册送免费会员 + 校招攻略 | 高精准度 |
| **SEO/SEM** | 覆盖「应届生简历优化」「校招面试模拟」等高频搜索词 | 长期精准流量 |
| **口碑传播** | 打造「校招神器」用户心智 | 自然增长 |

---

## 八、MVP 开发路线图

### Phase 1 — 基础骨架（第 1–2 周）

- [ ] Laravel 项目初始化 + 数据库 Migration + Seeder
- [ ] uni-app 小程序工程初始化（Vue3 + TypeScript + Pinia）
- [ ] 用户注册/登录（邮箱 + 微信小程序）
- [ ] DeepSeekService 封装 + 混元 Lite 降级
- [ ] 简历 CRUD + 结构化编辑器
- [ ] 简历多格式导入（OCR 解析 MVP）

### Phase 2 — 核心 AI 功能（第 3–4 周）

- [ ] 简历 AI 优化建议（JD 匹配 + STAR 法则重构）
- [ ] ATS 评分功能
- [ ] 面试会话创建 + AI 出题
- [ ] 面试答题 + AI 即时点评
- [ ] 动态追问逻辑

### Phase 3 — 完整体验（第 5–6 周）

- [ ] 面试总结报告生成
- [ ] JD 匹配度分析
- [ ] 求职看板（Kanban）
- [ ] PDF 导出
- [ ] 微信支付接入

### Phase 4 — 打磨与商业化（第 7–8 周）

- [ ] 会员订阅体系 + 权益控制
- [ ] 高级模板（付费）
- [ ] 数据统计仪表盘
- [ ] 错题本 + 专项训练
- [ ] 自我介绍定制

### Phase 5 — 增强功能（持续迭代）

- [ ] 语音面试模拟
- [ ] 视频面试模拟（WebRTC + ASR + 表情分析）
- [ ] 笔试刷题系统
- [ ] 校招信息推送
- [ ] Offer 对比
- [ ] 职业规划测评
- [ ] H5 / PC 页面端（后续迭代）

---

## 九、非功能需求

| 维度 | 要求 |
|------|------|
| **性能** | AI 接口响应 < 15s（超时降级），非 AI 接口 < 500ms |
| **安全** | 简历数据加密存储，AI 请求脱敏处理（不传用户真实姓名/手机号） |
| **可用性** | DeepSeek 不可用时自动降级到混元 Lite，不影响非 AI 功能 |
| **扩展性** | AI 服务层抽象为 `AIProviderInterface`，支持切换不同模型 |
| **限流** | 免费用户 AI 调用限流（简历优化 1 次/天，面试 1 次/天），会员不限 |
| **合规** | 严格遵守《个人信息保护法》，用户数据不用于 AI 训练，隐私协议明确声明 |

---

## 十、核心壁垒与风险应对

### 10.1 核心竞争壁垒

| 壁垒 | 说明 |
|------|------|
| **极致的校招垂直适配** | 完全围绕应届生身份特点与校招全流程深度适配，拒绝泛化 |
| **全流程闭环体验** | 职业规划→岗位匹配→简历优化→笔试→面试→Offer 对比一站式覆盖 |
| **高性价比普惠定位** | 对比人工辅导上千元，会员仅需几十元，覆盖最广泛用户群体 |
| **校招专属 AI 能力沉淀** | 持续的 Prompt 优化 + 知识库 + 场景化微调，规避通用大模型幻觉 |

### 10.2 风险与应对

| 风险 | 概率 | 影响 | 应对方案 |
|------|------|------|----------|
| **合规风险** | 低 | 极高 | 严格遵守《个人信息保护法》，加密存储，不承诺「保过/保拿 Offer」 |
| **AI 幻觉风险** | 中 | 高 | RAG 知识库 + Prompt 版本管理 + 人工审核异常输出 |
| **DeepSeek 不稳定** | 中 | 高 | 混元 Lite 降级备份，AI 服务层抽象支持切换 |
| **秋招高峰流量** | 中 | 中 | Serverless 弹性扩容 + Redis 队列削峰 |
| **竞争风险** | 中 | 中 | 不做综合招聘平台，聚焦校招 AI 工具垂直赛道 |
| **商业化节奏** | 低 | 中 | 低客单价会员制先行，避免过度商业化影响体验 |

---

## 十一、成功指标（MVP 阶段）

| 指标 | 目标值 |
|------|--------|
| 注册用户数 | 上线 1 个月 500 人 |
| 简历优化使用率 | 注册用户中 60% |
| 面试模拟完成率 | 开始面试的用户中 70% 完成 |
| 付费转化率 | 免费用户中 ≥ 5% 转为月卡 |
| 用户次日留存率 | ≥ 30% |
| NPS 净推荐值 | ≥ 40 |

---

*文档持续迭代，每次重大功能变更更新版本号。*
