<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesResumeExport;
use App\Http\Controllers\User\Traits\HandlesResumeModuleDisplay;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Services\Membership\QuotaService;
use App\Services\Resume\ResumeExportHandlerService;
use App\Services\Resume\ResumeExportTaskService;
use App\Services\Resume\ResumeModuleDisplayService;

final class ResumeExportController extends Controller
{
    use HandlesResumeExport;
    use HandlesResumeModuleDisplay;
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeExportTaskService $resumeExportTaskService,
        private readonly ResumeExportHandlerService $resumeExportHandlerService,
        private readonly QuotaService $quotaService,
        private readonly ResumeModuleDisplayService $resumeModuleDisplayService,
    ) {}
}
