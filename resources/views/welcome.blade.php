<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="auto">
<head>
    @include('partials.theme-init')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="{{ $seoDescription }}">
  <meta name="keywords" content="{{ $seoKeywords }}">
  <meta property="og:title" content="{{ $siteName }}">
  <meta property="og:description" content="{{ $seoDescription }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ $siteSettings['site_url'] ?? url('/') }}">
  @if(!empty($faviconUrl))
    <link rel="icon" href="{{ $faviconUrl }}">
  @endif
  <title>{{ $siteName }}</title>
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('fonts/outfit/outfit.css') }}">
  <link rel="stylesheet" href="{{ asset('fonts/work-sans/work-sans.css') }}">
  <link rel="preload" href="{{ asset('css/dark-mode.css') }}" as="style">
  <link rel="preload" href="{{ asset('css/pages/welcome.css') }}" as="style">
  <link rel="stylesheet" href="{{ asset('css/pages/welcome.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
</head>
<body>
  <!-- Navigation -->
  <nav id="mainNav" class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
        @if(!empty($siteLogoUrl))
          <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="me-2" style="height: 28px;">
        @else
          <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
        @endif
        <span class="fw-bold">{{ $siteName }}</span>
      </a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="#features">功能特点</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#membership">会员介绍</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#pricing">价格方案</a>
          </li>
          @if(!empty($latestEvents))
          <li class="nav-item">
            <a class="nav-link" href="#events">平台动态</a>
          </li>
          @endif
        </ul>
        
        <div class="d-flex ms-lg-4 gap-2">
          <button class="theme-toggle-btn" data-theme-toggle type="button" title="切换主题">
            <i class="bi bi-sun-fill theme-icon-light"></i>
            <i class="bi bi-moon-fill theme-icon-dark" style="display:none;"></i>
            <i class="bi bi-circle-half theme-icon-auto" style="display:none;"></i>
            <span class="theme-toggle-label">自动</span>
          </button>
          @auth
            <a href="{{ route('user.dashboard') }}" class="btn btn-primary">
              <i class="bi bi-dashboard me-2"></i>进入控制台
            </a>
          @else
            <a href="{{ route('login') }}" class="btn btn-link text-dark">登录</a>
            <a href="{{ route('register') }}" class="btn btn-primary">免费注册</a>
          @endauth
        </div>
      </div>
    </div>
  </nav>

  <!-- Maintenance Notice -->
  @if(!empty($maintenanceNotice))
  <div class="alert alert-warning rounded-0 mb-0" style="margin-top: 76px;">
    <div class="container">{{ $maintenanceNotice }}</div>
  </div>
  @endif

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <div class="mb-4">
            <span class="hero-badge reveal">
              <i class="bi bi-award"></i>
              年最佳求职工具
            </span>
          </div>
          
          <h1 class="display-4 fw-bold mb-4 reveal" data-reveal-delay="80">
            让 AI 帮你打造<br>
            <span class="text-primary">完美简历</span><br>
            斩获心仪 Offer
          </h1>
          
          <p class="lead text-secondary mb-4 reveal" data-reveal-delay="160">{{ $siteSubtitle }}</p>
          
          <div class="d-flex flex-column flex-sm-row gap-3 mb-4 reveal" data-reveal-delay="240">
            @guest
              <a href="{{ route('register') }}" class="btn btn-cta-primary btn-lg d-flex align-items-center justify-content-center gap-2 px-4 py-3">
                <i class="bi bi-upload"></i>
                <span>免费上传简历</span>
                <i class="bi bi-arrow-right"></i>
              </a>
              <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center gap-2 px-4 py-3">
                <i class="bi bi-file-earmark-text"></i>
                <span>使用模板创建</span>
              </a>
            @else
              <a href="{{ route('user.dashboard') }}" class="btn btn-cta-primary btn-lg d-flex align-items-center justify-content-center gap-2 px-4 py-3">
                <i class="bi bi-dashboard"></i>
                <span>进入控制台</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            @endauth
          </div>
          
          <div class="d-flex align-items-center gap-3 reveal" data-reveal-delay="320">
            <div class="avatar-group d-flex">
              <div class="avatar">L</div>
              <div class="avatar">Z</div>
              <div class="avatar">W</div>
              <div class="avatar">+</div>
            </div>
            <div class="small text-secondary">
              <div class="d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
              </div>
              <span>已帮助 10,000+ 学生拿到 Offer</span>
            </div>
          </div>
        </div>
        
        <div class="col-lg-6">
          <div class="position-relative reveal" data-reveal-delay="200">
            <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient opacity-25" 
                 style="background: linear-gradient(90deg, rgba(14,165,233,0.2), rgba(249,115,22,0.2)); border-radius: 1.5rem; filter: blur(40px);"></div>
            
            <div class="position-relative rounded-4 p-4 hero-visual-card">
              <svg viewBox="0 0 600 340" xmlns="http://www.w3.org/2000/svg" class="img-fluid rounded-3 mb-3" style="background:#fff;">
                <defs>
                  <linearGradient id="hg" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#0EA5E9"/><stop offset="100%" stop-color="#38BDF8"/></linearGradient>
                  <linearGradient id="pg" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#0EA5E9"/><stop offset="72%" stop-color="#38BDF8"/><stop offset="72%" stop-color="#E2E8F0"/><stop offset="100%" stop-color="#E2E8F0"/></linearGradient>
                </defs>
                <!-- Header bar -->
                <rect width="600" height="48" rx="8" fill="url(#hg)" y="0"/>
                <circle cx="24" cy="24" r="8" fill="rgba(255,255,255,.3)"/>
                <rect x="40" y="18" width="80" height="12" rx="3" fill="rgba(255,255,255,.6)"/>
                <rect x="480" y="16" width="50" height="16" rx="8" fill="rgba(255,255,255,.25)"/>
                <rect x="540" y="16" width="50" height="16" rx="8" fill="rgba(255,255,255,.25)"/>
                <!-- Left: Resume preview -->
                <rect x="16" y="60" width="270" height="268" rx="8" fill="#F8FAFC" stroke="#E2E8F0"/>
                <rect x="32" y="76" width="60" height="8" rx="2" fill="#94A3B8"/>
                <rect x="32" y="92" width="100" height="6" rx="2" fill="#CBD5E1"/>
                <rect x="32" y="108" width="80" height="6" rx="2" fill="#CBD5E1"/>
                <rect x="32" y="128" width="50" height="7" rx="2" fill="#94A3B8"/>
                <rect x="32" y="143" width="120" height="5" rx="2" fill="#E2E8F0"/>
                <rect x="32" y="155" width="110" height="5" rx="2" fill="#E2E8F0"/>
                <rect x="32" y="167" width="90" height="5" rx="2" fill="#E2E8F0"/>
                <rect x="32" y="187" width="50" height="7" rx="2" fill="#94A3B8"/>
                <rect x="32" y="202" width="120" height="5" rx="2" fill="#E2E8F0"/>
                <rect x="32" y="214" width="100" height="5" rx="2" fill="#E2E8F0"/>
                <!-- Highlight marks -->
                <rect x="32" y="143" width="40" height="5" rx="2" fill="#FBBF24" opacity=".5"/>
                <rect x="32" y="202" width="50" height="5" rx="2" fill="#34D399" opacity=".5"/>
                <!-- Right: Score panel -->
                <rect x="302" y="60" width="282" height="268" rx="8" fill="#FFF" stroke="#E2E8F0"/>
                <text x="316" y="86" font-size="13" font-weight="600" fill="#475569" font-family="sans-serif">ATS 匹配度分析</text>
                <!-- Score circle -->
                <circle cx="443" cy="160" r="52" fill="none" stroke="#E2E8F0" stroke-width="10"/>
                <circle cx="443" cy="160" r="52" fill="none" stroke="#0EA5E9" stroke-width="10" stroke-dasharray="235 327" stroke-linecap="round" transform="rotate(-90 443 160)"/>
                <text x="443" y="155" text-anchor="middle" font-size="28" font-weight="700" fill="#0EA5E9" font-family="sans-serif">72</text>
                <text x="443" y="172" text-anchor="middle" font-size="10" fill="#94A3B8" font-family="sans-serif">/100</text>
                <!-- Keyword tags -->
                <rect x="316" y="224" width="72" height="24" rx="12" fill="#DCFCE7"/>
                <text x="352" y="240" text-anchor="middle" font-size="10" fill="#16A34A" font-family="sans-serif">React</text>
                <rect x="396" y="224" width="88" height="24" rx="12" fill="#DCFCE7"/>
                <text x="440" y="240" text-anchor="middle" font-size="10" fill="#16A34A" font-family="sans-serif">TypeScript</text>
                <rect x="492" y="224" width="72" height="24" rx="12" fill="#FEF9C3"/>
                <text x="528" y="240" text-anchor="middle" font-size="10" fill="#CA8A04" font-family="sans-serif">Node.js</text>
                <rect x="316" y="256" width="56" height="24" rx="12" fill="#FEF9C3"/>
                <text x="344" y="272" text-anchor="middle" font-size="10" fill="#CA8A04" font-family="sans-serif">SQL</text>
                <rect x="380" y="256" width="72" height="24" rx="12" fill="#FEE2E2"/>
                <text x="416" y="272" text-anchor="middle" font-size="10" fill="#DC2626" font-family="sans-serif">Docker</text>
                <rect x="460" y="256" width="80" height="24" rx="12" fill="#DCFCE7"/>
                <text x="500" y="272" text-anchor="middle" font-size="10" fill="#16A34A" font-family="sans-serif">Vue.js</text>
                <!-- Legend -->
                <circle cx="326" cy="306" r="4" fill="#16A34A"/><text x="336" y="310" font-size="9" fill="#64748B" font-family="sans-serif">匹配</text>
                <circle cx="376" cy="306" r="4" fill="#CA8A04"/><text x="386" y="310" font-size="9" fill="#64748B" font-family="sans-serif">部分匹配</text>
                <circle cx="442" cy="306" r="4" fill="#DC2626"/><text x="452" y="310" font-size="9" fill="#64748B" font-family="sans-serif">缺失</text>
              </svg>
              
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="text-secondary fw-medium">简历综合评分</span>
                  <span class="h3 mb-0 text-primary fw-bold">72<small class="text-secondary fs-6">/100</small></span>
                </div>
                <div class="progress" style="height: 12px;">
                  <div class="progress-bar progress-bar-custom" role="progressbar" style="width: 72%"></div>
                </div>
              </div>
              
              <div class="row g-2">
                <div class="col-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle text-success fs-5"></i>
                    <span class="small text-secondary">发现 3 个亮点</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bullseye text-warning fs-5"></i>
                    <span class="small text-secondary">需改进 5 项</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  

  <!-- Stats Section -->
  <section class="py-5">
    <div class="container">
      <div class="row g-4 stats-row">
        <div class="col-6 col-lg-3">
          <div class="stat-item reveal">
            <div class="stat-number" data-count="10000" data-count-original="10,000+">10,000+</div>
            <div class="text-secondary">已帮助学生</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-item reveal" data-reveal-delay="80">
            <div class="stat-number" data-count="85" data-count-original="85%">85%</div>
            <div class="text-secondary">面试通过率提升</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-item reveal" data-reveal-delay="160">
            <div class="stat-number" data-count="500" data-count-original="500+">500+</div>
            <div class="text-secondary">合作企业</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-item reveal" data-reveal-delay="240">
            <div class="stat-number">
              <span data-count="4.9" data-count-original="4.9">4.9</span> <i class="bi bi-star-fill fs-4"></i>
            </div>
            <div class="text-secondary">用户满意度</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5 reveal">
        <h2 class="display-5 fw-bold mb-3">一站式求职解决方案</h2>
        <p class="lead text-secondary">从简历优化到面试准备，全方位提升求职竞争力</p>
      </div>
      
      <div class="row g-4">
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="0">
          <div class="card feature-card h-100 p-4">
            <div class="feature-icon bg-primary mb-3">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <h5 class="fw-bold mb-2">AI 简历分析</h5>
            <p class="text-secondary mb-0">智能识别简历问题，提供个性化优化建议，从格式、内容到关键词全方位优化</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="80">
          <div class="card feature-card h-100 p-4">
            <div class="feature-icon bg-success mb-3">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <h5 class="fw-bold mb-2">实时评分系统</h5>
            <p class="text-secondary mb-0">边编辑边查看评分变化，直观了解每次改进对简历质量的影响</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="160">
          <div class="card feature-card h-100 p-4">
            <div class="feature-icon bg-warning mb-3">
              <i class="bi bi-bullseye"></i>
            </div>
            <h5 class="fw-bold mb-2">智能面试准备</h5>
            <p class="text-secondary mb-0">根据简历内容自动生成面试题库，提供参考答案和面试技巧指导</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="0">
          <div class="card feature-card h-100 p-4">
            <div class="feature-icon bg-purple mb-3" style="background-color: #A855F7;">
              <i class="bi bi-people"></i>
            </div>
            <h5 class="fw-bold mb-2">海量模板库</h5>
            <p class="text-secondary mb-0">500+ 专业简历模板，覆盖技术、产品、设计、市场等各类岗位</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="80">
          <div class="card feature-card h-100 p-4">
            <div class="feature-icon bg-info mb-3" style="background-color: #EC4899;">
              <i class="bi bi-trophy"></i>
            </div>
            <h5 class="fw-bold mb-2">行业案例参考</h5>
            <p class="text-secondary mb-0">查看优秀简历案例，学习成功经验，了解不同岗位的简历偏好</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="160">
          <div class="card feature-card h-100 p-4">
            <div class="feature-icon bg-info mb-3">
              <i class="bi bi-check-all"></i>
            </div>
            <h5 class="fw-bold mb-2">ATS 系统优化</h5>
            <p class="text-secondary mb-0">确保简历通过企业 ATS 筛选，提升简历通过率到 HR 手中的概率</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  @include("welcome._demo-section")

  @if($plans->isNotEmpty())
  <!-- Membership Introduction -->
  <section id="membership" class="py-5">
    <div class="container">
      <div class="text-center mb-5 reveal">
        <h2 class="display-5 fw-bold mb-3">会员介绍</h2>
        <p class="lead text-secondary">选择适合你的会员方案，解锁更多求职能力</p>
      </div>

      <div class="row g-4">
        @foreach($plans as $plan)
        @php
          $quotaKeys = ['resumes_per_month','ai_optimize_per_month','ai_section_optimize_per_month','ats_score_per_month','export_pdf_per_month','interview_count_per_month','job_match_per_month'];
          $quotaItems = [];
          foreach ($quotaKeys as $key) {
              $v = $plan->quotas[$key] ?? null;
              $limit = is_array($v) ? ($v['monthly'] ?? $v['total'] ?? null) : $v;
              if ($limit === -1 || $limit === '-1') {
                  $quotaItems[] = '不限';
              } elseif ($limit !== null && $limit > 0) {
                  $quotaItems[] = $limit . '次';
              }
          }
          $boolFeatures = [];
          foreach (['advanced_model','priority_queue','dedicated_support','watermark_free'] as $f) {
              if ($plan->hasFeature($f)) {
                  $boolFeatures[] = match($f) {
                      'advanced_model' => '高级模型',
                      'priority_queue' => '优先队列',
                      'dedicated_support' => '专属客服',
                      'watermark_free' => '无水印',
                      default => $f,
                  };
              }
          }
          $quotaItems = array_merge($quotaItems, $boolFeatures);
          $quotaItems = array_slice($quotaItems, 0, 6);
        @endphp
        <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="{{ $loop->index * 80 }}">
          <div class="card feature-card membership-card h-100 p-4 text-center">
            <div class="mb-3">
              <span class="avatar bg-{{ $plan->slug === 'pro' ? 'purple' : ($plan->slug === 'basic' ? 'primary' : 'secondary') }}-lt" style="width:56px;height:56px;font-size:1.5rem;border-radius:50%">
                <i class="bi bi-{{ $plan->slug === 'pro' ? 'gem' : ($plan->slug === 'basic' ? 'star-fill' : 'gift-fill') }} text-{{ $plan->slug === 'pro' ? 'purple' : ($plan->slug === 'basic' ? 'primary' : 'secondary') }}"></i>
              </span>
            </div>
            <h4 class="fw-bold mb-2">{{ $plan->name }}</h4>
            @php $monthly = (int) $plan->price_monthly; @endphp
            <p class="text-secondary small mb-3">
              @if($monthly > 0) ¥{{ rtrim(rtrim(number_format($monthly / 100, 2), '0'), '.') }}/月 @else 免费 @endif
            </p>
            <ul class="list-unstyled text-start mb-0 small">
              @foreach($quotaItems as $item)
              <li class="mb-1">
                <i class="bi bi-check2 text-success me-1"></i>{{ $item }}
              </li>
              @endforeach
              @if(count($quotaItems) === 0)
              <li class="text-secondary">暂无配置</li>
              @endif
            </ul>
          </div>
        </div>
        @endforeach
      </div>

      <div class="text-center mt-4">
        <a href="#pricing" class="btn btn-outline-primary">
          查看价格方案 <i class="bi bi-arrow-down ms-1"></i>
        </a>
      </div>
    </div>
  </section>
  @endif

  @include("welcome._pricing-section")

  @if(!empty($latestEvents))
  <section id="events" class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5 reveal">
        <h2 class="fw-bold mb-2">平台动态</h2>
        <p class="text-secondary">最新功能、活动与行业洞察</p>
      </div>
      <div class="row g-4">
        @foreach($latestEvents as $event)
        <div class="col-md-4 reveal" data-reveal-delay="{{ $loop->index * 100 }}">
          <div class="card event-card h-100 border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge tag-{{ $event['category'] ?? '' }}">{{ $event['category'] ?? '' }}</span>
                <small class="text-secondary ms-auto">{{ \Illuminate\Support\Carbon::parse($event['published_at'])->format('m-d') }}</small>
              </div>
              <h5 class="fw-bold mb-2">
                <a href="{{ route('public.events.show', $event['slug']) }}" class="text-reset stretched-link text-decoration-none">{{ $event['title'] }}</a>
              </h5>
              @if(!empty($event['summary']))
              <p class="text-secondary small mb-0">{{ Str::limit($event['summary'], 80) }}</p>
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
      <div class="text-center mt-4">
        <a href="{{ route('public.events.index') }}" class="btn btn-outline-primary">
          查看全部动态 <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </section>
  @endif

  <!-- CTA Section -->
  <section class="cta-section py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center reveal">
          <h2 class="display-5 fw-bold text-white mb-3">准备好打造你的完美简历了吗？</h2>
          <p class="lead mb-4" style="color: rgba(255,255,255,0.9);">立即注册，开启职场成功之路</p>
          <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            @guest
              <a href="{{ route('register') }}" class="btn btn-light btn-lg px-5 py-3 fw-semibold">免费开始</a>
              <a href="#demo" class="btn btn-outline-light btn-lg px-5 py-3">预约演示</a>
            @else
              <a href="{{ route('user.dashboard') }}" class="btn btn-light btn-lg px-5 py-3 fw-semibold">进入控制台</a>
            @endguest
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-main">
        <div class="footer-brand">
          <div class="footer-logo">
            @if(!empty($siteLogoUrl))
              <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}">
            @else
              <i class="bi bi-lightning-charge-fill"></i>
            @endif
            <span>{{ $siteName }}</span>
          </div>
          <p class="footer-tagline">{{ $siteSubtitle }}</p>
        </div>

        

        <div class="footer-social">
          @if(!empty($githubUrl))
            <a href="{{ $githubUrl }}" target="_blank" rel="noopener noreferrer" title="GitHub">
              <i class="bi bi-github"></i>
            </a>
          @endif
          @if(!empty($wechatUrl))
            <a href="{{ $wechatUrl }}" target="_blank" rel="noopener noreferrer" title="微信">
              <i class="bi bi-wechat"></i>
            </a>
          @endif
          @if(!empty($linkedinUrl))
            <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener noreferrer" title="LinkedIn">
              <i class="bi bi-linkedin"></i>
            </a>
          @endif
          @if(!empty($siteSettings['contact_email']))
            <a href="mailto:{{ $siteSettings['contact_email'] }}" title="Email">
              <i class="bi bi-envelope"></i>
            </a>
          @endif
        </div>
      </div>

      <div class="footer-bottom">
        <div class="footer-copyright">
          @if(!empty($copyrightText))
            {{ $copyrightText }}
          @else
            &copy; {{ date('Y') }} {{ $siteName }}
          @endif
        </div>
        <div class="footer-legal">
          @if(!empty($icpNumber))
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">{{ $icpNumber }}</a>
          @endif
          @if(!empty($policeRecordNumber))
            @if(!empty($policeRecordUrl))
              <a href="{{ $policeRecordUrl }}" target="_blank" rel="noopener noreferrer">{{ $policeRecordNumber }}</a>
            @else
              <span>{{ $policeRecordNumber }}</span>
            @endif
          @endif
        </div>
      </div>
    </div>
  </footer>

  <script defer src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('js/theme-switcher.js') }}"></script>
  <script src="{{ asset('js/welcome.js') }}"></script>
</body>
</html>