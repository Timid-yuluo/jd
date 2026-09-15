@extends('layouts.user')

@section('title', '模板转化数据')
@section('page-pretitle', '模板中心')
@section('page-title', '模板转化数据')

@section('page-actions')
<a href="{{ route('user.resume-templates.index') }}" class="btn btn-outline-secondary">
    <i class="ti ti-arrow-left me-2"></i>返回模板库
</a>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('user.resume-templates.analytics') }}" class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label">统计周期</label>
                <select name="days" class="form-select">
                    <option value="7" {{ (int) $days === 7 ? 'selected' : '' }}>近7天</option>
                    <option value="14" {{ (int) $days === 14 ? 'selected' : '' }}>近14天</option>
                    <option value="30" {{ (int) $days === 30 ? 'selected' : '' }}>近30天</option>
                    <option value="60" {{ (int) $days === 60 ? 'selected' : '' }}>近60天</option>
                    <option value="90" {{ (int) $days === 90 ? 'selected' : '' }}>近90天</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">更新统计</button>
        </form>
    </div>
</div>

<div class="row row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small">详情浏览</div>
                <div class="h1 mb-0">{{ (int) $overview['detail_views'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small">同岗对比</div>
                <div class="h1 mb-0">{{ (int) $overview['compare_views'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small">套用次数</div>
                <div class="h1 mb-0">{{ (int) $overview['apply_count'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small">整体套用转化率</div>
                <div class="h1 mb-0">{{ number_format((float) $overview['apply_rate'], 1) }}%</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">模板转化明细（Top 20）</h3>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>模板</th>
                    <th>详情浏览</th>
                    <th>同岗对比</th>
                    <th>套用次数</th>
                    <th>新建套用</th>
                    <th>现有套用</th>
                    <th>对比率</th>
                    <th>套用转化率</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['template_name'] }}</td>
                        <td>{{ (int) $row['detail_views'] }}</td>
                        <td>{{ (int) $row['compare_views'] }}</td>
                        <td>{{ (int) $row['apply_count'] }}</td>
                        <td>{{ (int) $row['apply_new_count'] }}</td>
                        <td>{{ (int) $row['apply_existing_count'] }}</td>
                        <td>{{ number_format((float) $row['compare_rate'], 1) }}%</td>
                        <td>{{ number_format((float) $row['apply_rate'], 1) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary">当前周期暂无数据。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
