<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ConfirmablePasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Hash::check((string) $request->password, (string) $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => '密码不正确，请重新输入。',
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('user.dashboard', absolute: false));
    }
}
