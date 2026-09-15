@extends('layouts.admin')

@section('title', '数据导出')
@section('page-pretitle', '系统管理')
@section('page-title', '数据导出')

@section('content')
<div data-url-admin-data-export="{{ url('admin/data-export') }}">
<div class="row row-cards">
    @foreach($exportTypes as $key => $type)
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100" data-type="{{ $key }}">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-azure-lt me-3">
                        <i class="ti {{ $type['icon'] }}"></i>
                    </span>
                    <div>
                        <h3 class="card-title m-0">{{ $type['label'] }}</h3>
                        <div class="text-secondary small">{{ number_format($type['count']) }} 条记录</div>
                    </div>
                </div>
                <p class="text-secondary">{{ $type['description'] }}</p>
            </div>
            <div class="card-footer">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-action="preview-data" data-export-key="{{ $key }}">
                        <i class="ti ti-eye me-1"></i> 预览
                    </button>
                    <a href="{{ route('admin.data-export.export', $key) }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-download me-1"></i> 导出 CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">使用说明</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4 class="alert-title">关于数据导出</h4>
            <ul class="mb-0">
                <li>导出格式为 CSV，兼容 Excel 和其他表格软件</li>
                <li>数据采用 UTF-8 编码，如 Excel 打开乱码请选择 UTF-8 编码导入</li>
                <li>大量数据导出可能需要较长时间，请耐心等待</li>
                <li>导出数据不包含敏感信息（如密码、Token等）</li>
            </ul>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">数据预览</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3"></div>
                    <p class="mb-0">加载中...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-data-export-index.js') }}"></script>
@endpush

