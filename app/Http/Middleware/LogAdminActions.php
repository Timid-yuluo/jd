<?php

namespace App\Http\Middleware;

use App\Models\AdminActionLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            AdminActionLog::create([
                'user_id' => $user->id,
                'action' => $request->method() . ' ' . $request->path(),
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $request->except(['password', 'password_confirmation', 'current_password']),
            ]);
        }

        return $response;
    }
}
