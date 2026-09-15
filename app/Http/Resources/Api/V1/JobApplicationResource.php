<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company' => $this->company,
            'position' => $this->position,
            'job_tags' => $this->job_tags,
            'status' => $this->status,
            'deadline' => $this->deadline,
            'applied_at' => $this->applied_at,
            'interview_at' => $this->interview_at,
            'interview_info' => $this->interview_info,
            'channel' => $this->channel,
            'note' => $this->note,
            'job_url' => $this->job_url,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'location' => $this->location,
            'company_size' => $this->company_size,
            'industry' => $this->industry,
            'status_history' => $this->status_history,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
