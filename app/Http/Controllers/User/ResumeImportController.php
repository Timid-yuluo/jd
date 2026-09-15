<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesResumeControllerLogging;
use App\Http\Controllers\User\Traits\HandlesResumeFileImport;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Services\Resume\ResumeFileImportService;

final class ResumeImportController extends Controller
{
    use HandlesResumeControllerLogging;
    use HandlesResumeFileImport;
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeFileImportService $resumeFileImportService,
    ) {}
}
