<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ResumeResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'target_job' => $this->target_job,
            'content_structured' => $this->content_structured,
            'ats_score' => $this->ats_score,
            'template' => $this->template,
            'theme' => $this->theme,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
