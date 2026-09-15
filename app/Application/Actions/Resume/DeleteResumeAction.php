<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Infrastructure\Repositories\ResumeRepository;
use App\Models\Resume;

final class DeleteResumeAction
{
    public function __construct(
        private readonly ResumeRepository $resumeRepository,
    ) {}

    public function execute(Resume $resume): void
    {
        $this->resumeRepository->delete($resume);
    }
}
