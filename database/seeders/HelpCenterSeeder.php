<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Seeder;

class HelpCenterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => '快速入门',
                'slug' => 'getting-started',
                'icon' => 'ti ti-rocket',
                'description' => '快速了解平台功能与上手流程',
                'sort_order' => 0,
                'is_visible' => true,
            ],
            [
                'name' => '账号与安全',
                'slug' => 'account-security',
                'icon' => 'ti ti-shield-check',
                'description' => '账号注册、登录、安全设置与隐私',
                'sort_order' => 1,
                'is_visible' => true,
            ],
            [
                'name' => '简历制作',
                'slug' => 'resume',
                'icon' => 'ti ti-file-text',
                'description' => '简历创建、编辑、优化与导出',
                'sort_order' => 2,
                'is_visible' => true,
            ],
            [
                'name' => 'AI 模拟面试',
                'slug' => 'interview',
                'icon' => 'ti ti-message-chatbot',
                'description' => 'AI 面试的创建、答题与报告解读',
                'sort_order' => 3,
                'is_visible' => true,
            ],
            [
                'name' => '求职管理',
                'slug' => 'job-hunting',
                'icon' => 'ti ti-briefcase',
                'description' => '岗位分析、投递看板与进度管理',
                'sort_order' => 4,
                'is_visible' => true,
            ],
            [
                'name' => '计费与套餐',
                'slug' => 'billing',
                'icon' => 'ti ti-credit-card',
                'description' => '套餐对比、订阅充值与额度说明',
                'sort_order' => 5,
                'is_visible' => true,
            ],
            [
                'name' => '常见问题',
                'slug' => 'faq',
                'icon' => 'ti ti-help-circle',
                'description' => '用户高频问题解答与故障排查',
                'sort_order' => 6,
                'is_visible' => true,
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[] = HelpCategory::query()->firstOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        $articles = [
            // ============================
            // 快速入门
            // ============================
            [
                'category_slug' => 'getting-started',
                'title' => '平台概览',
                'slug' => 'platform-overview',
                'excerpt' => '了解职路通平台的核心功能与导航结构，快速认识每个模块的作用。',
                'content' =>
                    '<h2>欢迎使用职路通</h2>'
                    .'<p>职路通是一个 <strong>AI 驱动的求职助手平台</strong>，围绕求职全流程提供智能化工具，帮助您高效准备简历、模拟面试、匹配合适岗位并追踪投递进度。</p>'

                    .'<h2>核心功能一览</h2>'
                    .'<ul>'
                    .'  <li><strong>简历编辑器</strong> — 模块化创建和管理简历，支持个人信息、求职意向、教育背景、工作经历、项目经验、技能特长、荣誉证书、自我评价八大模块。</li>'
                    .'  <li><strong>AI 简历优化</strong> — 基于大语言模型对简历内容进行智能润色，支持量化成果、岗位定制、精简降噪、真实性增强等 12 种优化目标，可流式实时预览结果。</li>'
                    .'  <li><strong>ATS 评分</strong> — 对照目标岗位描述，对简历进行 ATS（申请人追踪系统）兼容性分析与分值评估，提前发现简历短板。</li>'
                    .'  <li><strong>简历导入</strong> — 上传 PDF 或 Word 文档，AI 自动解析并填充到简历编辑器中。</li>'
                    .'  <li><strong>简历导出</strong> — 将简历导出为 PDF 或 Word 文档，支持异步导出任务。也支持生成分享链接。</li>'
                    .'  <li><strong>AI 模拟面试</strong> — 基于您的简历和目标岗位，AI 自动生成个性化面试题目。涵盖技术、行为、综合、销售、管理、创意、金融等 17 种面试类型，每道题回答后 AI 实时评分并给出改进建议。</li>'
                    .'  <li><strong>岗位匹配分析</strong> — 上传岗位描述（JD），AI 分析您的简历与岗位的匹配度，输出技能缺口与改进建议。</li>'
                    .'  <li><strong>求职看板</strong> — 以看板形式管理所有投递记录，支持自定义状态流转（如"已投递→初筛→面试→Offer→入职"），一目了然掌握求职全局。</li>'
                    .'  <li><strong>外部招聘池</strong> — 平台自动聚合校招和社招信息，每日更新。</li>'
                    .'</ul>'

                    .'<h2>界面导航</h2>'
                    .'<p>登录后进入用户工作台，左侧边栏为主要功能入口：</p>'
                    .'<ul>'
                    .'  <li><strong>工作台</strong> — 概览页面，展示使用统计和快捷入口。</li>'
                    .'  <li><strong>我的简历</strong> — 简历列表、创建、编辑、优化、导出。子菜单含回收站和简历模板。</li>'
                    .'  <li><strong>模拟面试</strong> — 创建面试、进行答题、查看报告。</li>'
                    .'  <li><strong>岗位分析</strong> — 单条分析和批量分析，匹配度评估。</li>'
                    .'  <li><strong>投递看板</strong> — 求职进度可视化管理。</li>'
                    .'  <li><strong>消息通知</strong> — 系统通知与消息中心。</li>'
                    .'  <li><strong>个人中心</strong> — 个人资料、安全设置、套餐与用量、通知偏好。</li>'
                    .'</ul>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'getting-started',
                'title' => '新用户注册指南',
                'slug' => 'registration-guide',
                'excerpt' => '详细的注册流程说明与注意事项，帮助您快速创建账号。',
                'content' =>
                    '<h2>注册方式</h2>'
                    .'<p>职路通支持以下注册方式：</p>'
                    .'<ul>'
                    .'  <li><strong>邮箱注册</strong> — 使用邮箱地址和密码创建账号。</li>'
                    .'  <li><strong>GitHub 授权登录</strong> — 使用 GitHub 账号一键登录。</li>'
                    .'  <li><strong>支付宝授权登录</strong> — 使用支付宝账号快捷登录。</li>'
                    .'</ul>'

                    .'<h2>邮箱注册流程</h2>'
                    .'<ol>'
                    .'  <li>点击首页右上角的<strong>"注册"</strong>按钮。</li>'
                    .'  <li>填写邮箱地址和密码。</li>'
                    .'  <li>系统会向您的邮箱发送一封验证邮件，点击邮件中的链接完成验证。</li>'
                    .'  <li>验证成功后即可登录并开始使用平台功能。</li>'
                    .'</ol>'

                    .'<h2>密码要求</h2>'
                    .'<ul>'
                    .'  <li>最少 8 位字符。</li>'
                    .'  <li>必须包含大写字母、小写字母、数字和特殊符号。</li>'
                    .'  <li>不能是常见弱密码或已泄露的密码。</li>'
                    .'</ul>'

                    .'<h2>注意事项</h2>'
                    .'<ul>'
                    .'  <li>请使用常用邮箱注册，以便接收重要通知（如面试提醒、套餐到期通知等）。</li>'
                    .'  <li>如果未收到验证邮件，请检查邮箱的<strong>垃圾邮件</strong>文件夹。</li>'
                    .'  <li>注册后建议立即完善个人资料，有助于后续 AI 功能的个性化匹配。</li>'
                    .'</ul>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'getting-started',
                'title' => '工作台使用指南',
                'slug' => 'dashboard-guide',
                'excerpt' => '了解工作台的各项功能和快捷入口，高效管理求职全程。',
                'content' =>
                    '<h2>工作台概览</h2>'
                    .'<p>登录后看到的第一个页面就是您的个人工作台，它是整个平台的<strong>总控面板</strong>，汇集了关键数据和快捷操作入口。</p>'

                    .'<h2>主要区域</h2>'
                    .'<ul>'
                    .'  <li><strong>快速操作区</strong> — 创建简历、新建面试、岗位分析等常用操作的快捷按钮。</li>'
                    .'  <li><strong>数据统计</strong> — 展示您的简历数量、面试次数、投递记录等统计数据。</li>'
                    .'  <li><strong>最近动态</strong> — 最近编辑的简历、最近参加的面试、最新的分析结果等。</li>'
                    .'  <li><strong>套餐用量</strong> — 展示当前套餐的 AI 额度使用情况，帮助您了解剩余可用次数。</li>'
                    .'</ul>'

                    .'<h2>新手引导</h2>'
                    .'<p>首次登录后，工作台会显示新手引导提示，引导您完成：</p>'
                    .'<ol>'
                    .'  <li>创建第一份简历</li>'
                    .'  <li>尝试 AI 优化</li>'
                    .'  <li>进行一次模拟面试</li>'
                    .'</ol>'
                    .'<p>如果您想跳过，可以点击引导卡片上的关闭按钮。</p>',
                'sort_order' => 2,
                'is_published' => true,
            ],

            // ============================
            // 账号与安全
            // ============================
            [
                'category_slug' => 'account-security',
                'title' => '如何修改密码',
                'slug' => 'change-password',
                'excerpt' => '了解修改账号密码的步骤以及密码安全最佳实践。',
                'content' =>
                    '<h2>修改密码步骤</h2>'
                    .'<ol>'
                    .'  <li>登录后进入左侧菜单<strong>"个人中心" → "个人资料"</strong>。</li>'
                    .'  <li>点击"修改密码"区域。</li>'
                    .'  <li>输入当前密码和新密码（需包含大小写字母、数字和特殊符号）。</li>'
                    .'  <li>点击"保存"完成修改。</li>'
                    .'</ol>'

                    .'<h2>忘记密码</h2>'
                    .'<p>在登录页面点击"忘记密码"，输入注册邮箱即可收到重置链接。详见《<a href="/help/faq/forgot-password">忘记密码怎么办？</a>》。</p>'

                    .'<h2>密码安全建议</h2>'
                    .'<ul>'
                    .'  <li>定期更换密码，建议每 90 天更换一次。</li>'
                    .'  <li>不要在多个网站或 APP 使用相同的密码。</li>'
                    .'  <li>强烈建议开启<strong>两步验证（2FA）</strong>以增强安全性。</li>'
                    .'  <li>平台会记录您的密码历史，不允许重复使用近期用过的密码。</li>'
                    .'</ul>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'account-security',
                'title' => '两步验证（2FA）设置指南',
                'slug' => 'two-factor-auth',
                'excerpt' => '通过 Google 身份验证器为账号添加额外安全保护层。',
                'content' =>
                    '<h2>什么是两步验证？</h2>'
                    .'<p>两步验证（2FA）是在密码之外的第二层安全保护。开启后，除密码外还需输入手机认证器 App 生成的 6 位动态验证码才能登录。</p>'

                    .'<h2>开启步骤</h2>'
                    .'<ol>'
                    .'  <li>登录后进入<strong>"个人中心" → "个人资料"</strong>。</li>'
                    .'  <li>找到"两步验证"区域，点击<strong>"设置两步验证"</strong>。</li>'
                    .'  <li>使用手机上的身份验证器 App（如 Google Authenticator、Microsoft Authenticator 或 Authy）扫描弹出的二维码。</li>'
                    .'  <li>输入 App 中显示的 6 位验证码，点击"确认"完成绑定。</li>'
                    .'</ol>'

                    .'<h2>重要提示</h2>'
                    .'<ul>'
                    .'  <li><strong>请务必保存恢复码！</strong>绑定成功后系统会显示一组恢复码，请截图或记录下来并妥善保管。手机丢失或换机时，您将需要通过恢复码来重新访问账号。</li>'
                    .'  <li>建议将恢复码打印出来并存放在安全的地方。</li>'
                    .'  <li>每个恢复码只能使用一次。</li>'
                    .'</ul>'

                    .'<h2>关闭两步验证</h2>'
                    .'<p>如果需要关闭，在"个人中心" → "个人资料"中找到两步验证区域，点击"禁用"，按提示输入密码后确认即可。</p>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'account-security',
                'title' => '绑定第三方账号（GitHub / 支付宝）',
                'slug' => 'oauth-binding',
                'excerpt' => '将 GitHub 或支付宝账号绑定到您的职路通账号，实现快捷登录。',
                'content' =>
                    '<h2>支持的第三方账号</h2>'
                    .'<p>职路通支持绑定以下第三方平台账号用于快捷登录：</p>'
                    .'<ul>'
                    .'  <li><strong>GitHub</strong> — 使用 GitHub 账号登录。</li>'
                    .'  <li><strong>支付宝</strong> — 使用支付宝账号登录。</li>'
                    .'</ul>'

                    .'<h2>绑定步骤</h2>'
                    .'<ol>'
                    .'  <li>登录后进入<strong>"个人中心" → "个人资料"</strong>。</li>'
                    .'  <li>找到"绑定账号"区域。</li>'
                    .'  <li>点击对应平台的"绑定"按钮，跳转到授权页面。</li>'
                    .'  <li>在第三方平台完成授权后，自动返回并完成绑定。</li>'
                    .'</ol>'

                    .'<h2>解绑</h2>'
                    .'<p>在绑定账号区域点击"解绑"即可断开与第三方平台的联系。解绑后不再支持通过该平台登录。</p>'

                    .'<h2>注意事项</h2>'
                    .'<ul>'
                    .'  <li>绑定不需要邮箱一致，您可以使用不同的第三方账号关联。</li>'
                    .'  <li>建议同时绑定一种第三方登录方式，以免忘记密码时无法登录。</li>'
                    .'  <li>解绑操作不可逆，解绑后需要重新授权才能再次绑定。</li>'
                    .'</ul>',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'category_slug' => 'account-security',
                'title' => '账号注销与恢复',
                'slug' => 'account-deletion',
                'excerpt' => '了解账号注销流程、冷静期规定，以及注销后的恢复方法。',
                'content' =>
                    '<h2>注销账号</h2>'
                    .'<ol>'
                    .'  <li>进入<strong>"个人中心" → "个人资料"</strong>。</li>'
                    .'  <li>点击底部的<strong>"注销账号"</strong>按钮。</li>'
                    .'  <li>系统会要求您输入密码以确认身份。</li>'
                    .'  <li>选择注销原因（可选填写反馈），提交申请。</li>'
                    .'</ol>'

                    .'<h2>冷静期机制</h2>'
                    .'<p>提交注销申请后，账号不会立即删除，而是进入<strong>冷静期</strong>。在此期间：</p>'
                    .'<ul>'
                    .'  <li>您的账号将无法登录，所有数据暂存。</li>'
                    .'  <li>冷静期内，您会收到一封包含恢复链接的邮件。</li>'
                    .'  <li>如需撤销注销，点击邮件中的恢复链接即可恢复账号和数据。</li>'
                    .'</ul>'
                    .'<p>冷静期结束后，账号及相关数据将被<strong>永久删除</strong>，无法恢复。</p>'

                    .'<h2>重要提醒</h2>'
                    .'<ul>'
                    .'  <li>注销前请确保已导出或备份重要的简历数据。</li>'
                    .'  <li>已购买的套餐和次卡在注销后不予退还，请谨慎操作。</li>'
                    .'  <li>恢复账号需要在冷静期内（通过恢复邮件中的链接）完成。</li>'
                    .'</ul>',
                'sort_order' => 3,
                'is_published' => true,
            ],
            // ============================
            // 简历制作
            // ============================
            [
                'category_slug' => 'resume',
                'title' => '简历编辑器使用指南',
                'slug' => 'resume-editor-guide',
                'excerpt' => '详解八大简历模块的填写方法，快速创建一份专业简历。',
                'content' =>
                    '<h2>简历编辑器介绍</h2>'
                    .'<p>职路通提供<strong>模块化简历编辑器</strong>，将简历分为八大独立模块，支持拖拽排序和可视化管理，让您灵活组合内容。</p>'

                    .'<h2>八大核心模块</h2>'
                    .'<table>'
                    .'<thead><tr><th style="min-width:100px">模块</th><th>内容说明</th></tr></thead>'
                    .'<tbody>'
                    .'<tr><td><strong>个人信息</strong></td><td>姓名、手机号、邮箱、所在地等基本信息</td></tr>'
                    .'<tr><td><strong>求职意向</strong></td><td>目标岗位名称、期望城市、求职方向简述</td></tr>'
                    .'<tr><td><strong>教育背景</strong></td><td>学校、专业、学位、就读时间、相关课程或荣誉</td></tr>'
                    .'<tr><td><strong>工作经历</strong></td><td>公司名称、职位、工作时间、职责描述、关键成果</td></tr>'
                    .'<tr><td><strong>项目经验</strong></td><td>项目名称、角色、时间、技术栈、项目成果</td></tr>'
                    .'<tr><td><strong>技能特长</strong></td><td>技术技能、语言能力、工具熟练度</td></tr>'
                    .'<tr><td><strong>荣誉证书</strong></td><td>获得的证书、奖项、认证</td></tr>'
                    .'<tr><td><strong>自我评价</strong></td><td>个人优势总结、职业发展目标</td></tr>'
                    .'</tbody>'
                    .'</table>'

                    .'<h2>使用方法</h2>'
                    .'<ol>'
                    .'  <li>在"我的简历"页面点击<strong>"创建简历"</strong>，或点击已有简历进入编辑器。</li>'
                    .'  <li>点击左侧模块列表中的模块，右侧编辑区会出现对应的表单字段。</li>'
                    .'  <li>按提示填写内容，每个模块都提供了示例文字帮助您理解格式。</li>'
                    .'  <li>鼠标悬停在模块上可以拖拽调整排序。</li>'
                    .'  <li>点击右上角"保存"按钮保存修改。</li>'
                    .'</ol>'

                    .'<h2>小技巧</h2>'
                    .'<ul>'
                    .'  <li>建议优先完善"个人信息"和"求职意向"，这两个模块影响 AI 优化的精准度。</li>'
                    .'  <li>工作经历和项目经验建议使用<strong>STAR 原则</strong>（情境-任务-行动-结果）来描述。</li>'
                    .'  <li>简历完成度达到 100% 能获得更好的 AI 优化效果。</li>'
                    .'</ul>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'resume',
                'title' => 'AI 简历优化 — 功能详解',
                'slug' => 'ai-resume-optimize',
                'excerpt' => '了解 12 种 AI 优化目标、如何选择与组合，以及优化结果的应用。',
                'content' =>
                    '<h2>什么是 AI 简历优化？</h2>'
                    .'<p>AI 简历优化利用大语言模型对简历进行智能润色，帮助您提升表达的<strong>专业性、说服力和匹配度</strong>。您可以选择优化目标或用具体的岗位描述来定制优化方向。</p>'

                    .'<h2>12 种优化目标</h2>'
                    .'<table>'
                    .'<thead><tr><th>优化目标</th><th>适用场景</th></tr></thead>'
                    .'<tbody>'
                    .'<tr><td><strong>量化成果</strong></td><td>用具体数据强化工作成果，如"提升效率 30%"</td></tr>'
                    .'<tr><td><strong>岗位定制</strong></td><td>根据目标岗位 JD 调整简历重点和关键词</td></tr>'
                    .'<tr><td><strong>精简降噪</strong></td><td>去掉冗余表达，让内容更紧凑有力</td></tr>'
                    .'<tr><td><strong>亮点提炼</strong></td><td>突出核心成就和差异化优势</td></tr>'
                    .'<tr><td><strong>真实性增强</strong></td><td>确保优化后内容基于原文事实，不编造数据</td></tr>'
                    .'<tr><td><strong>领导力展示</strong></td><td>强化管理经验、团队带领和决策能力</td></tr>'
                    .'<tr><td><strong>跨职能转型</strong></td><td>重新框架化现有经验以适应新职能方向</td></tr>'
                    .'<tr><td><strong>国际化表达</strong></td><td>加入行业英文术语，适配外企风格</td></tr>'
                    .'<tr><td><strong>行业适配</strong></td><td>根据目标行业调整行文风格和用词</td></tr>'
                    .'<tr><td><strong>项目影响力</strong></td><td>突出项目背景、范围和业务价值</td></tr>'
                    .'<tr><td><strong>技术深度</strong></td><td>强化技术选型理由、架构决策和实现细节</td></tr>'
                    .'<tr><td><strong>创新 / 数据驱动 / 资质认证 / 跨文化协作</strong></td><td>针对特定方向的专项优化</td></tr>'
                    .'</tbody>'
                    .'</table>'

                    .'<h2>目标组合与冲突规则</h2>'
                    .'<p>您可以同时勾选多个优化目标，但某些目标存在天然冲突。平台内置了智能冲突解决规则：</p>'
                    .'<ul>'
                    .'  <li><strong>量化成果 + 真实性增强</strong> → 真实性优先，不会虚构数据。原文缺少可验证数据时，仅输出"待补充指标建议"。</li>'
                    .'  <li><strong>精简降噪 + 亮点提炼</strong> → 先保留关键亮点，再压缩冗余表达。</li>'
                    .'  <li><strong>岗位定制 + 真实性增强</strong> → 仅允许重排和重写原有事实，不补写未发生的经历。</li>'
                    .'</ul>'

                    .'<h2>使用方式</h2>'
                    .'<p>在简历编辑器中点击<strong>"AI 优化"</strong>按钮，选择优化目标（可多选），点击开始即可。优化结果以<strong>对比视图</strong>呈现——左栏为原文，右栏为优化后的内容，逐模块对照查看。</p>'

                    .'<h2>流式优化</h2>'
                    .'<p>平台默认使用<strong>流式输出</strong>模式，您可以实时看到 AI 逐字生成优化结果，无需等待完整生成。这大大提升了使用体验，尤其是全简历优化时。</p>'

                    .'<h2>优化对比与采纳</h2>'
                    .'<p>每次优化都会生成一个<strong>优化会话</strong>，包含优化版本记录。您可以在对比视图中逐条采纳优化建议，也可以一键应用全部优化结果。未采纳的会话会保留在历史记录中，随时可以回顾。</p>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'resume',
                'title' => 'ATS 简历评分指南',
                'slug' => 'ats-score',
                'excerpt' => '了解 ATS 评分的作用、使用方法和评分解读。',
                'content' =>
                    '<h2>什么是 ATS 评分？</h2>'
                    .'<p>ATS（Applicant Tracking System，申请人追踪系统）是现代企业招聘中用于<strong>自动筛选简历</strong>的系统。许多大公司在人工查看简历前，会先用 ATS 系统进行初筛。ATS 评分模拟了这一过程，帮助您了解简历在机器筛选中是否具备竞争力。</p>'

                    .'<h2>如何使用</h2>'
                    .'<ol>'
                    .'  <li>打开需要评分的简历。</li>'
                    .'  <li>输入目标岗位名称和岗位描述（JD），越详细评分越准确。</li>'
                    .'  <li>点击<strong>"ATS 评分"</strong>按钮。</li>'
                    .'  <li>AI 会分析简历与 JD 的匹配度，并输出分值和分析报告。</li>'
                    .'</ol>'

                    .'<h2>评分报告包含什么？</h2>'
                    .'<ul>'
                    .'  <li><strong>综合得分</strong> — 简历与目标岗位的总体匹配度分值。</li>'
                    .'  <li><strong>关键词匹配度</strong> — 简历中出现的关键词与 JD 关键要求的覆盖情况。</li>'
                    .'  <li><strong>改进建议</strong> — AI 针对简历弱项的具体优化方向。</li>'
                    .'  <li><strong>每次评分都会保存历史</strong>，方便您对比不同版本的评分变化。</li>'
                    .'</ul>'

                    .'<h2>提升 ATS 评分的技巧</h2>'
                    .'<ul>'
                    .'  <li>在简历中自然地融入 JD 中的<strong>核心关键词</strong>。</li>'
                    .'  <li>使用标准的简历格式，避免复杂的排版和图表。</li>'
                    .'  <li>每个工作经历都要有具体的职责描述和成果量化。</li>'
                    .'  <li>针对不同岗位创建不同的简历版本，而不是用同一份简历投所有岗位。</li>'
                    .'</ul>',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'category_slug' => 'resume',
                'title' => '简历导入与导出',
                'slug' => 'import-export',
                'excerpt' => '如何上传已有简历、导出 PDF/Word 文档，以及生成分享链接。',
                'content' =>
                    '<h2>简历导入</h2>'
                    .'<p>如果您已经有现成的简历文档，无需逐项填写，直接导入即可：</p>'
                    .'<h3>支持的文件格式</h3>'
                    .'<ul><li>PDF（.pdf）</li><li>Word 文档（.docx）</li></ul>'

                    .'<h3>导入步骤</h3>'
                    .'<ol>'
                    .'  <li>在简历编辑器中，点击<strong>"导入文档"</strong>。</li>'
                    .'  <li>上传您的简历文件。</li>'
                    .'  <li>AI 会自动解析文件内容，识别出个人信息、教育背景、工作经历、技能等模块。</li>'
                    .'  <li>解析完成后，内容会自动填充到编辑器中，您可以检查和微调。</li>'
                    .'</ol>'

                    .'<h3>导入注意事项</h3>'
                    .'<ul>'
                    .'  <li>推荐使用文本型 PDF（非扫描件或图片型 PDF），识别准确率更高。</li>'
                    .'  <li>导入后建议逐模块<strong>检查确认</strong>，AI 解析可能存在识别偏差。</li>'
                    .'  <li>如果导入结果不理想，也可以使用"导入文档草稿"功能，直接生成一份结构化草稿供参考。</li>'
                    .'</ul>'

                    .'<h2>简历导出</h2>'
                    .'<h3>支持的导出格式</h3>'
                    .'<ul><li>PDF（适合投递和打印）</li><li>Word 文档（.docx，适合进一步编辑）</li></ul>'

                    .'<h3>导出方式</h3>'
                    .'<ol>'
                    .'  <li>在简历页面点击<strong>"导出 PDF"</strong>或<strong>"导出 Word"</strong>。</li>'
                    .'  <li>系统会生成导出任务并在后台处理。</li>'
                    .'  <li>处理完成后，您可以在"导出任务"页面下载文件。</li>'
                    .'  <li>也可以直接在简历页点击<strong>"打印预览"</strong>，查看 PDF 效果后再下载。</li>'
                    .'</ol>'

                    .'<h2>分享简历</h2>'
                    .'<p>您可以生成简历的<strong>分享链接</strong>，发送给他人查看：</p>'
                    .'<ol>'
                    .'  <li>在简历页面点击<strong>"分享"</strong>按钮。</li>'
                    .'  <li>系统生成一个唯一分享链接。</li>'
                    .'  <li>（可选）设置<strong>访问密码</strong>，只有输入密码才能查看。</li>'
                    .'  <li>随时可以<strong>关闭分享</strong>，链接立即失效。</li>'
                    .'</ol>',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'category_slug' => 'resume',
                'title' => '简历版本管理和模板',
                'slug' => 'version-and-template',
                'excerpt' => '管理简历的历史版本，切换模板样式，以及校招赛道功能。',
                'content' =>
                    '<h2>版本管理</h2>'
                    .'<p>每次对简历的修改和 AI 优化都可以保存为独立的版本，方便您对比和回退：</p>'
                    .'<ul>'
                    .'  <li><strong>手动创建快照</strong> — 在编辑器中选择"保存快照"，将当前内容保存为一个命名版本。</li>'
                    .'  <li><strong>版本对比</strong> — 选择任意两个版本，直观查看差异。</li>'
                    .'  <li><strong>回退版本</strong> — 点击"恢复"按钮，将简历内容恢复到历史版本。</li>'
                    .'  <li><strong>版本标签</strong> — 给版本添加自定义标签（如"投递A公司版"、"优化后版"），方便识别。</li>'
                    .'</ul>'

                    .'<h2>简历模板</h2>'
                    .'<p>平台提供多种简历显示模板，您可以在"简历模板"页面浏览并应用喜欢的模板。</p>'
                    .'<ul>'
                    .'  <li><strong>浏览模板</strong> — 在"简历模板"页面查看所有可用模板的预览效果。</li>'
                    .'  <li><strong>应用模板</strong> — 选择模板后点击"应用"，简历的展示样式即切换为新模板。</li>'
                    .'  <li><strong>撤销应用</strong> — 应用新模板后可以撤销，恢复之前的模板。</li>'
                    .'  <li><strong>模板分析</strong> — 查看各模板的使用数据，帮助您选择受欢迎的模板。</li>'
                    .'</ul>'

                    .'<h2>校招赛道</h2>'
                    .'<p>如果您是应届生或有特定的求职方向，可以给简历设置<strong>校招赛道</strong>（如"技术研发"、"产品经理"、"数据分析"等）。设置后，AI 优化和面试出题都会更贴合该赛道的风格和要求。</p>',
                'sort_order' => 4,
                'is_published' => true,
            ],

            // ============================
            // AI 模拟面试
            // ============================
            [
                'category_slug' => 'interview',
                'title' => 'AI 模拟面试 — 入门指南',
                'slug' => 'interview-starter',
                'excerpt' => '从头开始创建一场 AI 模拟面试，了解面试类型、设置选项和答题流程。',
                'content' =>
                    '<h2>什么是 AI 模拟面试？</h2>'
                    .'<p>AI 模拟面试是一个<strong>基于您简历和目标岗位的智能面试模拟器</strong>。AI 会根据您的背景自动生成个性化面试题目，每道题回答后 AI 会实时评分并给出改进建议，帮助您在真实面试前做好充分准备。</p>'

                    .'<h2>创建面试</h2>'
                    .'<ol>'
                    .'  <li>点击左侧菜单<strong>"模拟面试" → "创建面试"</strong>。</li>'
                    .'  <li><strong>选择简历</strong> — 选择一份简历作为面试背景，AI 会基于此出题。</li>'
                    .'  <li><strong>选择面试类型</strong> — 从 17 种面试类型中选择，包括技术、行为、综合、销售、管理、创意、金融、零售、制造、服务、传媒、教育等。</li>'
                    .'  <li><strong>填写岗位描述</strong>（可选）— 如果提供目标岗位的 JD，AI 会结合岗位要求定制问题。</li>'
                    .'  <li><strong>设置题目数量</strong> — 默认可设置 1-5 道题。</li>'
                    .'  <li><strong>可选设置</strong> — 面试语言（中文/英文）、难度偏好、是否开启练习模式等。</li>'
                    .'  <li>点击"开始面试"即可进入答题。</li>'
                    .'</ol>'

                    .'<h2>答题流程</h2>'
                    .'<ol>'
                    .'  <li>AI 会逐题提问，每次显示一道题目。</li>'
                    .'  <li>在输入框中输入您的答案，也支持<strong>语音输入</strong>（移动端）。</li>'
                    .'  <li>提交答案后，AI 会进行评估并给出评分和反馈。</li>'
                    .'  <li>AI 可能会基于您的回答<strong>主动追问</strong>，模拟真实面试官的深度提问。</li>'
                    .'  <li>全部题目答完后，生成<strong>综合面试报告</strong>。</li>'
                    .'</ol>'

                    .'<h2>面试中的操作</h2>'
                    .'<ul>'
                    .'  <li><strong>暂停</strong> — 临时离开可以暂停面试，回来后可继续。</li>'
                    .'  <li><strong>提前结束</strong> — 点击"结束面试"，已答题部分仍会生成评估。</li>'
                    .'</ul>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'interview',
                'title' => '面试类型与能力维度详解',
                'slug' => 'interview-types',
                'excerpt' => '深入了解 17 种面试类型的特点，以及每种面试考察的能力维度。',
                'content' =>
                    '<h2>面试类型分类</h2>'
                    .'<p>职路通提供三大类共 17 种面试类型，覆盖绝大多数行业和岗位：</p>'

                    .'<h3>核心面试（3 种）</h3>'
                    .'<ul>'
                    .'  <li><strong>技术面试</strong> — 考察基础原理、系统设计、故障排查、架构决策等 15 个技术维度。适合开发、测试、运维、架构等岗位。</li>'
                    .'  <li><strong>行为面试</strong> — 考察沟通表达、团队协作、冲突处理、抗压能力、领导力等软技能。几乎适用于所有岗位。</li>'
                    .'  <li><strong>综合面试</strong> — 技术和行为维度各半，全面评估综合能力。适合不确定面试侧重时的通用选择。</li>'
                    .'</ul>'

                    .'<h3>行业面试（9 种）</h3>'
                    .'<ul>'
                    .'  <li><strong>销售/BD</strong> — 客户挖掘、销售策略、谈判推进、关系维护。</li>'
                    .'  <li><strong>管理岗</strong> — 团队搭建、绩效管理、战略规划、变革推动。</li>'
                    .'  <li><strong>创意/设计</strong> — 创意思维、用户洞察、设计规范、内容策略。</li>'
                    .'  <li><strong>金融/财务</strong> — 报表分析、预算管理、投资分析、税务合规。</li>'
                    .'  <li><strong>零售/电商</strong> — 平台策略、品类管理、用户增长、供应链。</li>'
                    .'  <li><strong>制造/工业</strong> — 生产管理、精益制造、质量管理、供应链协同。</li>'
                    .'  <li><strong>服务行业</strong> — 客户服务、投诉处理、收益管理、多门店运营。</li>'
                    .'  <li><strong>传媒/广告</strong> — 内容策划、传播策略、数据分析、危机公关。</li>'
                    .'  <li><strong>教育/培训</strong> — 课程设计、教学方法、学生评估、教育技术。</li>'
                    .'</ul>'

                    .'<h3>特色面试（1 种）</h3>'
                    .'<ul>'
                    .'  <li><strong>深度面谈</strong> — 探讨职业价值观、人生追求、关键时刻决策、长期成长等深度话题。适合高管面试、职业转型辅导等场景。</li>'
                    .'</ul>'

                    .'<h2>能力维度轮换机制</h2>'
                    .'<p>每种面试类型都定义了 10-15 个能力维度，AI 在出题时会<strong>自动轮换</strong>维度，确保不同题目的考察角度不重复，实现全面评估。您可以在面试报告中看到每题对应的维度标签。</p>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'interview',
                'title' => '面试评分与报告解读',
                'slug' => 'interview-evaluation',
                'excerpt' => '了解 AI 评分的维度和面试报告的构成，学会利用反馈提升面试表现。',
                'content' =>
                    '<h2>评分机制</h2>'
                    .'<p>每道题提交后，AI 会从以下维度进行评分和反馈：</p>'
                    .'<ul>'
                    .'  <li><strong>内容完整度</strong> — 是否全面覆盖了题目要求的要点。</li>'
                    .'  <li><strong>表达结构</strong> — 回答是否有清晰的逻辑结构（如 STAR 框架）。</li>'
                    .'  <li><strong>专业深度</strong> — 回答是否展现了足够的专业知识和见解。</li>'
                    .'  <li><strong>语言表达</strong> — 语言是否流畅、准确、有说服力。</li>'
                    .'  <li><strong>改进建议</strong> — AI 针对具体回答给出的优化方向。</li>'
                    .'</ul>'

                    .'<h2>快速评分通道</h2>'
                    .'<p>为了提升响应速度，AI 评分采用分级处理：</p>'
                    .'<ul>'
                    .'  <li><strong>极短回答（20 字以下）</strong> → 快速判定为低质量回答，即时返回评分。</li>'
                    .'  <li><strong>短回答（60 字以下）</strong> → 中等质量快速评估。</li>'
                    .'  <li><strong>正常长度回答</strong> → 完整 AI 评估，评估结果在后台异步处理，前端<strong>轮询获取</strong>。</li>'
                    .'</ul>'

                    .'<h2>面试报告</h2>'
                    .'<p>面试结束后自动生成综合报告，包含：</p>'
                    .'<ul>'
                    .'  <li><strong>总评分</strong> — 整场面试的综合评分。</li>'
                    .'  <li><strong>各题评分明细</strong> — 每题分数、考察维度、AI 点评。</li>'
                    .'  <li><strong>能力雷达图</strong> — 各维度能力的可视化对比。</li>'
                    .'  <li><strong>优势与待改进</strong> — AI 总结的亮点和薄弱环节。</li>'
                    .'  <li><strong>练习建议</strong> — 针对弱项的具体练习方向。</li>'
                    .'</ul>'

                    .'<h2>导出报告</h2>'
                    .'<p>面试报告支持<strong>导出为 PDF</strong>，方便保存和回顾。在面试详情页点击"导出报告 PDF"即可下载。</p>'

                    .'<h2>错题本（问题收藏）</h2>'
                    .'<p>对于回答质量较低的题目，您可以将其<strong>收藏到错题本</strong>中，添加笔记和心得。错题本帮助您：</p>'
                    .'<ul>'
                    .'  <li>聚焦薄弱题型，有针对性地练习。</li>'
                    .'  <li>记录每道题的解答思路和改进方向。</li>'
                    .'  <li>随时回顾和重新作答。</li>'
                    .'</ul>',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'category_slug' => 'interview',
                'title' => '应届生面试成长路径',
                'slug' => 'fresh-grad-path',
                'excerpt' => '针对应届生的 6 阶段渐进式面试成长路径，从破冰到职业规划。',
                'content' =>
                    '<h2>应届生专属面试路径</h2>'
                    .'<p>如果您在简历中体现为应届生身份（或在创建面试时选择了应届生画像），AI 会自动采用<strong>6 阶段渐进式面试路径</strong>，每阶段的难度、语气和考察重点都不同，帮助您逐步适应面试节奏。</p>'

                    .'<table>'
                    .'<thead><tr><th>阶段</th><th>名称</th><th>重点考察</th></tr></thead>'
                    .'<tbody>'
                    .'<tr><td>第 1 题</td><td><strong>破冰摸底</strong></td><td>基础知识扎实程度、校园经历多样性、表达逻辑</td></tr>'
                    .'<tr><td>第 2 题</td><td><strong>项目还原</strong></td><td>项目真实性验证、动手能力和问题解决思路</td></tr>'
                    .'<tr><td>第 3 题</td><td><strong>协作与沟通</strong></td><td>团队协作意识、沟通主动性、冲突处理能力</td></tr>'
                    .'<tr><td>第 4 题</td><td><strong>学习与适应</strong></td><td>自学能力、信息搜索能力、面对新技术的适应速度</td></tr>'
                    .'<tr><td>第 5 题</td><td><strong>职业认知</strong></td><td>职业方向清晰度、行业认知深度、求职动机真实性</td></tr>'
                    .'<tr><td>第 6 题</td><td><strong>成长总结</strong></td><td>综合回顾收获、自我反思、未来 3 年规划</td></tr>'
                    .'</tbody>'
                    .'</table>'

                    .'<p>每阶段之间，AI 还会给出<strong>暖心的引导语</strong>，营造轻松真实的面试氛围，而不是机械的一问一答。</p>'

                    .'<h2>应届生职业方向指引</h2>'
                    .'<p>平台为应届生提供 8 个热门职业方向的<strong>学习路线和资源推荐</strong>，包括后端开发、前端开发、全栈开发、数据分析、产品经理、运维/DevOps、AI/算法等。在面试报告中可以查看对应方向的入门资源。</p>',
                'sort_order' => 3,
                'is_published' => true,
            ],

            // ============================
            // 求职管理
            // ============================
            [
                'category_slug' => 'job-hunting',
                'title' => '岗位匹配分析',
                'slug' => 'job-matching',
                'excerpt' => '使用 AI 分析简历与岗位的匹配度，发现技能差距和改进方向。',
                'content' =>
                    '<h2>什么是岗位匹配分析？</h2>'
                    .'<p>岗位匹配分析将您的简历与目标岗位描述进行<strong>对比分析</strong>，AI 会评估匹配程度、识别技能缺口，并给出有针对性的改进建议。</p>'

                    .'<h2>单次分析</h2>'
                    .'<ol>'
                    .'  <li>进入<strong>"岗位分析"</strong>页面。</li>'
                    .'  <li><strong>选择一份简历</strong>作为分析基础。</li>'
                    .'  <li><strong>输入岗位描述（JD）</strong>— 粘贴目标岗位的完整 JD 内容。</li>'
                    .'  <li>点击"开始分析"，AI 将输出匹配度报告。</li>'
                    .'</ol>'

                    .'<h2>批量分析</h2>'
                    .'<p>如果您需要同时分析多个岗位，可以使用批量分析功能：</p>'
                    .'<ol>'
                    .'  <li>进入<strong>"批量分析"</strong>页面。</li>'
                    .'  <li>上传包含多条 JD 的文件，或逐条输入多个岗位描述。</li>'
                    .'  <li>选择简历后提交，系统会在后台逐条处理。</li>'
                    .'  <li>您可以实时查看<strong>处理进度</strong>，处理完成后查看所有分析结果。</li>'
                    .'</ol>'

                    .'<h2>分析报告内容</h2>'
                    .'<ul>'
                    .'  <li><strong>匹配度评分</strong> — 简历与岗位的整体匹配百分比。</li>'
                    .'  <li><strong>关键词覆盖</strong> — JD 关键要求在简历中的覆盖情况。</li>'
                    .'  <li><strong>技能缺口</strong> — 岗位要求但简历中未体现的技能。</li>'
                    .'  <li><strong>简历优化建议</strong> — 针对该岗位的简历改进方向。</li>'
                    .'  <li><strong>面试准备方向</strong> — 该岗位可能的面试重点。</li>'
                    .'</ul>'

                    .'<h2>收藏岗位</h2>'
                    .'<p>分析完成后，您可以将感兴趣的岗位<strong>加入书签</strong>，方便后续回顾和对比。书签支持添加个人笔记。</p>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'job-hunting',
                'title' => '求职看板使用指南',
                'slug' => 'kanban-guide',
                'excerpt' => '使用看板管理投递进度，自定义状态流转，一目了然掌握求职全局。',
                'content' =>
                    '<h2>什么是求职看板？</h2>'
                    .'<p>求职看板是一个<strong>可视化的求职进度管理工具</strong>。您可以将每个投递目标作为一个卡片，在看板的不同阶段（列）之间拖拽移动，直观追踪每个岗位的进展。</p>'

                    .'<h2>看板操作</h2>'
                    .'<ol>'
                    .'  <li>进入<strong>"投递看板"</strong>页面。</li>'
                    .'  <li><strong>创建卡片</strong> — 点击"添加"，填写岗位名称、公司、JD 链接、截止日期等信息。</li>'
                    .'  <li><strong>移动卡片</strong> — 将卡片从一个阶段拖拽到另一个阶段，反映最新进展。</li>'
                    .'  <li><strong>编辑卡片</strong> — 点击卡片查看/编辑详情，记录面试时间、面试反馈等。</li>'
                    .'  <li><strong>更新状态</strong> — 快速修改卡片的当前状态。</li>'
                    .'</ol>'

                    .'<h2>默认阶段</h2>'
                    .'<p>看板默认提供以下阶段列，您可以根据需要自定义：</p>'
                    .'<ul>'
                    .'  <li>意向收集</li>'
                    .'  <li>简历投递</li>'
                    .'  <li>初筛中</li>'
                    .'  <li>面试中</li>'
                    .'  <li>Offer</li>'
                    .'  <li>已入职</li>'
                    .'  <li>已放弃</li>'
                    .'</ul>'

                    .'<h2>看板统计</h2>'
                    .'<p>看板顶部展示了各阶段的卡片数量和统计概览，帮助您：</p>'
                    .'<ul>'
                    .'  <li>了解求职进度总体分布。</li>'
                    .'  <li>发现进度缓慢的环节。</li>'
                    .'  <li>评估投递转化率。</li>'
                    .'</ul>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'job-hunting',
                'title' => '外部招聘信息',
                'slug' => 'external-recruitment',
                'excerpt' => '了解平台自动聚合的外部招聘信息池，以及如何浏览和筛选校招/社招岗位。',
                'content' =>
                    '<h2>外部招聘池</h2>'
                    .'<p>平台会<strong>自动抓取并聚合</strong>最新的校招和社招信息，每天更新。信息来源包括：</p>'
                    .'<ul>'
                    .'  <li><strong>OfferStar</strong> — 主流校招信息平台。</li>'
                    .'  <li><strong>求职方舟</strong> — 校招汇总和职位流信息。</li>'
                    .'</ul>'

                    .'<h2>如何使用</h2>'
                    .'<p>您在前台浏览简历和岗位分析时，系统会自动推荐相关的外部招聘信息。管理后台的"外部招聘池"页面也提供浏览、搜索和筛选功能。</p>'

                    .'<h2>信息更新频率</h2>'
                    .'<ul>'
                    .'  <li>OfferStar 职位 — <strong>每小时</strong>更新一次。</li>'
                    .'  <li>求职方舟校招 — <strong>每 2 小时</strong>更新一次。</li>'
                    .'  <li>求职方舟职位流 — <strong>每 2 小时</strong>更新一次。</li>'
                    .'</ul>'

                    .'<h2>链接有效性</h2>'
                    .'<p>平台每 6 小时会自动巡检外部投递链接的有效性，失效链接会被自动标记，确保您看到的都是可用信息。</p>',
                'sort_order' => 2,
                'is_published' => true,
            ],

            // ============================
            // 计费与套餐
            // ============================
            [
                'category_slug' => 'billing',
                'title' => '套餐对比与选择',
                'slug' => 'plan-comparison',
                'excerpt' => '了解各套餐的功能差异和额度限制，选择最适合您的方案。',
                'content' =>
                    '<h2>套餐体系</h2>'
                    .'<p>职路通提供<strong>多级套餐</strong>，从免费入门到专业版。每个套餐包含不同的 AI 使用配额和功能权限，您可以在首页或<strong>"个人中心 → 套餐定价"</strong>查看所有可用套餐的详细信息与价格。</p>'

                    .'<h2>套餐计费周期</h2>'
                    .'<ul>'
                    .'  <li><strong>月付</strong> — 按月付费，每月自动续费。适合短期使用或先尝试后再决定。</li>'
                    .'  <li><strong>年付</strong> — 按年付费，单价更低。适合长期高频用户。</li>'
                    .'</ul>'

                    .'<h2>配额体系（AI 用量额度）</h2>'
                    .'<p>套餐的 AI 使用额度按<strong>功能类型</strong>分别控制，每月 1 日自动重置。包含以下配额维度：</p>'
                    .'<table>'
                    .'<thead><tr><th>配额项</th><th>对应功能</th></tr></thead>'
                    .'<tbody>'
                    .'<tr><td><strong>AI 岗位定向优化</strong></td><td>对整份简历进行 AI 全量优化（含流式优化）</td></tr>'
                    .'<tr><td><strong>分段优化</strong></td><td>针对单个模块（如工作经历、项目经验）进行优化</td></tr>'
                    .'<tr><td><strong>ATS 评分</strong></td><td>根据岗位描述对简历进行 ATS 兼容性评分</td></tr>'
                    .'<tr><td><strong>关键词提取</strong></td><td>从简历或岗位描述中提取核心关键词</td></tr>'
                    .'<tr><td><strong>AI 导入简历</strong></td><td>上传 PDF/Word 文档，由 AI 解析并结构化</td></tr>'
                    .'<tr><td><strong>AI 面试</strong></td><td>创建并完成 AI 模拟面试</td></tr>'
                    .'<tr><td><strong>面试评估</strong></td><td>AI 对面试答案进行评分和反馈</td></tr>'
                    .'<tr><td><strong>岗位匹配</strong></td><td>对简历与岗位 JD 进行匹配分析</td></tr>'
                    .'<tr><td><strong>导出 PDF</strong></td><td>将简历导出为 PDF 格式</td></tr>'
                    .'</tbody>'
                    .'</table>'
                    .'<p><em>注：配额值 -1 表示不限量。具体每项的配额数请以套餐详情页为准。</em></p>'

                    .'<h2>功能权益</h2>'
                    .'<p>套餐还控制以下功能权限：</p>'
                    .'<ul>'
                    .'  <li><strong>高级 AI 模型</strong> — 是否可以使用更强的模型（如深度策略模版），生成质量更高。</li>'
                    .'  <li><strong>优先队列</strong> — 异步任务（如优化、面试评估）是否进入优先处理队列，响应更快。</li>'
                    .'</ul>'

                    .'<h2>免费套餐说明</h2>'
                    .'<p>新注册用户自动获得<strong>免费套餐</strong>，有效期极长（远超常规使用周期），包含基础的 AI 使用额度。免费额度用完后，您可以选择：</p>'
                    .'<ol>'
                    .'  <li><strong>升级套餐</strong> — 订阅月付或年付的高级套餐，获得更多配额和高级功能。</li>'
                    .'  <li><strong>购买次卡</strong> — 不升级套餐，按次购买补充额度。</li>'
                    .'</ol>'

                    .'<h2>套餐升级</h2>'
                    .'<p>升级套餐时，系统会自动计算<strong>旧套餐剩余天数的折算金额</strong>，抵扣到新套餐费用中，您只需支付差额部分。升级后配额和新功能即时生效。</p>'

                    .'<h2>查看当前套餐</h2>'
                    .'<p>进入<strong>"个人中心 → 我的订阅"</strong>即可查看当前套餐名称、有效期、各功能本月已用/剩余额度，以及订阅状态。</p>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'billing',
                'title' => '次卡购买与使用',
                'slug' => 'credit-packs',
                'excerpt' => '了解次卡的类型、购买流程、使用规则和消耗优先级。',
                'content' =>
                    '<h2>什么是次卡？</h2>'
                    .'<p>次卡是一种<strong>按次购买的补充额度包</strong>。当您本月的套餐配额用完后，无需升级套餐，购买次卡即可获得额外使用次数。</p>'

                    .'<h2>次卡类型</h2>'
                    .'<p>次卡根据配额范围分为两大类：</p>'
                    .'<ul>'
                    .'  <li><strong>通用次卡</strong> — 可用于平台所有 AI 功能，包括 AI 岗位定向优化、ATS 评分、关键词提取、AI 面试、面试评估、岗位匹配等。</li>'
                    .'  <li><strong>专用次卡</strong> — 仅可用于特定功能（例如仅限 AI 面试、仅限 AI 优化等）。购买时请仔细查看适用范围。</li>'
                    .'</ul>'

                    .'<h2>购买流程</h2>'
                    .'<ol>'
                    .'  <li>进入<strong>"个人中心 → 购买次卡"</strong>页面，浏览当前可售的次卡商品。</li>'
                    .'  <li>点击要购买的次卡商品，查看价格、次数和有效期。</li>'
                    .'  <li>确认后生成订单，进入支付页面。</li>'
                    .'  <li>完成支付宝付款后，次数<strong>即时到账</strong>。</li>'
                    .'</ol>'

                    .'<h2>消耗优先级（重要）</h2>'
                    .'<p>当您触发一个需要额度消耗的操作时，扣除优先级为：</p>'
                    .'<ol>'
                    .'  <li><strong>优先消耗套餐月配额</strong> — 月配额未用完时，先从套餐免费额度中扣除。</li>'
                    .'  <li><strong>月配额耗尽后消耗次卡</strong> — 月配额用完时，系统会提示您选择使用次卡。此时：专用次卡优先于通用次卡，同类型的次卡优先消耗即将到期的。</li>'
                    .'  <li><strong>两者耗尽则无法操作</strong> — 无可用额度时操作被拒绝，需等待下月重置或购买新的次卡。</li>'
                    .'</ol>'

                    .'<h2>有效期</h2>'
                    .'<p>每个次卡有独立的<strong>有效期</strong>（购买时展示），请在有效期内用完。过期后剩余次数自动作废。平台每天凌晨会自动清理过期次卡。</p>'

                    .'<h2>查看次卡余额</h2>'
                    .'<p>进入<strong>"个人中心 → 我的次卡"</strong>，可查看所有有效次卡的余额、适用范围和到期时间。</p>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'billing',
                'title' => '用量查询与订阅管理',
                'slug' => 'usage-and-subscription',
                'excerpt' => '如何查看 AI 用量明细、管理订阅、查看订单和支付问题解答。',
                'content' =>
                    '<h2>查看用量明细</h2>'
                    .'<p>进入<strong>"个人中心 → 用量明细"</strong>，可以查看：</p>'
                    .'<ul>'
                    .'  <li>各功能<strong>本月已用量 / 月配额上限</strong>的对比。</li>'
                    .'  <li>各功能的用量自动按月统计，每月 1 日重置。</li>'
                    .'  <li>用量数据有 <strong>Redis 缓存</strong>，展示速度极快。</li>'
                    .'</ul>'

                    .'<h2>用量消耗日志</h2>'
                    .'<p>每次 AI 操作成功后会记录详细消耗日志，您可以在<strong>"个人中心 → 用量历史"</strong>查看每笔消耗的时间、操作类型和来源（月配额 / 次卡）。</p>'

                    .'<h2>订阅管理</h2>'
                    .'<p>进入<strong>"个人中心 → 我的订阅"</strong>：</p>'
                    .'<ul>'
                    .'  <li>查看<strong>当前套餐</strong>：套餐名称、计费周期（月付/年付）、到期日期。</li>'
                    .'  <li>查看<strong>订阅状态</strong>：<br/><code>active</code>（生效中）、<code>expired</code>（已到期）、<code>cancelled</code>（已取消）。</li>'
                    .'  <li><strong>套餐降级</strong>：可随时取消当前订阅，当前周期内权益不变，到期后自动降为免费套餐。</li>'
                    .'  <li><strong>到期提醒</strong>：套餐到期前 3 天，系统会通过邮件自动发送续费提醒。</li>'
                    .'</ul>'

                    .'<h2>订单管理</h2>'
                    .'<p>进入<strong>"个人中心 → 我的订单"</strong>，可查看所有历史订单：</p>'
                    .'<ul>'
                    .'  <li><strong>订阅订单</strong> — 套餐购买记录，订单号以 <code>SUB</code> 开头。</li>'
                    .'  <li><strong>次卡订单</strong> — 次卡购买记录，订单号以 <code>CP</code> 开头。</li>'
                    .'  <li><strong>订单状态</strong>：<br/><code>pending</code>（待支付）→ <code>paid</code>（已支付）→ 权益生效<br/>超时未支付会自动变为 <code>expired</code>（已过期），默认超时时间为 72 小时。</li>'
                    .'  <li>未支付的订单可以<strong>取消</strong>。</li>'
                    .'</ul>'

                    .'<h2>支付方式</h2>'
                    .'<p>平台当前支持<strong>支付宝当面付（F2F）</strong>支付。付款后在页面确认，系统验证支付流水后自动激活套餐或发放次卡。如遇支付问题，请提交"意见反馈"联系我们。</p>'

                    .'<h2>套餐校验</h2>'
                    .'<p>系统每天凌晨会自动<strong>校验用户套餐一致性</strong>，确保到期或异常状态被正确处理，避免用户因系统延迟而受到影响。</p>',

                'sort_order' => 2,
                'is_published' => true,
            ],

            // ============================
            // 常见问题
            // ============================
            [
                'category_slug' => 'faq',
                'title' => '忘记密码怎么办？',
                'slug' => 'forgot-password',
                'excerpt' => '通过邮箱重置密码的详细步骤。',
                'content' =>
                    '<h2>重置密码步骤</h2>'
                    .'<ol>'
                    .'  <li>在<strong>登录页面</strong>点击"忘记密码"链接。</li>'
                    .'  <li>输入注册时使用的邮箱地址。</li>'
                    .'  <li>系统会向您的邮箱发送一封<strong>密码重置邮件</strong>（有效期有限，请尽快操作）。</li>'
                    .'  <li>点击邮件中的链接，进入重置密码页面。</li>'
                    .'  <li>输入新密码（需包含大小写字母、数字和特殊符号），确认后完成重置。</li>'
                    .'</ol>'

                    .'<h2>常见问题</h2>'
                    .'<ul>'
                    .'  <li><strong>没收到邮件？</strong>请检查邮箱的<strong>垃圾邮件</strong>文件夹。如果仍未收到，可能是邮箱地址输入错误，请确认输入的是注册时使用的邮箱。</li>'
                    .'  <li><strong>重置链接过期？</strong>重置链接有时效性，过期后需要重新发送。请重新点击"忘记密码"发起新的重置请求。</li>'
                    .'  <li><strong>绑定了第三方登录？</strong>如果您绑定了 GitHub 或支付宝，也可以直接使用第三方登录，登录后再修改密码。</li>'
                    .'</ul>',
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'category_slug' => 'faq',
                'title' => '如何提交反馈与联系客服？',
                'slug' => 'contact-support',
                'excerpt' => '了解平台反馈渠道和客服响应方式。',
                'content' =>
                    '<h2>意见反馈</h2>'
                    .'<p>平台内置了<strong>意见反馈功能</strong>，这是推荐的沟通方式：</p>'
                    .'<ol>'
                    .'  <li>点击左侧菜单<strong>"意见反馈"</strong>。</li>'
                    .'  <li>点击"提交反馈"，填写反馈主题和详细描述。</li>'
                    .'  <li>支持选择反馈类型（功能建议、Bug 报告、使用问题等）。</li>'
                    .'  <li>提交后管理员会在工作日内回复。</li>'
                    .'</ol>'

                    .'<h2>反馈追踪</h2>'
                    .'<p>您可以在"意见反馈"页面查看所有提交的反馈及其处理状态：</p>'
                    .'<ul>'
                    .'  <li><strong>待处理</strong> — 已提交，待管理员阅读。</li>'
                    .'  <li><strong>处理中</strong> — 管理员正在处理。</li>'
                    .'  <li><strong>已完成</strong> — 问题已解决。</li>'
                    .'  <li><strong>已采纳</strong> — 建议已被纳入开发计划。</li>'
                    .'</ul>'
                    .'<p>管理员回复后您可以继续追问，形成完整的对话记录。处理完成后还可以对服务进行满意度评分。</p>'

                    .'<h2>提交反馈的建议</h2>'
                    .'<p>为了让您的问题得到更快的解决，请在反馈中尽量包含以下信息：</p>'
                    .'<ul>'
                    .'  <li>问题的<strong>详细描述</strong>和预期行为。</li>'
                    .'  <li>问题出现的<strong>时间和操作步骤</strong>。</li>'
                    .'  <li>相关<strong>截图</strong>（尤其是界面异常或错误提示）。</li>'
                    .'  <li>使用的<strong>浏览器和设备</strong>信息。</li>'
                    .'</ul>',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'category_slug' => 'faq',
                'title' => 'AI 优化/面试失败怎么办？',
                'slug' => 'ai-failure-help',
                'excerpt' => '排查 AI 功能使用中可能遇到的问题及解决方法。',
                'content' =>
                    '<h2>常见原因与解决方法</h2>'

                    .'<h3>1. 提示"额度不足"</h3>'
                    .'<p>如果提示额度不足，说明您当月的免费次数已用完。解决方法：</p>'
                    .'<ul>'
                    .'  <li>等待下月额度重置。</li>'
                    .'  <li>购买次卡补充额度。</li>'
                    .'  <li>升级到更高套餐。</li>'
                    .'</ul>'

                    .'<h3>2. AI 返回错误或超时</h3>'
                    .'<p>偶尔 AI 服务可能因为网络波动或服务商限流导致暂时不可用。平台内置了<strong>自动降级机制</strong>（主 AI 不可用时自动切换到备用 AI），通常会自动恢复。如果持续失败：</p>'
                    .'<ul>'
                    .'  <li>刷新页面后重试。</li>'
                    .'  <li>等待几分钟后重试（限流会在一段时间后自动解除）。</li>'
                    .'  <li>如果长时间无法使用，请提交反馈告知我们。</li>'
                    .'</ul>'

                    .'<h3>3. 简历导入解析不准确</h3>'
                    .'<ul>'
                    .'  <li>请确保上传的是<strong>文本型 PDF</strong>（非扫描件/图片型）。</li>'
                    .'  <li>Word 文档的解析准确率通常更高，推荐优先使用。</li>'
                    .'  <li>导入后请逐模块检查确认，手动修正识别错误的部分。</li>'
                    .'  <li>如果导入效果不理想，可以使用"导入文档草稿"功能作为参考。</li>'
                    .'</ul>'

                    .'<h3>4. 面试评分迟迟不显示</h3>'
                    .'<p>面试评分在后台异步处理，通常在几秒到几十秒内返回。如果长时间显示"评估中"：</p>'
                    .'<ul>'
                    .'  <li>刷新页面查看是否已完成。</li>'
                    .'  <li>等待 2-3 分钟，系统有超时恢复机制。</li>'
                    .'  <li>如持续未返回，可重新提交答案触发重新评估。</li>'
                    .'</ul>',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'category_slug' => 'faq',
                'title' => '数据隐私与安全保障',
                'slug' => 'privacy-security',
                'excerpt' => '了解平台如何保护您的简历和个人数据安全。',
                'content' =>
                    '<h2>数据加密</h2>'
                    .'<ul>'
                    .'  <li><strong>传输加密</strong> — 全站使用 HTTPS 加密传输，保护您在网络传输中的数据安全。</li>'
                    .'  <li><strong>存储加密</strong> — 简历原始内容在数据库中<strong>加密存储</strong>，即使数据库被非法访问也无法直接读取简历内容。</li>'
                    .'  <li><strong>密码加密</strong> — 密码使用 bcrypt 算法加盐哈希存储，平台无法获知您的明文密码。</li>'
                    .'</ul>'

                    .'<h2>数据控制</h2>'
                    .'<ul>'
                    .'  <li><strong>简历隐私</strong> — 您的简历默认只有自己可见，除非您主动开启分享功能。</li>'
                    .'  <li><strong>分享控制</strong> — 分享链接可以随时关闭，并可设置访问密码。</li>'
                    .'  <li><strong>数据导出</strong> — 您可以在个人中心申请导出所有个人数据。</li>'
                    .'  <li><strong>账号注销</strong> — 注销后数据在冷静期结束后永久删除。</li>'
                    .'</ul>'

                    .'<h2>AI 数据使用</h2>'
                    .'<p>当您使用 AI 优化或面试功能时，简历内容和答案会发送给 AI 服务提供商进行处理。请放心，我们仅使用必要的上下文信息，且 AI 提供商不会用您的数据训练模型。</p>'

                    .'<h2>通知偏好</h2>'
                    .'<p>进入<strong>"个人中心 → 通知偏好"</strong>，您可以自主控制哪些类型的邮件通知需要接收，包括：简历完成提醒、面试开始提醒、面试完成提醒、投递提醒、截止日期提醒、营销信息等。</p>',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'category_slug' => 'faq',
                'title' => '简历回收站与数据恢复',
                'slug' => 'resume-trash',
                'excerpt' => '了解简历删除后的回收站机制，以及如何恢复误删的简历。',
                'content' =>
                    '<h2>回收站机制</h2>'
                    .'<p>当您在简历列表中<strong>删除</strong>一份简历时，它不会被立即永久删除，而是进入<strong>回收站</strong>。这是一种安全机制，防止误操作导致数据丢失。</p>'

                    .'<h2>查看回收站</h2>'
                    .'<p>在"我的简历"页面，点击<strong>"回收站"</strong>标签，即可查看所有已删除的简历。</p>'

                    .'<h2>恢复简历</h2>'
                    .'<p>在回收站中找到需要恢复的简历，点击<strong>"恢复"</strong>按钮，简历将回到简历列表中，所有数据完好。</p>'

                    .'<h2>永久删除</h2>'
                    .'<p>如果您确定不再需要某份简历，可以在回收站中点击<strong>"永久删除"</strong>，该简历及其所有数据将被彻底清除，<strong>不可恢复</strong>，请谨慎操作。</p>'

                    .'<h2>关键提醒</h2>'
                    .'<ul>'
                    .'  <li>回收站中的简历不占用创建配额。</li>'
                    .'  <li>建议在永久删除前导出 PDF 备份。</li>'
                    .'</ul>',
                'sort_order' => 4,
                'is_published' => true,
            ],
        ];

        foreach ($articles as $article) {
            $categorySlug = $article['category_slug'];
            unset($article['category_slug']);

            $category = HelpCategory::query()->where('slug', $categorySlug)->first();
            if (! $category) {
                continue;
            }

            HelpArticle::query()->updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, [
                    'category_id' => $category->id,
                    'published_at' => now(),
                ])
            );
        }

        $this->command->info('帮助中心数据已创建：7 个分类、' . count($articles) . ' 篇文章。');
    }
}
