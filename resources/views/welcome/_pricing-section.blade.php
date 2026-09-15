  <!-- Pricing Section -->
  <section id="pricing" class="py-5">
    <div class="container">
      <div class="text-center mb-5 reveal">
        <h2 class="display-5 fw-bold mb-3">简单透明的价格方案</h2>
        <p class="lead text-secondary">学生专属优惠，投资自己的未来</p>
      </div>

      @php
        $featureMap = [
            'resumes_per_month' => ['icon' => 'file-earmark-text', 'label' => '简历数', 'format' => 'quota'],
            'ai_optimize_per_month' => ['icon' => 'robot', 'label' => 'AI 全文优化', 'format' => 'quota_monthly'],
            'ai_section_optimize_per_month' => ['icon' => 'puzzle', 'label' => '分段优化', 'format' => 'quota_monthly'],
            'ats_score_per_month' => ['icon' => 'graph-up-arrow', 'label' => 'ATS 评分', 'format' => 'quota_daily'],
            'export_pdf_per_month' => ['icon' => 'file-pdf', 'label' => '导出 PDF', 'format' => 'quota_monthly'],
            'interview_count_per_month' => ['icon' => 'chat-dots', 'label' => '模拟面试', 'format' => 'quota_monthly'],
            'job_match_per_month' => ['icon' => 'briefcase', 'label' => 'AI 岗位匹配', 'format' => 'quota_monthly'],
            'job_tracking_count' => ['icon' => 'kanban', 'label' => '投递跟踪', 'format' => 'quota_count'],
            'advanced_model' => ['icon' => 'cpu', 'label' => '高级 AI 模型', 'format' => 'bool'],
            'priority_queue' => ['icon' => 'lightning-charge', 'label' => 'AI 优先队列', 'format' => 'bool'],
            'dedicated_support' => ['icon' => 'person-check', 'label' => '专属客服', 'format' => 'bool'],
            'watermark_free' => ['icon' => 'badge-tm', 'label' => '无水印导出', 'format' => 'bool'],
        ];

        $slugStyles = [
            'free' => ['badge' => null, 'popular' => false],
            'basic' => ['badge' => '最受欢迎', 'popular' => true],
            'pro' => ['badge' => null, 'popular' => false],
        ];

        $planFeatures = [];
        foreach ($plans as $plan) {
            $items = [];
            foreach ($featureMap as $key => $meta) {
                $value = $plan->quotas[$key] ?? null;
                $hasFeature = $plan->hasFeature($key);

                if ($meta['format'] === 'bool') {
                    if ($hasFeature) {
                        $items[] = $meta['label'];
                    }
                    continue;
                }

                $limit = is_array($value) ? ($value['monthly'] ?? $value['total'] ?? null) : $value;
                if ($limit === -1 || $limit === '-1') {
                    $items[] = $meta['label'] . '：不限';
                } elseif ($limit !== null && $limit > 0) {
                    $items[] = $meta['label'] . '：' . $limit . match ($meta['format']) {
                        'quota_monthly' => ' 次/月',
                        'quota_daily' => ' 次/天/简历',
                        'quota_count' => ' 条',
                        default => ' 份',
                    };
                } elseif ($limit === 0 || $limit === '0') {
                    continue;
                }
            }
            $planFeatures[$plan->slug] = array_slice($items, 0, 6);
        }
      @endphp

      <div class="row g-4 align-items-center justify-content-center">
        @foreach ($plans as $plan)
          @php
            $style = $slugStyles[$plan->slug] ?? ['badge' => null, 'popular' => false];
            $monthlyPrice = (int) $plan->price_monthly;
          @endphp
          <div class="col-md-6 col-lg-4 reveal" data-reveal-delay="{{ $loop->index * 100 }}">
            <div class="card pricing-card h-100 p-4 {{ $style['popular'] ? 'popular' : 'border' }}">
              @if ($style['badge'])
              <div class="position-absolute top-0 start-50 translate-middle">
                <span class="badge-popular">{{ $style['badge'] }}</span>
              </div>
              @endif
              <h4 class="fw-bold mb-2">{{ $plan->name }}</h4>
              <p class="{{ $style['popular'] ? 'small' : 'text-secondary small' }} mb-3" {{ $style['popular'] ? 'style=opacity:0.8' : '' }}>{{ $plan->slug === 'free' ? '适合初步了解' : ($plan->slug === 'pro' ? '求职季全程陪伴' : '最优性价比之选') }}</p>
              <div class="mb-4">
                @if ($monthlyPrice > 0)
                <span class="display-4 fw-bold">¥{{ rtrim(rtrim(number_format($monthlyPrice / 100, 2), '0'), '.') }}</span>
                <span class="{{ $style['popular'] ? 'small' : 'text-secondary small' }}" {{ $style['popular'] ? 'style=opacity:0.8' : '' }}>/月</span>
                @else
                <span class="display-4 fw-bold">¥0</span>
                @endif
              </div>
              <ul class="list-unstyled mb-4">
                @foreach ($planFeatures[$plan->slug] ?? [] as $item)
                <li class="mb-3 d-flex align-items-center gap-2">
                  <i class="bi bi-check-circle-fill {{ $style['popular'] ? 'text-light' : 'text-primary' }}"></i>
                  <span>{{ $item }}</span>
                </li>
                @endforeach
              </ul>
              @if ($plan->slug === 'free')
              <a href="{{ route('register') }}" class="btn btn-outline-primary w-100">免费注册</a>
              @elseif ($plan->slug === 'basic')
              @auth
                  @php $currentPlan = auth()->user()->currentPlan(); @endphp
                  @if($currentPlan && $currentPlan->slug === 'free')
                      <a href="{{ route('user.membership.subscribe', ['plan' => $plan->slug, 'cycle' => 'monthly']) }}" class="btn w-100">升级 · ¥{{ $plan->monthly_price }}/月</a>
                  @elseif($currentPlan && $currentPlan->slug !== 'basic' && $currentPlan->slug !== 'pro')
                      <a href="{{ route('user.membership.subscribe', ['plan' => $plan->slug, 'cycle' => 'monthly']) }}" class="btn w-100">升级到基础版</a>
                  @else
                      <a href="{{ route('user.membership.subscription') }}" class="btn w-100">当前套餐</a>
                  @endif
              @else
                  <a href="{{ route('register') }}" class="btn w-100">7 天免费试用</a>
              @endauth
              @guest
                  <a href="{{ route('register') }}" class="btn w-100">7 天免费试用</a>
              @endguest
              @else
              @auth
                  @php $currentPlan = auth()->user()->currentPlan(); @endphp
                  @if($currentPlan && $currentPlan->slug === 'pro')
                      <a href="{{ route('user.membership.subscription') }}" class="btn btn-outline-primary w-100">当前套餐</a>
                  @elseif($currentPlan && $currentPlan->slug !== 'free')
                      <a href="{{ route('user.membership.subscribe', ['plan' => $plan->slug, 'cycle' => 'yearly']) }}" class="btn btn-outline-primary w-100">升级 · ¥{{ $plan->yearly_price }}/年</a>
                  @else
                      <a href="{{ route('user.membership.subscribe', ['plan' => $plan->slug, 'cycle' => 'yearly']) }}" class="btn btn-outline-primary w-100">¥{{ $plan->yearly_price }}/年</a>
                  @endif
              @else
                  <a href="{{ route('register') }}" class="btn btn-outline-primary w-100">立即开通</a>
              @endauth
              @endif
            </div>
          </div>
        @endforeach
      </div>

      @if ($plans->isNotEmpty())
      <div class="text-center mt-4">
        @auth
        <a href="{{ route('user.membership.pricing') }}" class="text-secondary small">
          查看详细套餐对比 <i class="bi bi-arrow-right ms-1"></i>
        </a>
        @else
        <a href="{{ route('register') }}" class="btn btn-outline-primary">
          免费注册 <i class="bi bi-arrow-right ms-1"></i>
        </a>
        @endauth
      </div>
      @endif
    </div>
  </section>
