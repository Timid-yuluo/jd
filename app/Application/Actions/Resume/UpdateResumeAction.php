<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Infrastructure\Repositories\ResumeRepository;
use App\Models\Resume;

final class UpdateResumeAction
{
    public function __construct(
        private readonly ResumeRepository $resumeRepository,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function execute(Resume $resume, array $payload): Resume
    {
        return $this->resumeRepository->update($resume, $payload);
    }
}
