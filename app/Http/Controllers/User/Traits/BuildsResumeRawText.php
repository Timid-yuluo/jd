<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use App\Support\Resume\ResumeModuleSerializer;

trait BuildsResumeRawText
{
    /**
     * @param  array<int, array<string, mixed>>  $modules
     */
    private function buildRawFromModulesArray(array $modules): string
    {
        return ResumeModuleSerializer::toRawText($modules);
    }
}
