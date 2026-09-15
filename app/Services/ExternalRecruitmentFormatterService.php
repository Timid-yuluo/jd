<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalRecruitment;

final class ExternalRecruitmentFormatterService
{
    public function buildPrefillJobDescription(?ExternalRecruitment $recruitment): string
    {
        if (! $recruitment) {
            return '';
        }

        $searchText = trim((string) data_get($recruitment->raw_payload, 'searchText', ''));
        if ($searchText !== '') {
            return $searchText;
        }

        return trim(
            collect([
                $recruitment->title ? '职位标题：'.$recruitment->title : null,
                $recruitment->company ? '公司：'.$recruitment->company : null,
                $recruitment->recruitment_type === ExternalRecruitment::TYPE_CAMPUS ? '招聘类型：校招' : '招聘类型：社招',
                $recruitment->work_location ? '地点：'.$recruitment->work_location : null,
                $recruitment->industry ? '行业：'.$recruitment->industry : null,
                $recruitment->batch ? '批次：'.$recruitment->batch : null,
                $recruitment->source_tags ? '标签：'.implode(' / ', (array) $recruitment->source_tags) : null,
                $recruitment->positions ? '岗位信息：'.$recruitment->positions : null,
                $recruitment->remarks ? '备注：'.$recruitment->remarks : null,
            ])->filter()->implode("\n")
        );
    }
}
