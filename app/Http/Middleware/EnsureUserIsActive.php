<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            if ($user->isSuspended()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                /** @var RedirectResponse $response */
                $response = redirect()->route('login')
                    ->withErrors(['email' => '账号已被封禁，请联系管理员处理。']);

                return $response;
            }

            if ($user->isPendingDeletion()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                /** @var RedirectResponse $response */
                $response = redirect()->route('login')
                    ->withErrors([
                        'email' => '您的账号已申请注销，正在冷静期内。如需恢复，请使用邮件中的恢复链接。',
                    ]);

                return $response;
            }
        }

        return $next($request);
    }
}
