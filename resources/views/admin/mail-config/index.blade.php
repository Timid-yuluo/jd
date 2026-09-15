@extends('layouts.admin')

@section('title', '邮件配置')
@section('page-pretitle', '系统设置')
@section('page-title', '邮件配置管理')

@section('content')
<div data-route-admin-mail-config-test="{{ route('admin.mail-config.test') }}" data-route-admin-mail-config-stats="{{ route('admin.mail-config.stats') }}">
<div class="row row-cards">
    {{-- 统计卡片 --}}
    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary me-3">
                                <i class="ti ti-mail fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">总发送量</div>
                                <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-success-lt text-success me-3">
                                <i class="ti ti-check fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">发送成功</div>
                                <div class="h2 mb-0 text-success">{{ number_format($stats['sent']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-danger-lt text-danger me-3">
                                <i class="ti ti-x fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">发送失败</div>
                                <div class="h2 mb-0 text-danger">{{ number_format($stats['failed']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-info-lt text-info me-3">
                                <i class="ti ti-calendar fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">今日发送</div>
                                <div class="h2 mb-0 text-info">{{ number_format($stats['today']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 发送趋势图表 --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">发送趋势（最近7天）</h3>
            </div>
            <div class="card-body">
                <div id="email-trend-chart" style="height: 250px;"></div>
            </div>
        </div>
    </div>

    {{-- 邮件测试 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">邮件测试</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">测试邮箱地址</label>
                    <input type="email" id="test-email" class="form-control" placeholder="test@example.com">
                    <div class="form-hint">发送测试邮件验证配置是否正确</div>
                </div>
                <button type="button" class="btn btn-primary w-100" data-action="send-test-email" id="test-btn">
                    <i class="ti ti-send me-1"></i>发送测试邮件
                </button>
                <div id="test-result" class="mt-3"></div>
            </div>
        </div>
    </div>

    {{-- 邮件配置表单 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">SMTP 配置</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.mail-config.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">邮件驱动</label>
                            <select name="mailer" class="form-select" id="mailer-select" data-action="toggle-smtp-config">
                                <option value="log" {{ $config['mailer'] === 'log' ? 'selected' : '' }}>Log（日志记录，不实际发送）</option>
                                <option value="smtp" {{ $config['mailer'] === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                <option value="sendmail" {{ $config['mailer'] === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                <option value="array" {{ $config['mailer'] === 'array' ? 'selected' : '' }}>Array（测试用）</option>
                            </select>
                            <div class="form-hint">选择邮件发送方式</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">快速配置</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-info" data-action="apply-qq-config">
                                    <i class="ti ti-brand-qq me-1"></i>QQ邮箱
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-action="apply-163-config">
                                    <i class="ti ti-mail me-1"></i>163邮箱
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-action="apply-gmail-config">
                                    <i class="ti ti-brand-gmail me-1"></i>Gmail
                                </button>
                            </div>
                            <div class="form-hint">点击自动填入常用邮箱配置</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label required">发件人邮箱</label>
                            <input type="email" name="from_address" class="form-control" value="{{ $config['from_address'] }}" required>
                            <div class="form-hint">显示在邮件中的发件人地址</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">发件人名称</label>
                            <input type="text" name="from_name" class="form-control" value="{{ $config['from_name'] }}" required>
                        </div>
                    </div>

                    {{-- SMTP 配置 --}}
                    <div id="smtp-config" style="display: {{ $config['mailer'] === 'smtp' ? 'block' : 'none' }}">
                        <hr class="my-4">
                        <h4 class="mb-3">SMTP 服务器设置</h4>

                        {{-- 常用邮箱配置说明 --}}
                        <div class="alert alert-info mb-3">
                            <h5 class="alert-title"><i class="ti ti-info-circle me-1"></i>常用邮箱配置指南</h5>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong><i class="ti ti-brand-qq text-info me-1"></i>QQ邮箱</strong>
                                    <ul class="list-unstyled small mb-0">
                                        <li>服务器: smtp.qq.com</li>
                                        <li>端口: 465 (SSL)</li>
                                        <li>密码: 使用<b>授权码</b>非QQ密码</li>
                                        <li><a href="https://service.mail.qq.com/cgi-bin/help?subtype=1&id=28&no=1001256" target="_blank">获取授权码教程</a></li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="ti ti-mail text-primary me-1"></i>163邮箱</strong>
                                    <ul class="list-unstyled small mb-0">
                                        <li>服务器: smtp.163.com</li>
                                        <li>端口: 465 (SSL)</li>
                                        <li>密码: 使用<b>授权码</b></li>
                                        <li><a href="https://help.mail.163.com/faqDetail.do?code=d7a5dc8471cd0c0e8b4b12f4f929c6f9" target="_blank">获取授权码教程</a></li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="ti ti-brand-gmail text-secondary me-1"></i>Gmail</strong>
                                    <ul class="list-unstyled small mb-0">
                                        <li>服务器: smtp.gmail.com</li>
                                        <li>端口: 587 (TLS)</li>
                                        <li>密码: 使用<b>应用专用密码</b></li>
                                        <li>需开启两步验证</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">SMTP 服务器</label>
                                <input type="text" name="host" class="form-control" value="{{ $config['host'] }}" placeholder="smtp.example.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">端口</label>
                                <input type="number" name="port" class="form-control" value="{{ $config['port'] }}" placeholder="587">
                                <div class="form-hint">常用端口：25, 465(SSL), 587(TLS)</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">加密方式</label>
                                <select name="encryption" class="form-select">
                                    <option value="tls" {{ $config['encryption'] === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $config['encryption'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ $config['encryption'] === '' ? 'selected' : '' }}>无</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">用户名</label>
                                <input type="text" name="username" class="form-control" value="{{ $config['username'] }}" placeholder="your@email.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">密码 / 授权码</label>
                                <input type="password" name="password" class="form-control" value="{{ $config['password'] }}" placeholder="{{ $config['password'] ? '已设置（留空保持不变）' : 'SMTP密码或授权码' }}">
                                <div class="form-hint text-warning">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    QQ/163等邮箱需要使用<b>授权码</b>，而非登录密码！
                                    <a href="https://mail.qq.com/cgi-bin/frame_html?sid=&r=0.5&lang=zh_CN&url=/cgi-bin/readtemplate?t=appcenter#" target="_blank" class="ms-1">如何获取QQ授权码?</a>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h4 class="mb-3">邮件发送限制</h4>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">验证邮件发送间隔（秒）</label>
                                <input type="number" name="email_verification_rate_limit" class="form-control" value="{{ $config['mail_email_verification_rate_limit'] ?? '60' }}" min="0" max="3600">
                                <div class="form-hint">设为 0 表示不限制，默认 60 秒（1分钟）</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>保存配置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 最近邮件记录 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">最近邮件记录</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.notifications.email-templates') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-template me-1"></i>邮件模板
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>收件人</th>
                            <th>主题</th>
                            <th>状态</th>
                            <th>发送时间</th>
                            <th class="w-1">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                        <tr>
                            <td>
                                @if($log->user)
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-xs bg-primary-lt text-primary me-2">
                                        {{ mb_substr($log->user->name, 0, 1) }}
                                    </span>
                                    <div>
                                        <div class="font-weight-medium">{{ $log->user->name }}</div>
                                        <div class="text-secondary small">{{ $log->recipient_email }}</div>
                                    </div>
                                </div>
                                @else
                                {{ $log->recipient_email }}
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 300px;">{{ $log->subject }}</td>
                            <td>
                                @if($log->status === 'sent')
                                <span class="badge bg-success-lt text-success">
                                    <i class="ti ti-check me-1"></i>成功
                                </span>
                                @elseif($log->status === 'failed')
                                <span class="badge bg-danger-lt text-danger" title="{{ $log->error_message }}">
                                    <i class="ti ti-x me-1"></i>失败
                                </span>
                                @else
                                <span class="badge bg-warning-lt text-warning">
                                    <i class="ti ti-clock me-1"></i>发送中
                                </span>
                                @endif
                            </td>
                            <td>
                                @if($log->sent_at)
                                {{ $log->sent_at->format('Y-m-d H:i:s') }}
                                @else
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                @endif
                            </td>
                            <td>
                                @if($log->status === 'failed')
                                <button type="button" class="btn btn-sm btn-outline-primary" data-action="retry-email" data-log-id="{{ $log->id }}" title="重试">
                                    <i class="ti ti-refresh"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                <i class="ti ti-mail-off mb-2 d-block" style="font-size: 2rem;"></i>
                                暂无邮件记录
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-mail-config-index.js') }}"></script>
@endpush