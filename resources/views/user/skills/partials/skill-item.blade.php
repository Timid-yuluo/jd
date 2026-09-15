@php
    $skill = $skill ?? null;
@endphp
<div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center mb-1">
            <strong class="me-2">{{ $skill->skill_name }}</strong>
            <span class="badge bg-{{ $skill->skill_category === 'hard' ? 'primary' : 'success' }}-lt">
                {{ $skill->skill_category === 'hard' ? '硬技能' : '软技能' }}
            </span>
        </div>
        <div class="text-secondary small">
            <span class="me-3"><i class="ti ti-chart-bar me-1"></i>{{ $skill->proficiency_label }}</span>
            @if($skill->years_used > 0)
            <span class="me-3"><i class="ti ti-clock me-1"></i>{{ $skill->years_used }} 年</span>
            @endif
            @if($skill->last_used_at)
            <span><i class="ti ti-calendar me-1"></i>{{ $skill->last_used_at->format('Y-m') }}</span>
            @endif
        </div>
        @if($skill->evidence)
        <div class="text-secondary small mt-1">
            <i class="ti ti-certificate me-1"></i>{{ $skill->evidence }}
        </div>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="text-end me-2" style="min-width: 60px;">
            <div class="progress progress-sm">
                <div class="progress-bar bg-primary" style="width: {{ $skill->proficiency_percent }}%"></div>
            </div>
            <div class="text-secondary small mt-1">{{ $skill->proficiency }}/5</div>
        </div>
        <button class="btn btn-sm btn-outline-primary" onclick="editSkill(
            {{ $skill->id }},
            '{{ addslashes($skill->skill_name) }}',
            '{{ $skill->skill_category }}',
            {{ $skill->proficiency }},
            {{ $skill->years_used ?? 'null' }},
            '{{ $skill->last_used_at?->format('Y-m-d') ?? '' }}',
            '{{ addslashes($skill->evidence ?? '') }}'
        )">
            <i class="ti ti-edit"></i>
        </button>
        <form action="{{ route('user.skills.destroy', $skill) }}" method="POST" style="display:inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除？')">
                <i class="ti ti-trash"></i>
            </button>
        </form>
    </div>
</div>
