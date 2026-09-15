<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use Illuminate\Http\Request;

trait InteractsWithAsyncResponses
{
    private function expectsAsyncResponse(Request $request): bool
    {
        return $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
