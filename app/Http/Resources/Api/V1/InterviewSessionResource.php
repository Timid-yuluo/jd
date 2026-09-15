<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InterviewSessionResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resume_id' => $this->resume_id,
            'position' => $this->position,
            'company' => $this->company,
            'type' => $this->type,
            'status' => $this->status,
            'question_count' => $this->question_count,
            'answered_count' => $this->answered_count,
            'overall_score' => $this->overall_score,
            'report' => $this->report,
            'questions' => InterviewQuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
