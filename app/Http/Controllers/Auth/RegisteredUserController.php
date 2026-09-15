<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\TurnstileValid;
use App\Services\Admin\SystemSettingService;
use App\Services\Membership\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

final class RegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! $this->isRegistrationEnabled()) {
            return redirect()->route('login')
                ->withErrors(['email' => '当前已关闭新用户注册。']);
        }

        return view('auth.register', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
            'passwordMinLength' => $this->resolvePasswordMinLength(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->isRegistrationEnabled()) {
            return redirect()->route('login')
                ->withErrors(['email' => '当前已关闭新用户注册。']);
        }

        $passwordMinLength = $this->resolvePasswordMinLength();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:'.$passwordMinLength, Rules\Password::defaults()],
            'school' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'cf-turnstile-response' => $this->turnstileRule(),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'school' => $validated['school'] ?? null,
            'major' => $validated['major'] ?? null,
        ]);

        event(new Registered($user));

        // 初始化免费套餐
        app(SubscriptionService::class)->initFreePlan($user);

        Auth::login($user);

        return redirect()->route('user.dashboard');
    }

    private function isRegistrationEnabled(): bool
    {
        $value = app(SystemSettingService::class)->get('allow_registration', '1');

        return (int) $value === 1;
    }

    private function resolvePasswordMinLength(): int
    {
        $raw = app(SystemSettingService::class)->get('password_min_length', '8');
        $value = (int) $raw;

        if ($value < 6) {
            return 8;
        }

        return min($value, 32);
    }

    /**
     * @return array<int, string|Rule>
     */
    private function turnstileRule(): array
    {
        $siteKey = config('services.turnstile.site_key');
        $secretKey = config('services.turnstile.secret_key');

        if (empty($siteKey) || empty($secretKey)) {
            return ['nullable', 'string'];
        }

        return ['required', 'string', new TurnstileValid];
    }
}
