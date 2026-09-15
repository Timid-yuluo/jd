<div x-show="showShortcuts" x-transition class="border rounded p-2 bg-light mx-3 mt-2" style="font-size: 12px;">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="fw-medium"><i class="ti ti-keyboard me-1"></i>快捷键</span>
        <button type="button" class="btn btn-ghost-secondary btn-sm p-0" @click="showShortcuts = false"><i class="ti ti-x"></i></button>
    </div>
    <div class="row g-1 text-muted">
        <div class="col-6"><kbd>Ctrl</kbd>+<kbd>S</kbd> 保存</div>
        <div class="col-6"><kbd>Ctrl</kbd>+<kbd>Z</kbd> 撤销</div>
        <div class="col-6"><kbd>Ctrl</kbd>+<kbd>Y</kbd> 重做</div>
        <div class="col-6"><kbd>?</kbd> 快捷键帮助</div>
    </div>
</div>
