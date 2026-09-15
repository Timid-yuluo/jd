<div id="app-toast-wrap" class="app-toast-wrap" aria-live="polite" aria-atomic="true"></div>
<div id="app-confirm-mask" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:12000;">
    <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="width:min(460px,100%);background:#fff;border-radius:14px;box-shadow:0 16px 40px rgba(15,23,42,.25);overflow:hidden;">
            <div style="padding:14px 16px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;font-weight:700;color:#0f172a;">
                <i class="ti ti-help-circle" style="color:#2563eb;"></i>
                <span id="app-confirm-title">请确认</span>
            </div>
            <div id="app-confirm-message" style="padding:16px;font-size:14px;color:#475569;line-height:1.6;white-space:pre-wrap;"></div>
            <div style="padding:12px 16px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #e2e8f0;">
                <button type="button" id="app-confirm-cancel" class="btn btn-outline-secondary">取消</button>
                <button type="button" id="app-confirm-ok" class="btn btn-primary">确定</button>
            </div>
        </div>
    </div>
</div>
<div id="app-prompt-mask" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:12001;">
    <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="width:min(460px,100%);background:#fff;border-radius:14px;box-shadow:0 16px 40px rgba(15,23,42,.25);overflow:hidden;">
            <div style="padding:14px 16px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;font-weight:700;color:#0f172a;">
                <i class="ti ti-edit" style="color:#2563eb;"></i>
                <span id="app-prompt-title">请输入</span>
            </div>
            <div id="app-prompt-message" style="padding:16px 16px 8px;font-size:14px;color:#475569;line-height:1.6;white-space:pre-wrap;"></div>
            <div style="padding:0 16px 12px;">
                <input id="app-prompt-input" type="text" class="form-control" />
                <textarea id="app-prompt-textarea" class="form-control" style="display:none;min-height:120px;resize:vertical;"></textarea>
                <div id="app-prompt-hint" style="display:none;margin-top:8px;font-size:12px;color:#dc2626;"></div>
                <div id="app-prompt-counter" style="display:none;margin-top:6px;font-size:12px;color:#64748b;text-align:right;"></div>
            </div>
            <div style="padding:12px 16px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #e2e8f0;">
                <button type="button" id="app-prompt-cancel" class="btn btn-outline-secondary">取消</button>
                <button type="button" id="app-prompt-ok" class="btn btn-primary">确定</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/pages/partials-ui-notify.js') }}"></script>
@endpush

{{-- ========== 意见反馈浮动按钮 + Modal ========== --}}
@auth
<button type="button" class="feedback-fab" data-bs-toggle="modal" data-bs-target="#feedbackModal" title="意见反馈" aria-label="意见反馈">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
</button>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="ti ti-message-circle me-1"></i> 意见反馈
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="feedbackForm" method="POST" action="{{ route('feedback.store', absolute: false) }}" data-route-feedback-store="{{ route('feedback.store', absolute: false) }}" data-feedback-index-url="{{ route('feedback.index', absolute: false) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="page_url" id="feedbackPageUrl">
                    <input type="hidden" name="page_name" id="feedbackPageName">
                    <input type="hidden" name="screen" id="feedbackScreen">
                    <input type="hidden" name="language" id="feedbackLang">

                    <div class="mb-3">
                        <label class="form-label required">反馈类型</label>
                        <select name="category" class="form-select" required>
                            <option value="bug">Bug 报告</option>
                            <option value="suggestion">功能建议</option>
                            <option value="ux">体验问题</option>
                            <option value="other">其他</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label required mb-0">标题</label>
                            <span class="form-text mt-0" id="feedbackTitleCounter">0 / 200</span>
                        </div>
                        <input type="text" name="title" class="form-control" maxlength="200" placeholder="简要描述您的反馈" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label required mb-0">详细描述</label>
                            <span class="form-text mt-0" id="feedbackContentCounter">0 / 5000</span>
                        </div>
                        <textarea name="content" class="form-control" rows="4" maxlength="5000" placeholder="请详细描述您遇到的问题或建议..." required></textarea>
                    </div>

                    <div class="form-text text-muted">
                        <i class="ti ti-link me-1"></i>当前页面：<span id="feedbackPageDisplay"></span>
                    </div>
                    <div class="form-text text-muted mt-1" id="feedbackDraftHint"></div>
                    <div class="d-none mt-2" id="feedbackDraftSwitcherWrap">
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm" id="feedbackDraftSwitcher" aria-label="切换草稿"></select>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="feedbackDraftNewBtn">新建</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="feedbackDraftDeleteBtn">删除当前</button>
                        </div>
                    </div>
                    <div class="alert alert-success py-2 px-3 mt-3 mb-0 d-none" id="feedbackInlineSuccess" role="status">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <div><i class="ti ti-check me-1"></i><span id="feedbackInlineSuccessText">反馈已提交，正在关闭弹窗...</span></div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-success d-none" id="feedbackCopyIdBtn">复制反馈编号</button>
                                <a href="{{ route('feedback.index', absolute: false) }}" class="btn btn-sm btn-success" id="feedbackSuccessLink">去我的反馈查看</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary" id="feedbackSubmitBtn">
                        <i class="ti ti-send me-1"></i> 提交反馈
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/pages/partials-ui-notify-2.js') }}"></script>
@endpush
@endauth
