@extends('layouts.admin')

@section('title', '文件管理器')
@section('page-pretitle', '系统管理')
@section('page-title', '文件管理器')

@section('page-actions')
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
    <i class="ti ti-upload me-1"></i>上传文件
</button>
<button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#createFolderModal">
    <i class="ti ti-folder-plus me-1"></i>新建文件夹
</button>
@endsection

@section('content')
<div class="row row-cards">
    {{-- 统计卡片 --}}
    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">总文件数</div>
                        <div class="h2 mb-0">{{ number_format($stats['total_files']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">占用空间</div>
                        <div class="h2 mb-0">{{ $stats['total_size'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">图片文件</div>
                        <div class="h2 mb-0 text-info">{{ number_format($stats['image_count']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">文档文件</div>
                        <div class="h2 mb-0 text-warning">{{ number_format($stats['document_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 文件列表 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            @foreach ($breadcrumbs as $index => $crumb)
                                @if ($index === count($breadcrumbs) - 1)
                                    <li class="breadcrumb-item active" aria-current="page">{{ $crumb['name'] }}</li>
                                @else
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.file-manager.index', ['path' => $crumb['path']]) }}">{{ $crumb['name'] }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>名称</th>
                            <th>类型</th>
                            <th>大小</th>
                            <th>修改时间</th>
                            <th class="w-1">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- 返回上级 --}}
                        @if ($currentPath !== '')
                        <tr>
                            <td colspan="5">
                                <a href="{{ route('admin.file-manager.index', ['path' => dirname($currentPath) === '.' ? '' : dirname($currentPath)]) }}" class="text-decoration-none">
                                    <i class="ti ti-corner-left-up me-2"></i>返回上级
                                </a>
                            </td>
                        </tr>
                        @endif

                        @forelse ($files as $file)
                        <tr>
                            <td>
                                @if ($file['type'] === 'directory')
                                    <a href="{{ route('admin.file-manager.index', ['path' => $file['path']]) }}" class="text-decoration-none">
                                        <i class="ti ti-folder text-yellow me-2"></i>
                                        <span class="font-weight-medium">{{ $file['name'] }}</span>
                                    </a>
                                @else
                                    <div class="d-flex align-items-center">
                                        @php
                                            $icon = match($file['extension'] ?? '') {
                                                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'ti-photo',
                                                'pdf' => 'ti-file-text',
                                                'doc', 'docx' => 'ti-file-description',
                                                'xls', 'xlsx' => 'ti-file-spreadsheet',
                                                'ppt', 'pptx' => 'ti-file-presentation',
                                                'zip', 'rar', '7z' => 'ti-file-zip',
                                                'mp3', 'wav', 'ogg' => 'ti-file-music',
                                                'mp4', 'avi', 'mov' => 'ti-file-video',
                                                'txt', 'md' => 'ti-file-text',
                                                default => 'ti-file',
                                            };
                                        @endphp
                                        <i class="ti {{ $icon }} text-secondary me-2"></i>
                                        <span>{{ $file['name'] }}</span>
                                        @if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <img src="{{ $file['url'] }}" alt="" class="ms-2" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;" loading="lazy">
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($file['type'] === 'directory')
                                    <span class="badge bg-secondary-lt">文件夹</span>
                                @else
                                    <span class="badge bg-azure-lt">{{ strtoupper($file['extension'] ?? 'FILE') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($file['type'] === 'file')
                                    @php
                                        $bytes = $file['size'];
                                        $units = ['B', 'KB', 'MB', 'GB'];
                                        $unitIndex = 0;
                                        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
                                            $bytes /= 1024;
                                            $unitIndex++;
                                        }
                                        echo round($bytes, 2) . ' ' . $units[$unitIndex];
                                    @endphp
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ date('Y-m-d H:i', $file['modified']) }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    @if ($file['type'] === 'file')
                                        <a href="{{ $file['url'] }}" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.file-manager.download', ['path' => $file['path']]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-action="show-rename-modal" data-file-path="{{ $file['path'] }}" data-file-name="{{ $file['name'] }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.file-manager.destroy') }}" method="POST" class="d-inline" data-app-confirm="确定要删除此{{ $file['type'] === 'directory' ? '文件夹及其内容' : '文件' }}吗？">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="path" value="{{ $file['path'] }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                <i class="ti ti-folder-off mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0">此文件夹为空</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 上传文件模态框 --}}
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.file-manager.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="path" value="{{ $currentPath }}">
                <div class="modal-header">
                    <h5 class="modal-title">上传文件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">选择文件</label>
                        <input type="file" name="files[]" class="form-control" multiple required>
                        <div class="form-hint">支持多选，单个文件最大 10MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">上传</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 新建文件夹模态框 --}}
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.file-manager.create-folder') }}" method="POST">
                @csrf
                <input type="hidden" name="path" value="{{ $currentPath }}">
                <div class="modal-header">
                    <h5 class="modal-title">新建文件夹</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">文件夹名称</label>
                        <input type="text" name="folder_name" class="form-control" required pattern="[a-zA-Z0_9_\-]+" placeholder="my-folder">
                        <div class="form-hint">只能包含字母、数字、下划线和横线</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">创建</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 重命名模态框 --}}
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.file-manager.rename') }}" method="POST">
                @csrf
                <input type="hidden" name="old_path" id="rename_old_path">
                <div class="modal-header">
                    <h5 class="modal-title">重命名</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">新名称</label>
                        <input type="text" name="new_name" id="rename_new_name" class="form-control" required pattern="[a-zA-Z0-9_\-\.]+">
                        <div class="form-hint">只能包含字母、数字、下划线、横线和点</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">重命名</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
('scripts')
<script src="{{ asset('js/pages/admin-file-manager-index.js') }}"></script>

@endpush
